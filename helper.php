<?php

defined('_JEXEC') or die;

/**
 * Helper for mod_wedal_joomla_callback
 */
class ModWedalJoomlaCallbackHelper
{

	public static function getParams()
    {
        jimport('joomla.application.module.helper');
        $module = JModuleHelper::getModule('wedal_joomla_callback');

		$params = new JRegistry;
        $params->loadString($module->params);

		$moduleId = $params->get('moduleid', '');

		$formfields = new stdClass();

		$formfields->name['show'] = $params->get('showname', '');
		$formfields->name['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('shownamereq', ''));

		$formfields->email['show'] = $params->get('showemail', '');
		$formfields->email['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('showemailreq', ''));

		$formfields->phone['show'] = $params->get('showphone', '');
		$formfields->phone['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('showphonereq', ''));

		$formfields->comment['show'] = $params->get('showtextarea', '');
		$formfields->comment['req'] = ModWedalJoomlaCallbackHelper::getRequired($params->get('showtextareareq', ''));

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
		$params = ModWedalJoomlaCallbackHelper::getParams();
		$moduleId = $params->get('moduleid', '');
		$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
		$formdesc = $params->get('formdesc', '');
		$formfields = $params->get('formfields', '');
		require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_form');
		return;
	}

	public static function sendFormAjax()
	{

		//Check token
		if (!JSession::checkToken()) {
			echo json_encode(Array('message' => JText::_('MOD_WEDAL_JOOMLA_CALLBACK_INVALID_TOKEN'), 'error' => 1));
			return;
		}

		$params = ModWedalJoomlaCallbackHelper::getParams();
		$moduleId = $params->get('moduleid', '');
		$jinput = JFactory::getApplication()->input;

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
				$mailtitle = JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MAILTITLE_DEFALT');
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
}
