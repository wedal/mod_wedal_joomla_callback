<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

/**
 * Клиент API sms.ru — https://sms.ru/docs/api
 *
 * Модуль обращается к sms.ru ровно за одним — отправить уведомление о заявке,
 * поэтому в классе один метод. Остаток на счёте для письма приходит в ответе
 * на ту же отправку. Остальные разделы API (стоимость, статус доставки, стоплист,
 * обработчики обратных вызовов, отправка письмом) модуль не вызывал никогда.
 *
 * Ключ API уходит в теле POST-запроса, а не в строке запроса: адреса запросов
 * попадают в журналы веб-сервера и прокси, тела — нет.
 *
 * Ответ в любом случае приходит объектом SmsRuResult: и отказ sms.ru, и обрыв
 * связи описываются одинаково, разбирать json_decode() на месте вызова не нужно.
 */
final class SmsRu
{
	/** Значение поля status успешного ответа — одно на всё API. */
	public const STATUS_OK = 'OK';

	/** Базовый адрес API. Только https: в теле запроса уходит ключ. */
	private const BASE_URL = 'https://sms.ru/';

	/**
	 * Пределы ожидания, секунды. Запрос идёт внутри отправки формы, поэтому оба
	 * короткие: недоступный sms.ru не должен держать посетителя до max_execution_time.
	 */
	private const CONNECT_TIMEOUT = 5;
	private const TIMEOUT = 10;

	/**
	 * Попыток на запрос: первая и один повтор при обрыве связи. Каждая добавляет
	 * посетителю до TIMEOUT секунд ожидания, поэтому повтор ровно один.
	 */
	private const ATTEMPTS = 2;

	/**
	 * Параметры отправки, которые принимает этот клиент.
	 *
	 * Список закрытый: через него не переопределить api_id и не отправить
	 * опечатку в имени параметра вместо текста сообщения.
	 *
	 * @see https://sms.ru/api/send
	 */
	private const SEND_PARAMETERS = array('to', 'msg', 'from', 'time', 'ttl', 'daytime', 'translit', 'test', 'partner_id', 'ip');

	/** @var string Ключ API из настроек модуля. */
	private $apiKey;

	/**
	 * Создаёт клиент API sms.ru.
	 *
	 * @param   string  $apiKey  Ключ API из личного кабинета sms.ru.
	 */
	public function __construct($apiKey)
	{
		$this->apiKey = trim((string) $apiKey);
	}

	/**
	 * Отправляет сообщение одному или нескольким получателям.
	 *
	 * Остаток на счёте приходит в этом же ответе — отдельный запрос
	 * к `my/balance` ради него не нужен.
	 *
	 * @param   array  $message  Параметры из SEND_PARAMETERS. Обязательны:
	 *                           to  — номер получателя или несколько через запятую (до 100);
	 *                           msg — текст сообщения в UTF-8.
	 *
	 * @return  SmsRuResult
	 */
	public function send(array $message)
	{
		$parameters = array_intersect_key($message, array_flip(self::SEND_PARAMETERS));

		if (!$this->isFilled($parameters, 'to')) {
			return SmsRuResult::fromLocalError('No sms.ru recipient is configured.');
		}

		if (!$this->isFilled($parameters, 'msg')) {
			return SmsRuResult::fromLocalError('The sms.ru message text is empty.');
		}

		return $this->request('sms/send', $parameters);
	}

	/**
	 * Заполнен ли обязательный параметр отправки.
	 *
	 * Значения приходят из настроек модуля, то есть строками. Массив здесь —
	 * ошибка вызова, а не пустое значение, и уезжать на шлюз ему незачем.
	 *
	 * @param   array   $parameters  Параметры отправки.
	 * @param   string  $name        Имя параметра.
	 *
	 * @return  bool
	 */
	private function isFilled(array $parameters, $name)
	{
		return isset($parameters[$name]) && is_scalar($parameters[$name]) && trim((string) $parameters[$name]) !== '';
	}

	/**
	 * Выполняет запрос к API и разбирает ответ.
	 *
	 * @param   string  $method      Метод API, например sms/send.
	 * @param   array   $parameters  Параметры метода без api_id.
	 *
	 * @return  SmsRuResult
	 */
	private function request($method, array $parameters)
	{
		if ($this->apiKey === '') {
			return SmsRuResult::fromLocalError('The sms.ru API key is not set.');
		}

		$parameters['api_id'] = $this->apiKey;

		$url = self::BASE_URL . $method . '?json=1';
		$body = http_build_query($parameters);
		$error = '';

		for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
			$response = $this->execute($url, $body);

			if ($response['body'] !== false) {
				return SmsRuResult::fromJson($response['body']);
			}

			$error = $response['error'];
		}

		return SmsRuResult::fromLocalError('The sms.ru server could not be reached: ' . $error);
	}

	/**
	 * Одна попытка HTTP-запроса.
	 *
	 * @param   string  $url   Полный адрес метода.
	 * @param   string  $body  Тело POST-запроса.
	 *
	 * @return  array  ['body' => string|false, 'error' => string]. false в body — попытка неудачна.
	 */
	private function execute($url, $body)
	{
		$handle = curl_init($url);

		if ($handle === false) {
			return array('body' => false, 'error' => 'the cURL handle could not be created');
		}

		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_HEADER, false);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
		curl_setopt($handle, CURLOPT_TIMEOUT, self::TIMEOUT);

		$response = curl_exec($handle);
		$code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$error = $response === false ? (string) curl_error($handle) : '';
		unset($handle);

		if ($response === false) {
			return array('body' => false, 'error' => $error);
		}

		// Отказ шлюза приходит страницей с кодом 5xx. Разбирать в ней нечего, но повторная попытка имеет смысл.
		if ($code >= 400) {
			return array('body' => false, 'error' => 'HTTP ' . $code);
		}

		return array('body' => $response, 'error' => '');
	}
}
