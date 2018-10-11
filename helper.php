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

	public static function getFormAjax()
	{
		$params = ModWedalJoomlaCallbackHelper::getParams();
		$moduleId = $params->get('moduleid', '');
		$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
		$formfields = $params->get('formfields', '');


		require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_form');
		return;
	}

	public static function sendFormAjax()
	{
		$params = ModWedalJoomlaCallbackHelper::getParams();
		$moduleId = $params->get('moduleid', '');
		$jinput = JFactory::getApplication()->input;

		$formfields = $params->get('formfields', '');

		foreach ($formfields as $key => &$formfield) {
			if ($formfield['show']) {
				$formfield['value'] = $jinput->get('WJCForm'.$moduleId.'_'.$key, '', 'STRING');
			}
		}

		$config = & JFactory::getConfig();

	//	if(isset($input_name)){

			ob_start();
			htmlspecialchars(require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_message'), ENT_QUOTES);
			$body = ob_get_contents();
			ob_end_clean();

/*
			$to = $config->get('mailfrom');
			$from = array($config->get('mailfrom') , $config->get('fromname') );
			$subject = "Поступил запрос обратного звонка!";

			$mailer = JFactory::getMailer();
			$mailer->setSender($from);
			$mailer->addRecipient($to);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->isHTML();
			$mailer->send();
*/
	//	}

	    return;
	}
}
