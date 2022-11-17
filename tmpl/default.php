<?php defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('jquery');
$wa->registerAndUseScript('wjcallback', 'mod_wedal_joomla_callback/wjcallback.js', [] ,['defer ' => true]);
$wa->registerAndUseStyle('wjcallback', 'mod_wedal_joomla_callback/wjcallback.css');

//if ($params->get('showphonemask') && $form->moduletype == 1) {
	$wa->registerAndUseScript('maska', 'mod_wedal_joomla_callback/maska.js', [] ,['defer ' => true]);
//}

?>

<div id="WJC<?php echo $module->id ?>" data-id="<?php echo $module->id ?>" data-itemid="<?php echo $form->itemid ?>" class="wjcallback <?php echo $form->moduleclass_sfx ?>">
    <a class="wjcallback-link" href="#"><?php echo $form->buttontext ?></a>
</div>
