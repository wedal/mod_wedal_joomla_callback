<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\Module\WedalJoomlaCallback\Site\Helper\WedalJoomlaCallbackHelper;

$buttonSuffix = WedalJoomlaCallbackHelper::escapeAttribute($params->get('button_suffix'));
$ymPopup = $params->get('ym_popup')
	? WedalJoomlaCallbackHelper::escapeAttribute($params->get('ym_popup'))
	: '';
$gaPopup = $params->get('ga_popup')
	? WedalJoomlaCallbackHelper::escapeAttribute($params->get('ga_popup'))
	: '';
?>

<?php if (!$params->get('hideformbutton')) { ?>
    <a data-id="<?php echo (int) $module->id ?>" class="wjcallback-link <?php echo $buttonSuffix ?>" href="#" <?php echo $ymPopup !== '' ? 'data-ym-aimid="' . $ymPopup . '"' : '' ?> <?php echo $gaPopup !== '' ? 'data-ga-event="' . $gaPopup . '"' : '' ?>><?php echo $form->buttontext ?></a>
<?php } ?>
