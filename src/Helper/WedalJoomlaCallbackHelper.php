<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Helper for mod_wedal_joomla_callback
 *
 * Точка входа модуля: его вызывают диспетчер (getForm()) и com_ajax (методы *Ajax()),
 * а шаблоны получают экземпляр в переменной $form. Сама работа разложена по помощникам:
 *
 *  - FormBuilderHelper    — поля формы по настройкам модуля;
 *  - SpamProtectionHelper — поле-ловушка, время заполнения и лимит заявок;
 *  - AttachmentHelper     — приём, проверка и удаление вложений;
 *  - EmailHelper          — письмо с заявкой;
 *  - SmsHelper            — SMS через sms.ru;
 *  - TelegramHelper       — уведомление в Telegram;
 *  - LogHelper            — журнал модуля.
 */

class WedalJoomlaCallbackHelper extends \stdClass
{

	/**
	 * Инициализирует приложение и передаёт параметры JavaScript в документ.
	 */
	public function __construct()
	{
		$this->app = Factory::getApplication();

		//Параметры для JS
		$js_params['itemid'] = $this->app->getInput()->get('Itemid', null, 'int');
		$js_params['baseurl'] = Uri::root(true);

		$this->app->getDocument()->addScriptOptions('wedal_joomla_callback', $js_params);
	}

	/**
	 * Экранирует значение для вывода в HTML-атрибут шаблона.
	 *
	 * @param   mixed  $value  Значение из параметров модуля или полей формы.
	 *
	 * @return  string
	 */
	public static function escapeAttribute($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	/**
	 * Класс обёртки поля для шаблона — имя поля, приведённое к тем же символам, что и идентификатор. Шаблоны брали для этого `$field->id`. После появления префикса id имени больше не равен, а класс обёртки обязан остаться прежним по причинам обратной совместимости.
	 *
	 * @param   object  $field  Поле формы Joomla.
	 *
	 * @return  string
	 */
	public static function fieldWrapperClass($field)
	{
		return preg_replace('#\W#', '_', (string) $field->getAttribute('name'));
	}

	/**
	 * Загружает параметры модуля и формирует поля формы.
	 *
	 * @param   object|int|array  $moduleid        Модуль, его идентификатор либо массив: тогда идентификатор берётся из запроса.
	 * @param   bool              $startFormTimer  Отметить в сессии момент показа формы.
	 *
	 * @return  bool  false — модуль недоступен, форма не собрана.
	 */
	public function getForm($moduleid, $startFormTimer = true)
	{
		if ($moduleid instanceof \stdClass) {
			$this->moduleid = (int) ($moduleid->id ?? 0);
			$module = ($moduleid->module ?? null) === 'mod_wedal_joomla_callback' ? $moduleid : null;
		} else {
			if (is_array($moduleid)) {
				$this->moduleid = $this->app->getInput()->get('modid', null, 'int');
			} else {
				$this->moduleid = (int) $moduleid;
			}

			$module = $this->getAccessibleModule($this->moduleid);
		}

		if ($module === null) {
			return false;
		}

		$this->params = new Registry;
		$this->params->loadString($module->params);
		if ($startFormTimer) {
			$this->formStartedAt = (new SpamProtectionHelper($this->app))->startFormTimer($this->moduleid);
		}

		$this->app->getLanguage()->load('mod_wedal_joomla_callback');

		$this->moduletype = $this->params->get('moduletype', 0);
		$this->itemid = $this->app->getInput()->get('Itemid', null, 'int');

		$this->buttontext = $this->params->get('buttontext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_BUTTONTEXT_DEFAULT'));
		$this->thankyoutext = $this->params->get('thankyoutext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT'));

		$this->moduleclass_sfx = htmlspecialchars($this->params->get('moduleclass_sfx') ?? '', ENT_COMPAT, 'UTF-8');
		$this->formdesc = $this->params->get('formdesc', '');

		if ($this->params->get('showformtitle', '1')) {
			$this->formtitle = $this->params->get('formtitle', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'));
		}

		$this->form = (new FormBuilderHelper($this->app, $this->params, $this->moduleid))->build();
		$this->fields = $this->form->getXml();

		return true;
	}

