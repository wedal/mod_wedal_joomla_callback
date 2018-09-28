<?php

defined('_JEXEC') or die;

$doc = JFactory::getDocument();

JHtml::_('jquery.framework');

$doc->addScript('/modules/'.$module->module.'/assets/js/wjcallback.js');
$doc->addStyleSheet('/modules/'.$module->module.'/assets/css/wjcallback.css');

JLoader::register('ModWedalJoomlaCallbackHelper', __DIR__ . '/helper.php');

$moduleId = $module->id;
//$moduleWidth = $params->get('width', '');

//for ($i = 1; $i < 10; $i++) {
//    $num_array[$i] = $params->get('article_id_'.$i);
//}

//
//$images = ModWedalJoomlaCallbackHelper::getImages($params);

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
require JModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default'));
