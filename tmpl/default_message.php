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
            <strong><?php echo htmlspecialchars((string) $form->form->getFieldAttribute($key, 'label'), ENT_QUOTES, 'UTF-8'); ?></strong>: <span><?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
	<?php } ?>

    <?php if (!empty($page_url)) { ?>
		<div><?php echo htmlspecialchars(Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND_FROM_URL') . $page_url, ENT_QUOTES, 'UTF-8'); ?></div>
	<?php } ?>

	<?php if ($form->params->get('show_smsinfo_in_mail') && !empty($sms_status)) { ?>
		<div><?php echo htmlspecialchars((string) $sms_status, ENT_QUOTES, 'UTF-8'); ?></div>
	<?php } ?>

</div>
