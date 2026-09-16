<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

/**
 * Антиспам-защита заявок: поле-ловушка, минимальное время заполнения и лимит частоты отправки.
 *
 * CAPTCHA сюда не входит: это обычное поле формы — его добавляет FormBuilderHelper,
 * а проверяет Joomla вместе с остальными полями.
 */
final class SpamProtectionHelper
{
	// Во сколько раз порог общего потолка модуля выше персонального порога по IP/сессии.
	private const RATE_LIMIT_MODULE_FACTOR = 20;

	/** @var object Приложение Joomla. */
	private $app;

	/**
	 * Создаёт помощник антиспам-защиты заявок.
	 *
	 * @param   object  $app  Приложение Joomla.
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Проверяет заявку антиспам-защитой модуля, если она включена в настройках.
	 *
	 * Заявка, прошедшая ловушку и проверку времени, учитывается в счётчиках лимита —
	 * даже если её потом отклонит проверка полей формы.
	 *
	 * @param   int       $moduleId  Идентификатор модуля.
	 * @param   Registry  $params    Параметры модуля.
	 * @param   array     $data      Данные POST-запроса.
	 *
	 * @return  bool  true — заявку нужно отклонить.
	 */
	public function isSpam($moduleId, Registry $params, array $data)
	{
		if (!$params->get('enable_antispam', 1)) {
			return false;
		}

		// Поле-ловушка скрыто от посетителя, заполняет его только робот.
		if (!empty($data['wjcallback_website']) || !$this->hasMinimumFillTime($moduleId, $params)) {
			return true;
		}

		return $this->isRateLimited($moduleId, $params);
	}

	/**
	 * Отмечает в сессии момент, с которого посетитель видит форму.
	 *
	 * @param   int  $moduleId  Идентификатор модуля.
	 *
	 * @return  int  Метка времени.
	 */
	public function startFormTimer($moduleId)
	{
		$startedAt = time();
		$this->app->getSession()->set('wjcallback.form_started.' . $moduleId, $startedAt);

		return $startedAt;
	}

	/**
	 * Проверяет, что посетитель заполнял форму не слишком быстро.
	 *
	 * @param   int       $moduleId  Идентификатор модуля.
	 * @param   Registry  $params    Параметры модуля.
	 *
	 * @return  bool  Форма заполнялась не быстрее заданного порога.
	 */
	private function hasMinimumFillTime($moduleId, Registry $params)
	{
		$minimumFillTime = max(0, min(60, (int) $params->get('minimum_fill_time', 3)));
		$formStartedAt = (int) $this->app->getSession()->get('wjcallback.form_started.' . $moduleId, 0);

		return $formStartedAt > 0 && time() - $formStartedAt >= $minimumFillTime;
	}

	/**
	 * Проверяет и увеличивает счётчики лимита заявок.
	 *
	 * Отказ хранилища не должен блокировать заявку: поле-ловушка, минимальное время
	 * заполнения и CAPTCHA продолжают работать, поэтому здесь fail-open.
	 *
	 * @param   int       $moduleId  Идентификатор модуля.
	 * @param   Registry  $params    Параметры модуля.
	 *
	 * @return  bool  true — лимит заявок исчерпан.
	 */
	private function isRateLimited($moduleId, Registry $params)
	{
		try {
			return $this->checkRateLimit($moduleId, $params);
		} catch (\Throwable $exception) {
			LogHelper::add('The rate limit storage is unavailable, the check was skipped.');

			return false;
		}
	}

	/**
	 * Считает заявки в скользящем окне по модулю, IP-адресу и сессии.
	 *
	 * @param   int       $moduleId  Идентификатор модуля.
	 * @param   Registry  $params    Параметры модуля.
	 *
	 * @return  bool  true — хотя бы один из счётчиков достиг своего предела.
	 */
	private function checkRateLimit($moduleId, Registry $params)
	{
		$maximumRequests = max(1, min(20, (int) $params->get('rate_limit_requests', 3)));
		$window = max(60, min(86400, (int) $params->get('rate_limit_window', 3600)));
		$now = time();
		$sessionId = (string) $this->app->getSession()->getId();
		$ipAddress = (string) $this->app->getInput()->server->getString('REMOTE_ADDR', 'unknown');
		$cache = $this->getRateLimitCache($window);

		$limits = array(
			'module:' . $moduleId => $maximumRequests * self::RATE_LIMIT_MODULE_FACTOR,
			'ip:' . hash('sha256', $ipAddress) => $maximumRequests,
			'session:' . hash('sha256', $sessionId) => $maximumRequests,
		);
		$cacheEntries = array();

		foreach ($limits as $key => $maximum) {
			$cacheId = 'rate_limit_' . hash('sha256', $key);
			$timestamps = $cache->get($cacheId);
			$timestamps = is_array($timestamps) ? $timestamps : array();
			$timestamps = array_values(array_filter($timestamps, static function ($timestamp) use ($now, $window) {
				return is_int($timestamp) && $timestamp > $now - $window;
			}));

			if (count($timestamps) >= $maximum) {
				return true;
			}

			$timestamps[] = $now;
			$cacheEntries[$cacheId] = $timestamps;
		}

		foreach ($cacheEntries as $cacheId => $timestamps) {
			$cache->store($timestamps, $cacheId);
		}

		return false;
	}

	/**
	 * Возвращает хранилище счётчиков лимита заявок.
	 *
	 * @param   int  $window  Размер скользящего окна, секунды.
	 *
	 * @return  \Joomla\CMS\Cache\Controller\OutputController
	 */
	private function getRateLimitCache($window)
	{
		return Factory::getContainer()
			->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', array(
				'defaultgroup' => 'mod_wedal_joomla_callback',
				'caching' => true,
				'lifetime' => (int) ceil($window / 60),
				'locking' => false,
			));
	}
}
