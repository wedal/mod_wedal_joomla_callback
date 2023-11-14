<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php if (!$params->get('hideformbutton')) { ?>
    <a data-id="<?php echo $module->id ?>" class="wjcallback-link <?php echo $params->get('button_suffix') ?>" href="#" <?php echo $params->get('ym_popup') ? 'data-ym-aimid="'.$params->get('ym_popup'). '"' : '' ?>><?php echo $form->buttontext ?></a>
<?php } ?>
