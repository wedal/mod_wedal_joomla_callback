<?php

defined('_JEXEC') or die;

/**
 * Helper for mod_wedal_joomla_callback
 */
class ModWedalJoomlaCallbackHelper
{

	public static function getParams($moduleId)
    {
        //jimport('joomla.application.module.helper');
        //$module = JModuleHelper::getModuleById($moduleId); //--->> It can be use for Joomla 3.9.0+
		$module = ModWedalJoomlaCallbackHelper::getModuleById($moduleId); // It use for Joomla < 3.9.0 for legacy reason

		$params = new JRegistry;
        $params->loadString($module->params);

		$formfields = new stdClass();

		$formfields->name['show'] = $params->get('showname', '');
		$formfields->name['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('shownamereq', ''));

		$formfields->email['show'] = $params->get('showemail', '');
		$formfields->email['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('showemailreq', ''));

		$formfields->phone['show'] = $params->get('showphone', '');
		$formfields->phone['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('showphonereq', ''));

		$formfields->comment['show'] = $params->get('showtextarea', '');
		$formfields->comment['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('showtextareareq', ''));

		$formfields->tos['show'] = $params->get('showtos', '');
		$formfields->tos['toslink'] = $params->get('toslink', '#');

		if ($formfields->tos['show'] && $formfields->tos['toslink'] != '#') {
			JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
 			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');
			$article = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));
			$article->setState('article.id', $formfields->tos['toslink']);
			$article->setState('filter.published', 1);
			$article->setState('params', jFactory::getApplication()->getParams());
			$tos_article   = $article->getItem();
			$formfields->tos['toslink'] =  JRoute::_(ContentHelperRoute::getArticleRoute($formfields->tos['toslink'], $tos_article->catid, $tos_article->language));
		}

		$formfields->tos['toslinktext'] = $params->get('toslinktext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TOSLINKTEXT_TITLE'));
		$formfields->tos['toscheckbox'] = $params->get('toscheckbox', '');

		$params->set('moduleid', $module->id);
		$params->set('formfields', $formfields);

        return $params;
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

	public static function checkRequired($formfields)
	{
		$checked = true;
		foreach ($formfields as $formfield) {
			if ($formfield['show'] && $formfield['req'] && !$formfield['value']) {
				$checked = false;
			}
		}

		return $checked;
	}

	public static function getFormAjax()
	{
		$jinput = JFactory::getApplication()->input;
		$moduleId = $jinput->get('modid', null, 'int');
		$itemid = $jinput->get('Itemid', null, 'int');
		$params = ModWedalJoomlaCallbackHelper::getParams($moduleId);

		$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
		$formdesc = $params->get('formdesc', '');
		if ($params->get('showformtitle', '1')) {
		    $formtitle = $params->get('formtitle', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'));
		}
		$formfields = $params->get('formfields', '');
		require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_popupform');
		return;
	}

	public static function sendFormAjax()
	{

		//Check token
		if (!JSession::checkToken()) {
			echo json_encode(Array('message' => JText::_('MOD_WEDAL_JOOMLA_CALLBACK_INVALID_TOKEN'), 'error' => 1));
			return;
		}

		$jinput = JFactory::getApplication()->input;
		$moduleId = $jinput->get('modid', null, 'int');
		$params = ModWedalJoomlaCallbackHelper::getParams($moduleId);

		$formfields = $params->get('formfields', '');

		//Check required fields
		foreach ($formfields as $key => &$formfield) {
			if ($formfield['show']) {
				$formfield['value'] = $jinput->get('WJCForm'.$moduleId.'_'.$key, '', 'STRING');
			}
		}

		$checked = ModWedalJoomlaCallbackHelper::checkRequired($formfields);

		if ($checked) {
			$config = & JFactory::getConfig();

			$mailtitle = $params->get('mailtitle', '');
			if (!$mailtitle) {
				$mailtitle = JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MAILTITLE_DEFAULT');
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
				$thankyoutext = JText::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT');
			}


			ob_start();
			htmlspecialchars(require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_message'), ENT_QUOTES);
			$body = ob_get_contents();
			ob_end_clean();

			$to = $email;
			$from = array($config->get('mailfrom') , $config->get('fromname') );
			$subject = $mailtitle;

			$mailer = JFactory::getMailer();
			$mailer->setSender($from);
			$mailer->addReplyTo($from);
			$mailer->addRecipient($to);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->isHTML();
			$mailer->send();

			echo json_encode(Array('message' => $thankyoutext, 'error' => 0));

		} else {
			echo json_encode(Array('message' => JText::_('MOD_WEDAL_JOOMLA_CALLBACK_VALIDATION_ERROR'), 'error' => 1));
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
		$modules = JModuleHelper::getModuleList();

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