	/**
	 * Возвращает модуль, доступный текущему посетителю и назначенный на текущую страницу.
	 *
	 * @param   int  $moduleId  Идентификатор модуля.
	 *
	 * @return  object|null  Модуль либо null, если он недоступен или это модуль другого типа.
	 */
	private function getAccessibleModule($moduleId)
	{
		if ($moduleId <= 0) {
			return null;
		}

		$module = ModuleHelper::getModuleById((string) $moduleId);

		if (
			!is_object($module)
			|| (int) ($module->id ?? 0) !== $moduleId
			|| $module->module !== 'mod_wedal_joomla_callback'
		) {
			return null;
		}

		return $module;
	}

	/**
	 * Возвращает разметку всплывающей формы для AJAX-запроса.
	 *
	 * Вызывается с format=raw, поэтому ответ должен быть обычным текстом или HTML, а не JsonResponse.
	 *
	 * @return  bool  Всегда false: разметка уже напечатана.
	 */
	public function getFormAjax()
	{
		$moduleId = Factory::getApplication()->getInput()->get('modid', null, 'int');

		$form = new WedalJoomlaCallbackHelper;
		if (!$form->getForm($moduleId)) {
			echo htmlspecialchars(Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR'), ENT_QUOTES, 'UTF-8');
			return false;
		}

		require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $form->params->get('layout', 'default') . '_popupform');
		return false;
	}

	/**
	 * Отдаёт живое состояние формы: токен сессии и метку начала заполнения. Встроенная форма попадает в кэш страниц Joomla вместе с разметкой: токен там принадлежит чужой сессии, а метка в сессии не создаётся вовсе, потому что модуль не рендерится. Поэтому и то и другое выдаётся отдельным запросом, минующим кэш, так же, как их получает всплывающая форма в getFormAjax().
	 *
	 * @return  array  Ответ com_ajax: токен формы и признак ошибки.
	 */
	public function getFormStateAjax()
	{
		$moduleId = (int) $this->app->getInput()->get('modid', null, 'int');

		$this->app->getLanguage()->load('mod_wedal_joomla_callback');

		if ($this->getAccessibleModule($moduleId) === null) {
			return $this->getInvalidModuleResponse();
		}

		(new SpamProtectionHelper($this->app))->startFormTimer($moduleId);

		return array('token' => Session::getFormToken(), 'error' => 0);
	}

	/**
	 * Проверяет и отправляет заявку, полученную через AJAX.
	 *
	 * @return  array  Ответ com_ajax: текст для посетителя и признак ошибки.
	 */
	public function sendFormAjax()
	{
		//Check token
		if (!$this->hasValidToken()) {
			return $this->getInvalidTokenResponse();
		}

		$moduleId = $this->app->getInput()->get('modid', null, 'int');
		$page_url = $this->getPageUrl();

		$form = new WedalJoomlaCallbackHelper;
		if (!$form->getForm($moduleId, false)) {
			return $this->getInvalidModuleResponse();
		}

		$data = $this->app->getInput()->post->getArray();

		if ((new SpamProtectionHelper($this->app))->isSpam($moduleId, $form->params, $data)) {
			return $this->getSpamProtectionResponse();
		}

		$form->values  = $form->form->filter($data);

		$result = $form->form->validate($form->values);

		if (!$result)
		{
			return $this->getValidationErrorResponse($form->form->getErrors());
		}

		unset($form->values['tos_box']); //Наверное мы не хотим видеть согласие с условиями в письме, т.к. это предполагается по умолчанию.

		$thankyoutext = $form->params->get('thankyoutext', '');
		if (!$thankyoutext) {
			$thankyoutext = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT');
		}

		$attachments = new AttachmentHelper($this->app);

		try {
			// Почтовик бросает исключение на некорректный адрес, поэтому письмо готовится под обработчиком.
			$mail = new EmailHelper($this->app, $form);

			if (!$mail->prepare()) {
				return $this->getDeliveryErrorResponse();
			}

			//Отправка СМС. Обязана быть до сборки тела письма: макет письма печатает
			//результат отправки, когда включена настройка show_smsinfo_in_mail.
			$sms_status = null;

			if ($form->params->get('enable_sms')) {
				$sms_status = (new SmsHelper($form))->send();

				if ($sms_status === false) {
					return $this->getDeliveryErrorResponse();
				}
			}

			$attached_files = $attachments->receive($form);

			if (!$mail->send($page_url, $sms_status, $attached_files)) {
				return $this->getDeliveryErrorResponse();
			}

			//Отправка в Telegram. Должна быть до удаления загруженных файлов!
			if ($form->params->get('enable_telegram')) {
				if (!(new TelegramHelper($form))->send($attached_files, $page_url)) {
					LogHelper::add('The Telegram notification was not delivered.');
				}
			}
		} catch (\Throwable $exception) {
			return $this->getDeliveryErrorResponse();
		} finally {
			$attachments->cleanup();
		}

		return array('message' => $thankyoutext, 'error' => 0);
	}

