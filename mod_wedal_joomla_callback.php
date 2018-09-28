<?php

defined('_JEXEC') or die;

$doc = JFactory::getDocument();

JHtml::_('jquery.framework');

$doc->addScript('/modules/'.$module->module.'/assets/js/wjcallback.js');
$doc->addStyleSheet('/modules/'.$module->module.'/assets/css/wjcallback.css');

JLoader::register('ModWedalJoomlaCallbackHelper', __DIR__ . '/helper.php');

// Get params
$moduleId = $module->id;

$buttontext = $params->get('buttontext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_BUTTONTEXT_DEFALT'));

$showname = $params->get('showname', '');
$shownamereq = $params->get('shownamereq', '');

$showemail = $params->get('showemail', '');
$showemailreq = $params->get('showemailreq', '');

$showphone = $params->get('showphone', '');
$showphonereq = $params->get('showphonereq', '');

$showtextarea = $params->get('showtextarea', '');
$showtextareareq = $params->get('showtextareareq', '');

$thankyoutext = $params->get('thankyoutext', JText::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT'));

$showtextarea = $params->get('showtextarea', '');

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

//$images = ModWedalJoomlaCallbackHelper::getImages($params);


require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default'));
require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . '_form');
