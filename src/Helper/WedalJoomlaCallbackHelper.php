<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
/**
 * Helper for mod_wedal_joomla_callback
 */
class WedalJoomlaCallbackHelper
{

	function __construct(&$params)
    {
        //jimport('joomla.application.module.helper');
        //$module = JModuleHelper::getModuleById($moduleId); //--->> It can be use for Joomla 3.9.0+
	    //$this->module = WedalJoomlaCallbackHelper::getModuleById($moduleId); // It use for Joomla < 3.9.0 for legacy reason

	    //$this->params = new Registry;
	   // $this->params->loadString($this->module->params);

	    $this->moduletype = $params->get('moduletype', 0);

		$this->formfields = new \stdClass();

	    $this->formfields->name['show'] = $params->get('showname', '');
	    $this->formfields->name['req'] = WedalJoomlaCallbackHelper::getRequired($params->get('shownamereq', ''));

	    $this->formfields->email['show'] = $params->get('showemail', '');
	    $this->formfields->email['req'] = WedalJoomlaCallbackHelper::getRequired($params->get('showemailreq', ''));

	    $this->formfields->phone['show'] = $params->get('showphone', '');
	    $this->formfields->phone['req'] = WedalJoomlaCallbackHelper::getRequired($params->get('showphonereq', ''));

	    $this->formfields->comment['show'] = $params->get('showtextarea', '');
	    $this->formfields->comment['req'] = WedalJoomlaCallbackHelper::getRequired($params->get('showtextareareq', ''));

	    $this->formfields->tos['show'] = $params->get('showtos', '');
	    $this->formfields->tos['toslink'] = $params->get('toslink', '#');

		if ($this->formfields->tos['show'] && $this->formfields->tos['toslink'] != '#') {
			JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
 			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');
			$article = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));
			$article->setState('article.id', $this->formfields->tos['toslink']);
			$article->setState('filter.published', 1);
			$article->setState('params', Factory::getApplication()->getParams());
			$tos_article   = $article->getItem();
			$this->formfields->tos['toslink'] =  JRoute::_(ContentHelperRoute::getArticleRoute($this->formfields->tos['toslink'], $tos_article->catid, $tos_article->language));
		}

	    $this->formfields->tos['toslinktext'] = $params->get('toslinktext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TOSLINKTEXT_TITLE'));
	    $this->formfields->tos['toscheckbox'] = $params->get('toscheckbox', '');

		$params->set('moduleid', $module->id);
		$params->set('formfields', $this->formfields);

    }

	public static function getRequired($param)
	{

		if($param) {
			$req = array();
			$req[0] = '*';
			$req[1] = 'required';
			return $req;
		}

	}

	public function checkRequired()
	{
		$checked = true;
		foreach ($this->formfields as $formfield) {
			if ($formfield['show'] && $formfield['req'] && !$formfield['value']) {
				$checked = false;
			}
		}

		return $checked;
	}

	public static function getFormAjax()
	{
		$jinput = Factory::getApplication()->input;
		$moduleId = $jinput->get('modid', null, 'int');
		$itemid = $jinput->get('Itemid', null, 'int');
		$params = WedalJoomlaCallbackHelper::getParams($moduleId);

		$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
		$formdesc = $params->get('formdesc', '');
		if ($params->get('showformtitle', '1')) {
		    $formtitle = $params->get('formtitle', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'));
		}
		$this->formfields = $params->get('formfields', '');
		require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_popupform');
		return;
	}

	public static function sendFormAjax()
	{

		//Check token
		if (!Session::checkToken()) {
			echo json_encode(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_INVALID_TOKEN'), 'error' => 1));
			return;
		}

		$jinput = Factory::getApplication()->input;
		$moduleId = $jinput->get('modid', null, 'int');
		$page_url = urldecode($jinput->get('page', null, 'STRING'));

		$params = WedalJoomlaCallbackHelper::getParams($moduleId);

		$this->formfields = $params->get('formfields', '');

		//Check required fields
		foreach ($this->formfields as $key => &$formfield) {
			if ($formfield['show']) {
				$formfield['value'] = $jinput->get('WJCForm'.$moduleId.'_'.$key, '', 'STRING');
			}
		}

		$checked = $this->checkRequired();

		if ($checked) {
			$config = Factory::getConfig();

			$mailtitle = $params->get('mailtitle', '');
			if (!$mailtitle) {
				$mailtitle = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_MAILTITLE_DEFAULT');
			}

			$email =  $params->get('email', '');
			if (!$email) {
				$email = $config->get('mailfrom');
			}

			$email =  $params->get('email', '');
			if (!$email) {
				$email = $config->get('mailfrom');
			}

			$thankyoutext = $params->get('thankyoutext', '');
			if (!$thankyoutext) {
				$thankyoutext = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT');
			}


			ob_start();
			htmlspecialchars(require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_message'), ENT_QUOTES);
			$body = ob_get_contents();
			ob_end_clean();

			$to = $email;
			$from = array($config->get('mailfrom') , $config->get('fromname') );
			$subject = $mailtitle;

			$mailer = Factory::getMailer();
			$mailer->setSender($from);

			if ($this->formfields->email['value']) {
				$mailer->addReplyTo($this->formfields->email['value']);
			}

			$mailer->addRecipient($to);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->isHTML();
			$mailer->send();

			echo json_encode(Array('message' => $thankyoutext, 'error' => 0));

		} else {
			echo json_encode(Array('message' => Text::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR'), 'error' => 1));
		}

	    return;
	}

	/**
	 * Get module by id
	 //----------> Use it for Joomla < 3.9.0
	 */
	public static function &getModuleById($id)
	{
		jimport('joomla.application.module.helper');
		$modules = ModuleHelper::getModuleList();

		foreach ($modules as $module)
		{
			if ($module->id == $id)
			{
				return $module;
			}
		}

		return false;
	}
}
