<?php

defined('_JEXEC') or die;

/*
@// TODO:
5) English translation
*/


$doc = JFactory::getDocument();

JHtml::_('jquery.framework');

$doc->addScript('/modules/'.$module->module.'/assets/js/wjcallback.js');
$doc->addStyleSheet('/modules/'.$module->module.'/assets/css/wjcallback.css');

JLoader::register('ModWedalJoomlaCallbackHelper', __DIR__ . '/helper.php');

// Get params
$moduleId = $module->id;
$buttontext = $params->get('buttontext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_BUTTONTEXT_DEFALT'));
$thankyoutext = $params->get('thankyoutext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT'));
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default'));
