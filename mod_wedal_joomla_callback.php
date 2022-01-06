<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Module\WedalJoomlaCallback\Site\Helper\WedalJoomlaCallbackHelper;

HTMLHelper::_('script','mod_wedal_joomla_callback/wjcallback.js',	['relative' => true], ['defer ' => 'defer']);
HTMLHelper::_('stylesheet','mod_wedal_joomla_callback/wjcallback.css',	['relative' => true], ['defer ' => 'defer']);

// Get params
$buttontext = $params->get('buttontext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_BUTTONTEXT_DEFAULT'), '');
$thankyoutext = $params->get('thankyoutext', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_THANKYOUTEXT'));

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
$formdesc = $params->get('formdesc', '');

if ($params->get('showformtitle', '1')) {
	$formtitle = $params->get('formtitle', Text::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'));
}

$form = new WedalJoomlaCallbackHelper ($params);

$jinput = Factory::getApplication()->input;
$itemid = $jinput->get('Itemid', null, 'int');

if ($params->get('showphonemask') && $form->moduletype == 1) {
	HTMLHelper::_('script','mod_wedal_joomla_callback/wjcallback.js',	['relative' => true], ['defer ' => 'defer']);
}

require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $params->get('layout', 'default') . $form->moduletype ? '_embeddedform' : '' );