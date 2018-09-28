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
        return $moduleParams;
    }

	public static function getFormAjax()
	{
		///index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm
		$params = ModWedalJoomlaCallbackHelper::getParams();
		require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_form');
	    return;
	}
}
