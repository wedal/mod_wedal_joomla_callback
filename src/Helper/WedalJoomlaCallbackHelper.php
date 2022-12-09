<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Mail\MailHelper;
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
class WedalJoomlaCallbackHelper
{
	public function __construct()
	{
		$this->app = Factory::getApplication();

		//Параметры для JS
		$js_params['itemid'] = $this->app->input->get('Itemid', null, 'int');

		$this->app->getDocument()->addScriptOptions('wedal_joomla_callback', $js_params);
	}

	public function getForm($moduleid)
	{
		if (is_array($moduleid)) {
			$this->moduleid = $this->app->input->get('modid', null, 'int');
		} else {
			$this->moduleid = (int) $moduleid;
		}

		$module = ModuleHelper::getModuleById((string) $this->moduleid);

		$this->params = new Registry;
		$this->params->loadString($module->params);

		$this->app->getLanguage()->load('mod_wedal_joomla_callback');

		$this->moduletype = $this->params->get('moduletype', 0);
		$this->itemid = $this->app->input->get('Itemid', null, 'int');

		$this->buttontext = $this->params->get('buttontext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_BUTTONTEXT_DEFAULT'));
		$this->thankyoutext = $this->params->get('thankyoutext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT'));

		$this->moduleclass_sfx = htmlspecialchars($this->params->get('moduleclass_sfx') ?? '', ENT_COMPAT, 'UTF-8');
		$this->formdesc = $this->params->get('formdesc', '');

		if ($this->params->get('showformtitle', '1')) {
			$this->formtitle = $this->params->get('formtitle', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'));
		}

		$this->form = Form::getInstance('form'.$this->moduleid, '<form><fieldset name="fields"></fieldset></form>'); //array("control" => "WJCForm_" . $this->moduleid )

		$this->createFields();

		$this->fields = $this->form->getXml();
	}

	public function createField($form_params, $fieldset = 'fields'){  //!!!! Динамическая генерация полей через Jform
		$note = new \SimpleXMLElement('<field />');

		$form_params->class = $form_params->name;

		foreach ($form_params as $key => $value) {
			$note->addAttribute($key, $value);
		}

		$this->form->setField($note, null, true, $fieldset);
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
			$form_field->filter = 'raw';

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

				$article = $this->app->bootComponent('com_content')->getMVCFactory()->createModel('Articles', 'Site', ['ignore_request' => true]);

				$article->setState('article.id', $this->params->get('toslink'));
				$article->setState('filter.published', 1);
				$article->setState('params', Factory::getApplication()->getParams());
				$article->setState('list.limit', 1);

				$tos_article   = $article->getItems();
				$article_slug     = $tos_article[0]->id . ':' . $tos_article[0]->alias;
				$tos_link = Route::_(RouteHelper::getArticleRoute($article_slug, $tos_article[0]->catid, $tos_article[0]->language));

				$form_field->label = Text::sprintf('MOD_WEDAL_JOOMLA_CALLBACK_TOSTEXT', $tos_link, $this->params->get('toslinktext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TOSLINKTEXT_TITLE')));
			}


			$this->createField($form_field, $customfields ? 'customfields' : '');

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

	public function getFormAjax()
	{
		$moduleId = Factory::getApplication()->input->get('modid', null, 'int');

		$form = new WedalJoomlaCallbackHelper;
		$form->getForm($moduleId);

		require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $form->params->get('layout', 'default') . '_popupform');
		return false;
	}

	public function sendFormAjax()
	{
		//Check token
		if (!Session::checkToken()) {
			echo json_encode(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_INVALID_TOKEN'), 'error' => 1));
			return;
		}

		$moduleId = $this->app->input->get('modid', null, 'int');
		$page_url = urldecode($this->app->input->get('page', null, 'STRING'));

		$form = new WedalJoomlaCallbackHelper;
		$form->getForm($moduleId);

		$data = $this->app->input->post->getArray();
		$form->values  = $form->form->filter($data);

		$result = $form->form->validate($form->values);

		if (!$result)
		{
			$errors = $form->getErrors();
			return new JsonResponse(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR') . ':' . $errors , 'error' => 0));
		}

		unset($form->values['tos_box']); //Наверное мы не хотим видеть согласие с условиями в письме, т.к. это предполагается по умолчанию.

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
		htmlspecialchars(require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $form->params->get('layout', 'default') . '_message'), ENT_QUOTES);
		$body = ob_get_contents();
		ob_end_clean();

		$to = $email;
		$from = array($this->app->get('mailfrom') , $this->app->get('fromname') );
		$subject = $mailtitle;

		$mailer = Factory::getMailer();
		$mailer->setSender($from);

		if ($form->values['email']) {
			$mailer->addReplyTo($form->values['email']);
		}

		$mailer->addRecipient($to);

		if ($form->params->get('email_additional', '')) {
			$additional_recipients = preg_split('/\r\n|[\r\n]/', $form->params->get('email_additional', ''));

			foreach ($additional_recipients as $additional_recipient) {
				if (MailHelper::isEmailAddress($additional_recipient)) {
					$mailer->addRecipient($additional_recipient);
				}
			}
		}

		//Вложение
		if ($form->params->get('showattachment'))
		{
			$tmpPath = $this->app->get('tmp_path');

			$files = $this->app->input->files->get('attachments');

			if ($files[0]['name'])
			{
				foreach ($files as $file)
				{
					$filename = File::makeSafe($file['name']);
					$src      = $file['tmp_name'];
					$dest     = $tmpPath . '/' . $filename;

					if (File::upload($src, $dest))
					{
						$file_ext = File::getExt($dest);

						if ($this->isValidFileType($file_ext, $file['type'], $form->params->get('attachmentformat', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_ATTACHMENT_FORMAT_TITLE'))))
						{
							$mailer->addAttachment($dest);
						}
						else
						{
							File::delete($dest);
						}
					}
				}
			}
		}
		//--

		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->isHTML();
		$mailer->send();

		//Удаляем файлы вложений после отправки письма
		if ($form->params->get('showattachment') && is_array($files))
		{
			foreach ($files as $file)
			{
				$filename = File::makeSafe($file['name']);
				$dest     = $tmpPath . '/' . $filename;

				if (File::exists($dest)) {
					File::delete($dest);
				}
			}
		}

		return new JsonResponse(Array('message' => $thankyoutext, 'error' => 0));
	}

	public function isValidFileType($file_ext, $filetype, $accept) {

		if (!$accept) {
			return true;
		}

		$file_ext = strtolower($file_ext);
		$filetype = strtolower($filetype);
		$accept = strtolower($accept);

		$accept_rules = explode(',', str_replace(' ', '', $accept));

		if (count($accept_rules) == 0) {
			return true;
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
}
