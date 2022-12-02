<?php defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseScript('wjcallback', 'mod_wedal_joomla_callback/wjcallback.js', [] ,['defer ' => true]);
$wa->registerAndUseStyle('wjcallback', 'mod_wedal_joomla_callback/wjcallback.css');

if ($params->get('showphonemask')) {
	$wa->registerAndUseScript('maska', 'mod_wedal_joomla_callback/maska.js', [] ,['defer ' => true]);
}
?>

<a data-id="<?php echo $module->id ?>" class="wjcallback-link btn <?php echo $params->get('button_suffix') ?>" href="#"><?php echo $form->buttontext ?></a>

