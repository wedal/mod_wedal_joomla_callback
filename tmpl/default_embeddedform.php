<?php defined('_JEXEC') or die('Restricted access'); ?>

<div id="WJCForm<?php echo $moduleId ?>" class="wjcallbackform embeddedform <?php echo $moduleclass_sfx ?>" data-id="<?php echo $moduleId ?>" data-itemid="<?php echo $itemid ?>">
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

                <?php if (!empty($formfields->name['show'])) { ?>
        			<div class="inputcont">
        				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME'); ?> <?php echo $formfields->name['req'][0] ?>" value="" class="inputbox <?php echo $formfields->name['req'][1] ?> form-control" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME_ERROR');  ?>" id="WJCForm<?php echo $moduleId ?>_name" name="WJCForm<?php echo $moduleId ?>_name" />
        			</div>
                <?php } ?>

                <?php if (!empty($formfields->phone['show'])) { ?>
        			<div class="inputcont">
        				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE'); ?> <?php echo $formfields->phone['req'][0] ?>" value="" class="inputbox <?php echo $formfields->phone['req'][1] ?> form-control" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE_ERROR');  ?>" id="WJCForm<?php echo $moduleId ?>_phone" name="WJCForm<?php echo $moduleId ?>_phone" />
        			</div>
                <?php } ?>

                <?php if (!empty($formfields->email['show'])) { ?>
                    <div class="inputcont">
                        <input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MAIL'); ?> <?php echo $formfields->email['req'][0] ?>" value="" class="inputbox <?php echo $formfields->email['req'][1] ?> form-control" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_EMAIL_ERROR');  ?>" id="WJCForm<?php echo $moduleId ?>_email" name="WJCForm<?php echo $moduleId ?>_email" />
                    </div>
                <?php } ?>

                <?php if (!empty($formfields->comment['show'])) { ?>
                    <div class="inputcont">
                        <textarea id="WJCForm<?php echo $moduleId ?>_comment"  rows="4" cols="10" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TEXTAREA'); ?> <?php echo $formfields->comment['req'][0] ?>" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MESSAGE_ERROR');  ?>" name="WJCForm<?php echo $moduleId ?>_comment" class="customer-comment <?php echo $formfields->comment['req'][1] ?>"></textarea>
                    </div>
                <?php } ?>

                <?php if (!empty($formfields->tos['show'])) { ?>
                    <div class="inputcont">
                        <label id="WJCForm<?php echo $moduleId ?>_tos" class="tos">
                            <?php if (!empty($formfields->tos['toscheckbox'])) { ?>
                                <input id="WJCForm<?php echo $moduleId ?>_tos_box" type="checkbox" class="tos-box required" name="tos_box" value="1" data-error="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TOS_ERROR');  ?>">
                            <?php } ?>
                            <span><?php echo JText::sprintf('MOD_WEDAL_JOOMLA_CALLBACK_TOSTEXT', $formfields->tos['toslink'], $formfields->tos['toslinktext']); ?></span>
                            <?php if (!empty($formfields->tos['toscheckbox'])) { ?>
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
           $("#WJCForm'.$moduleId.'_phone").mask("'.$params->get('phonemasktype', JText::_("MOD_WEDAL_JOOMLA_CALLBACK_SHOWPHONEMASKTYPE_TITLE")).'");
        });
    </script>
    ';
    }
?>
