<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\Form;

/**
 * Helper for mod_wedal_joomla_callback
 */

class WedalJoomlaCallbackHelper extends \stdClass
{
	private const SAFE_ATTACHMENT_TYPES = array(
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
		'webp' => 'image/webp',
		'avif' => 'image/avif',
		'bmp' => 'image/bmp',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	);

	// Во сколько раз порог общего потолка модуля выше персонального порога по IP/сессии.
	private const RATE_LIMIT_MODULE_FACTOR = 20;

	// Инициализирует приложение и параметры JavaScript.
	public function __construct()
	{
		$this->app = Factory::getApplication();

		//Параметры для JS
		$js_params['itemid'] = $this->app->getInput()->get('Itemid', null, 'int');

		$this->app->getDocument()->addScriptOptions('wedal_joomla_callback', $js_params);
	}

	// Экранирует значение для вывода в HTML-атрибут шаблона.
	public static function escapeAttribute($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	// Загружает параметры модуля и формирует поля формы.
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
			$this->startFormTimer($this->moduleid);
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

		$this->form = new Form('form'.$this->moduleid);
		$this->form->load('<form><fieldset name="fields"></fieldset></form>'); //array("control" => "WJCForm_" . $this->moduleid )

		$this->createFields();

		$this->fields = $this->form->getXml();

		return true;
	}

	// Возвращает модуль, доступный текущему посетителю и назначенный на текущую страницу.
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

	// Добавляет динамически сформированное поле в форму.
	public function createField($form_params, $fieldset = 'fields'){
		$note = new \SimpleXMLElement('<field />');

		$form_params->class = $form_params->name;

		foreach ($form_params as $key => $value) {
			// SimpleXMLElement приводит true к "1", а Joomla считает поле обязательным
			// только при required="true" или required="required" (FormField::validate()).
			// Поэтому булевы значения атрибутов нормализуем в строки.
			if (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			}

			$note->addAttribute($key, (string) $value);
		}

		$this->form->setField($note, null, true, $fieldset);
	}

	// Возвращает CAPTCHA-плагин модуля, если он выбран и включён.
	private function getCaptchaPlugin()
	{
		$plugin = trim((string) $this->params->get('captcha', '0'));

		if ($plugin === '' || $plugin === '0') {
			return '';
		}

		if (!PluginHelper::isEnabled('captcha', $plugin)) {
			Log::add(
				sprintf('The CAPTCHA plugin "%s" selected in the module is not enabled, the field was skipped.', $plugin),
				Log::WARNING,
				'mod_wedal_joomla_callback'
			);

			return '';
		}

		return $plugin;
	}

