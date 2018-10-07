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

        $moduleParams = new JRegistry;
        $moduleParams->loadString($module->params);
		$moduleParams->set('moduleid', $module->id);
        return $moduleParams;
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

		$showname = $params->get('showname', '');
		$shownamereq = ModWedalJoomlaCallbackHelper::getRequired($params->get('shownamereq', ''));

		$showemail = $params->get('showemail', '');
		$showemailreq = ModWedalJoomlaCallbackHelper::getRequired($params->get('showemailreq', ''));

		$showphone = $params->get('showphone', '');
		$showphonereq = ModWedalJoomlaCallbackHelper::getRequired($params->get('showphonereq', ''));

		$showtextarea = $params->get('showtextarea', '');
		$showtextareareq = ModWedalJoomlaCallbackHelper::getRequired($params->get('showtextareareq', ''));

		require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_form');
	    return;
	}
}
