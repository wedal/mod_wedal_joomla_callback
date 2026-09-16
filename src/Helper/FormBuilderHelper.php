<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;

/**
 * Форма модуля: базовые поля по настройкам, дополнительные поля из XML, CAPTCHA,
 * согласие с условиями и идентификаторы полей, уникальные в пределах страницы.
 */
final class FormBuilderHelper
{
	/** @var object Приложение Joomla: через него загружается статья с условиями. */
	private $app;

	/** @var Registry Параметры модуля. */
	private $params;

	/** @var int Идентификатор модуля. */
	private $moduleId;

	/**
	 * @param   object    $app       Приложение Joomla.
	 * @param   Registry  $params    Параметры модуля.
	 * @param   int       $moduleId  Идентификатор модуля.
	 */
	public function __construct($app, Registry $params, $moduleId)
	{
		$this->app = $app;
		$this->params = $params;
		$this->moduleId = (int) $moduleId;
	}

	/**
	 * Собирает форму модуля по его настройкам.
	 *
	 * @return  Form
	 */
	public function build()
	{
		$form = new Form('form' . $this->moduleId);
		$form->load('<form><fieldset name="fields"></fieldset></form>'); //array("control" => "WJCForm_" . $this->moduleid )

		$this->createFields($form);
		$this->prefixFieldIds($form);

		return $form;
	}

	/**
	 * Приписывает полям формы идентификатор с номером модуля. Без этого id поля равен его имени (`name`, `email`, `phone`), и на странице с двумя экземплярами модуля идентификаторы дублируются: `<label for>` ведёт на чужое поле, а скринридер и клик по label попадают не туда.
	 *
	 * @param   Form  $form  Собранная форма.
	 */
	private function prefixFieldIds($form)
	{
		$xml = $form->getXml();

		if (!($xml instanceof \SimpleXMLElement)) {
			return;
		}

		$fields = $xml->xpath('//field');

		if (!is_array($fields)) {
			return;
		}

		// Номер модуля делает id уникальным в пределах страницы.
		$prefix = 'wjc' . $this->moduleId . '_';

		foreach ($fields as $field) {
			$name = (string) $field['name'];

			if ($name === '' || (string) $field['id'] !== '') {
				continue;
			}

			$field->addAttribute('id', $prefix . $name);
		}
	}

	// Добавляет динамически сформированное поле в форму.
	private function createField($form, $form_params, $fieldset = 'fields')
	{
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

		$form->setField($note, null, true, $fieldset);
	}

	// Возвращает CAPTCHA-плагин модуля, если он выбран и включён.
	private function getCaptchaPlugin()
	{
		$plugin = trim((string) $this->params->get('captcha', '0'));

		if ($plugin === '' || $plugin === '0') {
			return '';
		}

		if (!PluginHelper::isEnabled('captcha', $plugin)) {
			LogHelper::add(sprintf('The CAPTCHA plugin "%s" selected in the module is not enabled, the field was skipped.', $plugin));

			return '';
		}

		return $plugin;
	}

	//Создает базовые поля модуля согласно настройкам в нем
	private function createFields($form){

		//Имя
		if ($this->params->get('showname', ''))
		{
			$form_field       = new \stdClass();
			$form_field->name = 'name';
			$form_field->type = 'text';
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME');
			$form_field->hint = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME');
			$form_field->{'data-error'} = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME_ERROR');

			if ($this->params->get('shownamereq', ''))
			{
				$form_field->required = true;
			}

			$this->createField($form, $form_field);
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

			$this->createField($form, $form_field);
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

			$this->createField($form, $form_field);
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

			$this->createField($form, $form_field);
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

			$this->createField($form, $form_field);
		}

		//Дополнительные поля
		$customfields = $this->createCustomFields($form);

		$captchaPlugin = $this->getCaptchaPlugin();

		if ($captchaPlugin !== '') {
			$form_field = new \stdClass();
			$form_field->name = 'captcha';
			$form_field->type = 'captcha';
			$form_field->plugin = $captchaPlugin;
			// Своё пространство имён на экземпляр модуля, чтобы модули нескольких форм на одной странице не конфликтовали.
			$form_field->namespace = 'wjcallback' . $this->moduleId;
			$form_field->validate = 'captcha';
			$form_field->required = true;
			$form_field->label = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_CAPTCHA');

			$this->createField($form, $form_field);
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
				$form_field->label = $this->getTosLabel();
			}

			$this->createField($form, $form_field, $customfields ? 'customfields' : 'fields');
		}
	}

	// Подпись согласия со ссылкой на статью с условиями. Если статья недоступна, остаётся текст ссылки без самой ссылки.
	private function getTosLabel()
	{
		$tosLinkText = $this->params->get('toslinktext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TOSLINKTEXT_TITLE'));

		try {
			$article = $this->app->bootComponent('com_content')->getMVCFactory()->createModel('Articles', 'Site', ['ignore_request' => true]);

			$article->setState('filter.article_id', $this->params->get('toslink'));
			$article->setState('filter.published', 1);
			$article->setState('params', $this->app->getParams());
			$article->setState('list.limit', 1);
			$tosArticles = $article->getItems();

			if (!isset($tosArticles[0]) || !is_object($tosArticles[0])) {
				LogHelper::add('The configured Terms of Service article is unavailable.');

				return $tosLinkText;
			}

			$tosArticle = $tosArticles[0];
			$articleSlug = $tosArticle->id . ':' . $tosArticle->alias;
			$tosLink = Route::_(RouteHelper::getArticleRoute($articleSlug, $tosArticle->catid, $tosArticle->language));

			return Text::sprintf('MOD_WEDAL_JOOMLA_CALLBACK_TOSTEXT', $tosLink, $tosLinkText);
		} catch (\Throwable $exception) {
			LogHelper::add('The configured Terms of Service article could not be loaded.');

			return $tosLinkText;
		}
	}

	//Создает дополнительные поля модуля согласно настройкам на вкладке дополнительных полей
	private function createCustomFields($form){
		if (!$this->params->get('enable_customfields', '0')) {
			return false;
		}

		if (!$this->params->get('customfields', '')) {
			return false;
		}

		$custom_xml = '<form><fieldset name="customfields">' .$this->params->get('customfields', ''). '</fieldset></form>';

		$previousUseErrors = libxml_use_internal_errors(true);
		$loaded = false;
		$errors = array();

		try {
			$loaded = (bool) $form->load($custom_xml);
			$errors = libxml_get_errors();
		} catch (\Throwable $exception) {
			$loaded = false;
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors($previousUseErrors);
		}

		if (!$loaded) {
			LogHelper::add(sprintf(
				'The custom fields XML of module %d is invalid, the custom fields were skipped: %s',
				$this->moduleId,
				$this->firstXmlError($errors)
			));

			return false;
		}

		return true;
	}

	/**
	 * Первое сообщение разбора XML в виде, пригодном для журнала: одна строка ограниченной длины, чтобы разметка полей не разрывала запись переводами строк.
	 *
	 * @param   \LibXMLError[]  $errors  Ошибки, накопленные libxml за время загрузки.
	 *
	 * @return  string
	 */
	private function firstXmlError(array $errors)
	{
		if ($errors === array()) {
			return 'no parser message available';
		}

		$message = preg_replace('/\s+/', ' ', trim((string) $errors[0]->message));

		return sprintf('line %d: %s', (int) $errors[0]->line, mb_strimwidth($message, 0, 200, '..'));
	}
}