	//Создает базовые поля модуля согласно настройкам в нем
	public function createFields(){

		//Имя
		if ($this->params->get('showname', ''))
		{
			$form_field       = new \stdClass();
			$form_field->name = 'name';
			$form_field->type = 'text';
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME');
			$form_field->hint = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME');
			$form_field->{'data-error'} = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME_ERROR');
			$form_field->filter = 'STRING';

			if ($this->params->get('shownamereq', ''))
			{
				$form_field->required = true;
			}

			$this->createField($form_field);
		}

		//Email
		if ($this->params->get('showemail', ''))
		{
			$form_field       = new \stdClass();
			$form_field->name = 'email';
			$form_field->type = 'email';
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_MAIL');
			$form_field->hint = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_MAIL');
			$form_field->{'data-error'} = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_EMAIL_ERROR');
			$form_field->validate = 'email';

			if ($this->params->get('showemailreq', ''))
			{
				$form_field->required = true;
			}

			$this->createField($form_field);
		}

		//Телефон
		if ($this->params->get('showphone', ''))
		{
			$form_field = new \stdClass();
			$form_field->name = 'phone';
			$form_field->type = 'tel';
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE');
			$form_field->hint = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE');
			$form_field->{'data-error'} = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE_ERROR');

			if ($this->params->get('showphonereq', ''))
			{
				$form_field->required = true;
			}

			$this->createField($form_field);
		}

		//Комментарий
		if ($this->params->get('showtextarea', ''))
		{
			$form_field = new \stdClass();
			$form_field->name = 'comment';
			$form_field->type = 'textarea';
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TEXTAREA');
			$form_field->hint = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TEXTAREA');
			$form_field->{'data-error'} = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_MESSAGE_ERROR');

			if ($this->params->get('showtextareareq', ''))
			{
				$form_field->required = true;
			}

			$this->createField($form_field);
		}

		//Вложение
		if ($this->params->get('showattachment', ''))
		{
			$form_field = new \stdClass();
			$form_field->name = 'attachments';
			$form_field->type = 'file';
			$form_field->label = $this->params->get('attachmentlabel', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_ATTACHMENT_LABEL_TITLE'));
			$form_field->accept = $this->params->get('attachmentformat', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_ATTACHMENT_FORMAT_TITLE'));

			if ($this->params->get('allow_multi_attachment', ''))
			{
				$form_field->multiple = true;
			}

			$this->createField($form_field);
		}

		//Дополнительные поля
		$customfields = $this->createCustomFields();

		$captchaPlugin = $this->getCaptchaPlugin();

		if ($captchaPlugin !== '') {
			$form_field = new \stdClass();
			$form_field->name = 'captcha';
			$form_field->type = 'captcha';
			$form_field->plugin = $captchaPlugin;
			// Своё пространство имён на экземпляр модуля, чтобы виджеты нескольких форм
			// на одной странице не конфликтовали.
			$form_field->namespace = 'wjcallback' . $this->moduleid;
			$form_field->validate = 'captcha';
			$form_field->required = true;
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_CAPTCHA');

			$this->createField($form_field);
		}

		//Согласие с условиями
		if ($this->params->get('showtos'))
		{
			$form_field = new \stdClass();
			$form_field->name = 'tos_box';

			if ($this->params->get('toscheckbox')) {
				$form_field->type = 'checkbox';
				$form_field->required = true;
				$form_field->{'data-error'} = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TOS_ERROR');
			} else {
				$form_field->type = 'note';
				$form_field->heading = 'div';
			}

			if ($this->params->get('toslink', '#') != '#')
			{
				$tosLinkText = $this->params->get('toslinktext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TOSLINKTEXT_TITLE'));
				$form_field->label = $tosLinkText;

				try {
					$article = $this->app->bootComponent('com_content')->getMVCFactory()->createModel('Articles', 'Site', ['ignore_request' => true]);

					$article->setState('filter.article_id', $this->params->get('toslink'));
					$article->setState('filter.published', 1);
					$article->setState('params', Factory::getApplication()->getParams());
					$article->setState('list.limit', 1);
					$tosArticles = $article->getItems();

					if (!isset($tosArticles[0]) || !is_object($tosArticles[0])) {
						Log::add('The configured Terms of Service article is unavailable.', Log::WARNING, 'mod_wedal_joomla_callback');
					} else {
						$tosArticle = $tosArticles[0];
						$articleSlug = $tosArticle->id . ':' . $tosArticle->alias;
						$tosLink = Route::_(RouteHelper::getArticleRoute($articleSlug, $tosArticle->catid, $tosArticle->language));
						$form_field->label = Text::sprintf('MOD_WEDAL_JOOMLA_CALLBACK_TOSTEXT', $tosLink, $tosLinkText);
					}
				} catch (\Throwable $exception) {
					Log::add('The configured Terms of Service article could not be loaded.', Log::WARNING, 'mod_wedal_joomla_callback');
				}
			}


			$this->createField($form_field, $customfields ? 'customfields' : 'fields');

		}
	}

	//Создает дополнительные поля модуля согласно настройкам на вкладке дополнительных полей
	public function createCustomFields(){
		if (!$this->params->get('enable_customfields', '0')) {
			return false;
		}

		if (!$this->params->get('customfields', '')) {
			return false;
		}

		$custom_xml = '<form><fieldset name="customfields">' .$this->params->get('customfields', ''). '</fieldset></form>';
		$this->form->load($custom_xml);

		return true;
	}

	// Возвращает разметку всплывающей формы для AJAX-запроса.
	// Вызывается с format=raw, поэтому ответ должен быть обычным текстом/HTML, а не JsonResponse.
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

	/** Отдаёт живое состояние формы: токен сессии и метку начала заполнения. Встроенная форма попадает в кэш страниц Joomla вместе с разметкой: токен там принадлежит чужой сессии, а метка в сессии не создаётся вовсе, потому что модуль не рендерится. Поэтому и то и другое выдаётся отдельным запросом, минующим кэш, так же, как их получает всплывающая форма в getFormAjax().
	 */
	public function getFormStateAjax()
	{
		$moduleId = (int) $this->app->getInput()->get('modid', null, 'int');

		$this->app->getLanguage()->load('mod_wedal_joomla_callback');

		if ($this->getAccessibleModule($moduleId) === null) {
			return $this->getInvalidModuleResponse();
		}

		$this->startFormTimer($moduleId);

		return new JsonResponse(Array('token' => Session::getFormToken(), 'error' => 0));
	}

	// Проверяет и отправляет заявку, полученную через AJAX.
	public function sendFormAjax()
	{
		//Check token
		if (!$this->hasValidToken()) {
			return $this->getInvalidTokenResponse();
		}

		$moduleId = $this->app->getInput()->get('modid', null, 'int');
		$pageUrl = rawurldecode((string) $this->app->getInput()->get('page', '', 'RAW'));
		$page_url = null;

		if (strlen($pageUrl) <= 2048 && filter_var($pageUrl, FILTER_VALIDATE_URL)) {
			$pageScheme = parse_url($pageUrl, PHP_URL_SCHEME);

			if (in_array(strtolower((string) $pageScheme), array('http', 'https'), true)) {
				$page_url = $pageUrl;
			}
		}

		$form = new WedalJoomlaCallbackHelper;
		if (!$form->getForm($moduleId, false)) {
			return $this->getInvalidModuleResponse();
		}

		$data = $this->app->getInput()->post->getArray();

		if ($form->params->get('enable_antispam', 1)) {
			if (!empty($data['wjcallback_website']) || !$this->hasMinimumFillTime($moduleId, $form->params)) {
				return $this->getSpamProtectionResponse();
			}

			if ($this->isRateLimited($moduleId, $form->params)) {
				return $this->getSpamProtectionResponse();
			}
		}

		$form->values  = $form->form->filter($data);

		$result = $form->form->validate($form->values);

		if (!$result)
		{
			$errors = $form->form->getErrors();
			return new JsonResponse(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR') . ':' . json_encode($errors) , 'error' => 0));
		}

		unset($form->values['tos_box']); //Наверное мы не хотим видеть согласие с условиями в письме, т.к. это предполагается по умолчанию.

		//Отправка на почту
		$mailtitle = $form->params->get('mailtitle', '');
		if (!$mailtitle) {
			$mailtitle = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_MAILTITLE_DEFAULT');
		}

		$email =  $form->params->get('email', '');
		if (!$email) {
			$email = $this->app->get('mailfrom');
		}

		$thankyoutext = $form->params->get('thankyoutext', '');
		if (!$thankyoutext) {
			$thankyoutext = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT');
		}

		ob_start();
		require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $form->params->get('layout', 'default') . '_message');
		$body = ob_get_contents();
		ob_end_clean();

		$to = $email;
		$from = array($this->app->get('mailfrom') , $this->app->get('fromname') );
		$subject = $mailtitle;

		$this->mailer = Factory::getMailer();
		$this->mailer->setSender($from);

		$this->mailer->addRecipient($to);

		if ($form->params->get('email_additional', '')) {
			$additional_recipients = preg_split('/\r\n|[\r\n]/', $form->params->get('email_additional', ''));

			foreach ($additional_recipients as $additional_recipient) {
				if (MailHelper::isEmailAddress($additional_recipient)) {
					$this->mailer->addRecipient($additional_recipient);
				}
			}
		}

		// Проверяем, есть ли среди дополнительных полей поля типа file и, если таковые имеются, прикрепляем выбранные файлы как вложения к письму
		$attached_files = array();

		try {
			if (!empty($form->values['email'])) {
				$this->mailer->addReplyTo($form->values['email']);
			}

			//Отправка СМС
			if ($form->params->get('enable_sms')) {
				$sms_status = $this->sendSMS($form);

				if ($sms_status === false) {
					return $this->getDeliveryErrorResponse();
				}
			}

			foreach ($form->form->getFieldset('customfields') as $field) {
				if (!empty($field->getAttribute('name')) && !empty($field->getAttribute('type')) && $field->getAttribute('type') == 'file') {
					$custom_attached_files = $this->attach_file($field->getAttribute('name'), $form, true);

					if ($custom_attached_files && is_array($custom_attached_files)) {
						$attached_files = array_merge($attached_files, $custom_attached_files);
					}
				}
			}

			// Стандартное Вложение
			if ($form->params->get('showattachment')) {
				$standart_attached_files = $this->attach_file('attachments', $form, true);

				if ($standart_attached_files && is_array($standart_attached_files)) {
					$attached_files = array_merge($attached_files, $standart_attached_files);
				}
			}

			$this->mailer->setSubject($subject);
			$this->mailer->setBody($body);
			$this->mailer->isHTML();

			if ($this->mailer->send() !== true) {
				return $this->getDeliveryErrorResponse();
			}

			//Отправка в Telegram. Должна быть до удаления загруженных файлов!
			if ($form->params->get('enable_telegram')) {
				if (!$this->sendTelegram($form, $attached_files, $page_url)) {
					return $this->getDeliveryErrorResponse();
				}
			}
		} catch (\Throwable $exception) {
			return $this->getDeliveryErrorResponse();
		} finally {
			if (!empty($attached_files)) {
				$tmpPath = $this->app->get('tmp_path');

				foreach ($attached_files as $file) {
					$filename = $file['stored_name'];
					$dest = $tmpPath . '/' . $filename;

					if (File::exists($dest)) {
						File::delete($dest);
					}
				}
			}
		}

		return new JsonResponse(Array('message' => $thankyoutext, 'error' => 0));
	}

	// Повторяет проверку Session::checkToken(), но без редиректа.
	// При новой сессии checkToken() уводит запрос на index.php: fetch идёт по редиректу
	// и получает HTML главной страницы вместо JSON, после чего разбор ответа на клиенте падает.
	private function hasValidToken()
	{
		$token = Session::getFormToken();
		$input = $this->app->getInput();

		if ($input->post->get($token, '', 'alnum')) {
			return true;
		}

		return $input->server->get('HTTP_X_CSRF_TOKEN', '', 'alnum') === $token;
	}

	// Возвращает ответ об истёкшем токене в том же виде, что и остальные ветки sendFormAjax().
	// Собственный echo склеивался с ответом com_ajax в два JSON-документа подряд, и клиент не мог их разобрать.
	private function getInvalidTokenResponse()
	{
		return new JsonResponse(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_INVALID_TOKEN'), 'error' => 1));
	}

	// Возвращает нейтральный ответ, не раскрывая конфигурацию недоступного модуля.
	private function getInvalidModuleResponse()
	{
		return new JsonResponse(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR'), 'error' => 1));
	}

	// Отмечает в сессии момент, с которого посетитель видит форму.
	private function startFormTimer($moduleId)
	{
		$this->formStartedAt = time();
		$this->app->getSession()->set('wjcallback.form_started.' . $moduleId, $this->formStartedAt);
	}

	// Проверяет, что пользователь заполнял форму не слишком быстро.
	private function hasMinimumFillTime($moduleId, Registry $params)
	{
		$minimumFillTime = max(0, min(60, (int) $params->get('minimum_fill_time', 3)));
		$formStartedAt = (int) $this->app->getSession()->get('wjcallback.form_started.' . $moduleId, 0);

		return $formStartedAt > 0 && time() - $formStartedAt >= $minimumFillTime;
	}

	// Проверяет и увеличивает счётчики лимита заявок.
	// Отказ хранилища не должен блокировать заявку: honeypot, минимальное время, заполнения и CAPTCHA продолжают работать, поэтому здесь fail-open.
	private function isRateLimited($moduleId, Registry $params)
	{
		try {
			return $this->checkRateLimit($moduleId, $params);
		} catch (\Throwable $exception) {
			Log::add('The rate limit storage is unavailable, the check was skipped.', Log::WARNING, 'mod_wedal_joomla_callback');

			return false;
		}
	}

	// Считает заявки в скользящем окне по модулю, IP-адресу и сессии.
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

	// Возвращает хранилище счётчиков лимита заявок.
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

	// Формирует единый ответ при срабатывании антиспам-защиты.
	private function getSpamProtectionResponse()
	{
		return new JsonResponse(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SPAM_PROTECTION_ERROR'), 'error' => 1));
	}

	// Возвращает нейтральный ответ, не раскрывая детали сбоя доставки.
	private function getDeliveryErrorResponse()
	{
		return new JsonResponse(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_DELIVERY_ERROR'), 'error' => 1));
	}

	/**
	 * Прикрепляет файл к сообщению или письму
	 *
	 * @param   string   $file_field_name  	Имя файла вложения.
	 * @param   mixed    $form    			Объект формы
	 * @param   bool  	$attach_to_mail     Прикреплять ли файл к письму
	 *
	 * @return  boolean	True on success.
	 */
	public function attach_file($file_field_name, $form, $attach_to_mail) {

		$files = $this->app->getInput()->files->get($file_field_name);

		$tmpPath = $this->app->get('tmp_path');

		if ((!$form->params->get('allow_multi_attachment', '') && $file_field_name == 'attachments') || $file_field_name != 'attachments') {
			$files_tmp = $files;
			unset($files);
			$files = array();
			$files[0] = $files_tmp;
		}

		if (!empty($files[0]['name']))
		{
			$returned_files = array();

			foreach ($files as $key => $file)
			{
				if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
					continue;
				}

				$fileExt = strtolower(File::getExt($file['name']));
				$mimeType = false;
				$finfo = new \finfo(FILEINFO_MIME_TYPE);

				if ($finfo) {
					$mimeType = $finfo->file($file['tmp_name']);
				}

				$customAccept = $form->form->getField($file_field_name)->getAttribute('accept');
				$attachmentAccept = trim((string) $form->params->get('attachmentformat', ''));
				
				if ($file_field_name === 'attachments') {
					if (!empty($attachmentAccept)) {
						$accept = $attachmentAccept;
					} else {
						$accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx';
					}
				} else {
					$accept = $customAccept;
				}


				if (!$this->isValidFileType($fileExt, $mimeType, $accept)) {
					continue;
				}

				$storedName = bin2hex(random_bytes(16)) . '.' . $fileExt;
				$dest = $tmpPath . '/' . $storedName;

				if (File::upload($file['tmp_name'], $dest)) {
					$file['stored_name'] = $storedName;
					$file['mime_type'] = $mimeType;
					$returned_files[$key] = $file;

					if ($attach_to_mail) {
						$this->mailer->addAttachment($dest, File::makeSafe($file['name']));
					}
				}
			}
			return $returned_files;
		}

	}

	// Сверяет расширение и MIME-тип файла с разрешёнными форматами.
	public function isValidFileType($file_ext, $filetype, $accept) {

		if (!$accept || !is_string($filetype)) {
			return false;
		}

		$file_ext = strtolower($file_ext);
		$filetype = strtolower($filetype);
		$accept = strtolower($accept);

		$accept_rules = explode(',', str_replace(' ', '', $accept));

		if (count($accept_rules) == 0 && (!isset(self::SAFE_ATTACHMENT_TYPES[$file_ext]) || self::SAFE_ATTACHMENT_TYPES[$file_ext] !== $filetype)) {
			return false;
		}

		//Разбираем все правила на отдельные расширения и MIME
		foreach ($accept_rules as $accept_rule) {
			if (strripos($accept_rule,'/')) {
				if (strripos($accept_rule,'/*')) {
					$rules_full_mime[] = stristr($accept_rule,'/*',true);
				} else {
					$rules_mime[] = $accept_rule;
				}

			} else {
				$rules_ext[] = $accept_rule;
			}
		}

		if (!empty($rules_ext) && (in_array($file_ext, $rules_ext) || in_array('.' . $file_ext, $rules_ext))) {
			return true;
		}

		if (!empty($rules_mime) && in_array($filetype, $rules_mime)) {
			return true;
		}

		//Остается случай, когда accept задан в формате image/*
		if (!empty($rules_full_mime) && in_array(stristr($filetype,'/',true), $rules_full_mime)) {
			return true;
		}

		return false;
	}

	// Отправляет уведомление о заявке через SMS.ru.
	public function sendSMS($form) {
		if (!$form->params->get('sms_api_key') || !$form->params->get('sms_recipient_number')) {
			return false;
		}

		//Формируем СМС сообщение
		$sms_message = '';

		if ($form->params->get('sms_introtext')) {
			$sms_message .= $form->params->get('sms_introtext');
		}

		$sms_send_fields = $form->params->get('sms_send_fields');

		if (!empty($sms_send_fields)) {
			$sms_send_fields_array = explode(',', str_replace(' ', '', $sms_send_fields));
			$sms_send_fields_limit = $form->params->get('sms_send_fields_limit', 100);
			$sms_message_field_values = array();

			foreach ($sms_send_fields_array as $sms_send_field) {
				if (!empty($form->values[$sms_send_field]))	{
					if (is_array($form->values[$sms_send_field])) {
						$sms_send_field_value = implode(', ', $form->values[$sms_send_field]);
					} else {
						$sms_send_field_value = (string) $form->values[$sms_send_field];
					}
					$sms_message_field_values[] = mb_strimwidth($sms_send_field_value, 0, $sms_send_fields_limit, '..');
				}
			}

			$sms_message .= implode(',', $sms_message_field_values);
			$sms_message = mb_strimwidth($sms_message, 0, $form->params->get('sms_send_fields_total_limit', 450), '');
		}

		require_once('sms.ru.php');
		$apikey =  $form->params->get('sms_api_key');
		$sms = new \SMSRU($apikey);

		$smsdata = new \stdClass();
		$smsdata->to = $form->params->get('sms_recipient_number');
		$smsdata->text = $sms_message;

		if ($form->params->get('sms_transliterate')) {
			$smsdata->translit = 1;
		}

		$smsdata->partner_id = '410554';
		$sms_response = $sms->send_one($smsdata);

		if (isset($sms_response->status) && $sms_response->status == "OK") {
			$sms_balance = $sms->getBalance();
			$return_message = Text::sprintf( 'MOD_WEDAL_JOOMLA_CALLBACK_SMS_SEND_SUCCESS', $sms_response->sms_id, $sms_balance->balance);
		} else {
			return false;
		}

		return $return_message;
	}

	// Отправляет уведомление и вложения в Telegram.
	public function sendTelegram($form, $attached_files, $page_url = null) {
		if (!$form->params->get('telegram_api_key') || !$form->params->get('telegram_chat_id')) {
			return false;
		}

		//Отправка запроса
		$tg_query = array(
			"chat_id" 	=> $form->params->get('telegram_chat_id'),
			"text"  	=> $this->buildTelegramMessage($form, $page_url),
			"parse_mode" => "html",
		);

		$ch = curl_init("https://api.telegram.org/bot". $form->params->get('telegram_api_key') ."/sendMessage");

		if ($ch === false) {
			return false;
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tg_query));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HEADER, false);

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		unset($ch);

		if (!$this->isSuccessfulTelegramResponse($result, $httpCode)) {
			return false;
		}

		//Отправка вложений
		if (empty($attached_files)) {
			return true;
		}

		$tmpPath = $this->app->get('tmp_path');
		$query_media = array();

		foreach ($attached_files as $key => $file)
		{
			$filename = $file['stored_name'];
			$dest     = $tmpPath . '/' . $filename;
			$query_media[$key]['type'] = 'photo';
			$query_media[$key]['media'] = 'attach://' . $filename;
			//$query_media[$key]['caption'] = @todo: добавить caption для изображений из label полей

			$tg_query[$filename] = new \CURLFile($dest, $file['mime_type'], File::makeSafe($file['name']));
		}

		$tg_query['media'] = json_encode($query_media);

		$ch = curl_init('https://api.telegram.org/bot'. $form->params->get('telegram_api_key') .'/sendMediaGroup');

		if ($ch === false) {
			return false;
		}

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $tg_query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		unset($ch);

		return $this->isSuccessfulTelegramResponse($res, $httpCode);
	}

	// Собирает текст уведомления для Telegram. 
	private function buildTelegramMessage($form, $page_url = null)
	{
		$tg_message = '';

		if ($form->params->get('telegram_introtext')) {
			$tg_message .= $this->escapeTelegramHtml($form->params->get('telegram_introtext')) . "\n\n";
		}

		foreach ($form->values as $key => $value) {
			if (is_array($value)) {
				$value = implode(', ', $value);
			}

			$tg_message .= $this->escapeTelegramHtml($form->form->getFieldAttribute($key, 'label')) . ': ' . $this->escapeTelegramHtml($value) . "\n";
		}

		if (!empty($page_url)) {
			$tg_message .= "\n" . $this->escapeTelegramHtml(Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND_FROM_URL')) . "\n" . $this->escapeTelegramHtml($page_url);
		}

		return $tg_message;
	}

	// Экранирует значение для сообщения Telegram с parse_mode=html.
	private function escapeTelegramHtml($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	// Проверяет транспортный и API-результат Telegram, не раскрывая ответ пользователю.
	private function isSuccessfulTelegramResponse($response, $httpCode)
	{
		if (!is_string($response) || $httpCode < 200 || $httpCode >= 300) {
			return false;
		}

		$payload = json_decode($response);

		return is_object($payload) && !empty($payload->ok);
	}

}