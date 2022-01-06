<?php defined('_JEXEC') or die('Restricted access'); ?>

<div id="WJCForm<?php echo $module->id ?>" class="wjcallbackform embeddedform <?php echo $moduleclass_sfx ?>" data-id="<?php echo $module->id ?>" data-itemid="<?php echo $itemid ?>">
    <div class="wjcallbackform-wrapper message-container">
    	<form method="post" action="<?php JURI::current(); ?>" class="form-validate">

            <?php if (!empty($formtitle)) { ?>
                <div class="modal-header">
                    <h2 class="form-title"><?php echo $formtitle ?></h2>
                </div>
            <?php } ?>

    		<div class="modal-body message-container">

                <?php if (!empty($formdesc)) { ?>
        			<div class="informtext one-click-desc">
        				<?php echo $formdesc; ?>
        			</div>
                <?php } ?>

                <?php if (!empty($form->formfields->name['show'])) { ?>
        			<div class="inputcont">
        				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME'); ?> <?php echo $form->formfields->name['req'][0] ?>" value="" class="inputbox <?php echo $form->formfields->name['req'][1] ?> form-control" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME_ERROR');  ?>" id="WJCForm<?php echo $module->id ?>_name" name="WJCForm<?php echo $module->id ?>_name" />
        			</div>
                <?php } ?>

                <?php if (!empty($form->formfields->phone['show'])) { ?>
        			<div class="inputcont">
        				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE'); ?> <?php echo $form->formfields->phone['req'][0] ?>" value="" class="inputbox <?php echo $form->formfields->phone['req'][1] ?> form-control" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE_ERROR');  ?>" id="WJCForm<?php echo $module->id ?>_phone" name="WJCForm<?php echo $module->id ?>_phone" />
        			</div>
                <?php } ?>

                <?php if (!empty($form->formfields->email['show'])) { ?>
                    <div class="inputcont">
                        <input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MAIL'); ?> <?php echo $form->formfields->email['req'][0] ?>" value="" class="inputbox <?php echo $form->formfields->email['req'][1] ?> form-control" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_EMAIL_ERROR');  ?>" id="WJCForm<?php echo $module->id ?>_email" name="WJCForm<?php echo $module->id ?>_email" />
                    </div>
                <?php } ?>

                <?php if (!empty($form->formfields->comment['show'])) { ?>
                    <div class="inputcont">
                        <textarea id="WJCForm<?php echo $module->id ?>_comment"  rows="4" cols="10" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TEXTAREA'); ?> <?php echo $form->formfields->comment['req'][0] ?>" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MESSAGE_ERROR');  ?>" name="WJCForm<?php echo $module->id ?>_comment" class="customer-comment <?php echo $form->formfields->comment['req'][1] ?>"></textarea>
                    </div>
                <?php } ?>

                <?php if (!empty($form->formfields->tos['show'])) { ?>
                    <div class="inputcont">
                        <label id="WJCForm<?php echo $module->id ?>_tos" class="tos">
                            <?php if (!empty($form->formfields->tos['toscheckbox'])) { ?>
                                <input id="WJCForm<?php echo $module->id ?>_tos_box" type="checkbox" class="tos-box required" name="tos_box" value="1" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TOS_ERROR');  ?>">
                            <?php } ?>
                            <span><?php echo JText::sprintf('MOD_WEDAL_JOOMLA_CALLBACK_TOSTEXT', $form->formfields->tos['toslink'], $form->formfields->tos['toslinktext']); ?></span>
                            <?php if (!empty($form->formfields->tos['toscheckbox'])) { ?>
                                <span>*</span>
                            <?php } ?>
                        </label>
                    </div>
                <?php } ?>

    		</div>

    		<div class="modal-footer">
                <?php echo JHtml::_( 'form.token' ); ?>
    			<button class="btn" type="submit"><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND'); ?></button>
    		</div>

    	</form>
    </div>
</div>

<?php if (!empty($params->get('showphonemask'))) {
    echo '
    <script type="text/javascript">
        jQuery(document).ready(function($) {
           $("#WJCForm'.$module->id.'_phone").mask("'.$params->get('phonemasktype', JText::_("MOD_WEDAL_JOOMLA_CALLBACK_SHOWPHONEMASKTYPE_TITLE")).'");
        });
    </script>
    ';
    }
/**
 * Copyright (С) Wedal web workshop <https://wedal.ru
 */

?>
