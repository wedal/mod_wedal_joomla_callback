<?php defined('_JEXEC') or die('Restricted access'); ?>

<div class="message">

    <?php if ($formfields->name['show']) { ?>
        <div>
            <span><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME'); ?></span>: <span><?php echo $formfields->name['value']; ?></span>
        </div>
    <?php } ?>

    <?php if ($formfields->email['show']) { ?>
        <div>
            <span><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MAIL'); ?></span>: <span><?php echo $formfields->email['value']; ?></span>
        </div>
    <?php } ?>

    <?php if ($formfields->phone['show']) { ?>
        <div>
            <span><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE'); ?></span>: <span><?php echo $formfields->phone['value']; ?></span>
        </div>
    <?php } ?>

    <?php if ($formfields->comment['show']) { ?>
        <div>
            <span><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TEXTAREA'); ?></span>: <span><?php echo $formfields->comment['value']; ?></span>
        </div>
    <?php } ?>

    <?php if ($page_url) { ?>
        <div><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND_FROM_URL').$page_url ?></div>
    <?php } ?>
</div>