	/**
	 * Адрес страницы, с которой отправлена заявка. Приходит от посетителя, поэтому принимается только http(s) разумной длины.
	 *
	 * @return  string|null  Проверенный адрес либо null, если он не прошёл проверку.
	 */
	private function getPageUrl()
	{
		$pageUrl = rawurldecode((string) $this->app->getInput()->get('page', '', 'RAW'));

		if (strlen($pageUrl) > 2048 || !filter_var($pageUrl, FILTER_VALIDATE_URL)) {
			return null;
		}

		$pageScheme = parse_url($pageUrl, PHP_URL_SCHEME);

		return in_array(strtolower((string) $pageScheme), array('http', 'https'), true) ? $pageUrl : null;
	}

	/**
	 * Повторяет проверку Session::checkToken(), но без редиректа.
	 *
	 * При новой сессии checkToken() уводит запрос на index.php: fetch идёт по редиректу
	 * и получает HTML главной страницы вместо JSON, после чего разбор ответа на клиенте падает.
	 *
	 * @return  bool  Запрос принёс действующий токен формы.
	 */
	private function hasValidToken()
	{
		$token = Session::getFormToken();
		$input = $this->app->getInput();

		if ($input->post->get($token, '', 'alnum')) {
			return true;
		}

		return $input->server->get('HTTP_X_CSRF_TOKEN', '', 'alnum') === $token;
	}

	/**
	 * Возвращает ответ об истёкшем токене в том же виде, что и остальные ветки sendFormAjax().
	 *
	 * Собственный echo склеивался с ответом com_ajax в два JSON-документа подряд, и клиент не мог их разобрать.
	 *
	 * @return  array  Ответ com_ajax: текст для посетителя и признак ошибки.
	 */
	private function getInvalidTokenResponse()
	{
		return array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_INVALID_TOKEN'), 'error' => 1);
	}

	/**
	 * Возвращает нейтральный ответ, не раскрывая конфигурацию недоступного модуля.
	 *
	 * @return  array  Ответ com_ajax: текст для посетителя и признак ошибки.
	 */
	private function getInvalidModuleResponse()
	{
		return array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR'), 'error' => 1);
	}

	/**
	 * Возвращает ответ о непройденной проверке формы.
	 *
	 * @param   array  $errors  Ошибки, накопленные Form::validate().
	 *
	 * @return  array  Ответ com_ajax: текст для посетителя и признак ошибки.
	 */
	private function getValidationErrorResponse(array $errors)
	{
		$messages = array(Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR'));

		foreach ($errors as $error) {
			if ($error instanceof \Throwable) {
				$message = $error->getMessage();
			} elseif (is_scalar($error) || $error instanceof \Stringable) {
				$message = (string) $error;
			} else {
				continue;
			}

			$message = trim(strip_tags($message));

			if ($message !== '' && !in_array($message, $messages, true)) {
				$messages[] = $message;
			}
		}

		return array('message' => implode("\n", $messages), 'error' => 1);
	}

	/**
	 * Формирует единый ответ при срабатывании антиспам-защиты.
	 *
	 * @return  array  Ответ com_ajax: текст для посетителя и признак ошибки.
	 */
	private function getSpamProtectionResponse()
	{
		return array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SPAM_PROTECTION_ERROR'), 'error' => 1);
	}

	/**
	 * Возвращает нейтральный ответ, не раскрывая детали сбоя доставки.
	 *
	 * @return  array  Ответ com_ajax: текст для посетителя и признак ошибки.
	 */
	private function getDeliveryErrorResponse()
	{
		return array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_DELIVERY_ERROR'), 'error' => 1);
	}

}
