<?php

defined('_JEXEC') or die;

$doc = JFactory::getDocument();
JHtml::_('jquery.framework');

$doc->addScript('/modules/'.$module->module.'/assets/js/wjcallback.js');
$doc->addStyleSheet('/modules/'.$module->module.'/assets/css/wjcallback.css');

JLoader::register('ModWedalJoomlaCallbackHelper', __DIR__ . '/helper.php');

// Get params
$moduleId = $module->id;
$buttontext = $params->get('buttontext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_BUTTONTEXT_DEFAULT'));
$thankyoutext = $params->get('thankyoutext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT'));
$moduletype = $params->get('moduletype', 0);
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
$params = ModWedalJoomlaCallbackHelper::getParams($moduleId);
$formfields = $params->get('formfields');
$formdesc = $params->get('formdesc', '');
if ($params->get('showformtitle', '1')) {
    $formtitle = $params->get('formtitle', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'));
}
$formdesc = $params->get('formdesc', '');

$jinput = JFactory::getApplication()->input;
$itemid = $jinput->get('Itemid', null, 'int');

if ($moduletype == 1) {
    require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_embeddedform');
} else {
    require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default'));
}
