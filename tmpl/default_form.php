<?php defined('_JEXEC') or die('Restricted access'); ?>

<div id="WJCForm<?php echo $moduleId ?>" class="wjcallbackform <?php echo $moduleclass_sfx ?>" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        	<form method="post" action="<?php JURI::current(); ?>" class="form-validate">

        		<div class="modal-header">
        			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        			<h2 class="modal-title"><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'); ?></h2>
        		</div>

        		<div class="modal-body">

        			<div class="informtext one-click-desc">
        				<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_DESC'); ?>
        			</div>

                    <?php if ($showname) { ?>
            			<div class="inputcont">
            				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME'); ?> <?php echo $shownamereq[0] ?>" value="" class="inputbox <?php echo $shownamereq[1] ?> form-control" id="WJCForm<?php echo $moduleId ?>_name" name="WJCForm<?php echo $moduleId ?>_name" />
            			</div>
                    <?php } ?>

                    <?php if ($showphone) { ?>
            			<div class="inputcont">
            				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE'); ?> <?php echo $showphonereq[0] ?>" value="" class="inputbox <?php echo $showphonereq[1] ?> form-control" id="WJCForm<?php echo $moduleId ?>_phone" name="WJCForm<?php echo $moduleId ?>_phone" />
            			</div>
                    <?php } ?>

                    <?php if ($showemail) { ?>
                        <div class="inputcont">
                            <input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_MAIL'); ?> <?php echo $showemailreq[0] ?>" value="" class="inputbox <?php echo $showemailreq[1] ?> form-control" id="WJCForm<?php echo $moduleId ?>_email" name="WJCForm<?php echo $moduleId ?>_email" />
                        </div>
                    <?php } ?>

                    <?php if ($showtextarea) { ?>
                        <div class="inputcont">
                            <textarea id="WJCForm<?php echo $moduleId ?>_comment"  rows="4" cols="10" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TEXTAREA'); ?> <?php echo $showtextareareq[0] ?>" name="WJCForm<?php echo $moduleId ?>_comment" class="customer-comment <?php echo $showtextareareq[1] ?>"></textarea>
                        </div>
                    <?php } ?>

        		</div>

        		<div class="modal-footer">
        			<button class="btn" type="submit"><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND'); ?></button>
        		</div>

        	</form>
        </div>
    </div>
</div>
