<?php 

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>

<div class="message">
	<?php foreach ($form->values as $key => $value) { ?>
        <?php if (is_array($value)) {
			$value = implode(', ', $value);
        } ?>

        <div>
            <strong><?php echo $form->form->getFieldAttribute($key, 'label'); ?></strong>: <span><?php echo $value; ?></span>
        </div>
	<?php } ?>

    <?php if (!empty($page_url)) { ?>
        <div><?php echo Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND_FROM_URL').$page_url ?></div>
    <?php } ?>
</div>
