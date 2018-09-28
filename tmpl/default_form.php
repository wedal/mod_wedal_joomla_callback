<?php defined('_JEXEC') or die('Restricted access'); ?>

<div id="WJCForm<?php echo $moduleId ?>" class="wjcallbackform modal fade <?php echo $moduleclass_sfx ?>" role="dialog">

    <div class="modal-dialog" role="document">

        <div class="modal-content">

        	<form method="post" action="<?php JURI::current(); ?>" class="form-validate">

        		<div class="modal-header">
        			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        			<h2 class="modal-title" id="myModalLabel"><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_TITLE'); ?></h2>
        		</div>

        		<div class="modal-body">

        			<div class="informtext one-click-desc">
        				<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_DESC'); ?>
        			</div>

        			<div class="inputcont">
        				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_NAME'); ?>" value="" class="inputbox required form-control" id="WJCForm<?php echo $moduleId ?>_name" name="WJCForm<?php echo $moduleId ?>_name" />
        			</div>

        			<div class="inputcont">
        				<input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_PHONE'); ?>" value="" class="inputbox required form-control" id="WJCForm<?php echo $moduleId ?>_phone" name="WJCForm<?php echo $moduleId ?>_phone" />
        			</div>

                    <div class="inputcont">
                        <input type="text" placeholder="<?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_EMAIL'); ?>" value="" class="inputbox required form-control" id="WJCForm<?php echo $moduleId ?>_email" name="WJCForm<?php echo $moduleId ?>_email" />
                    </div>

        			<div class="inputcont">
        				<input type="text"  value="" class="inputbox required form-control" id="WJCForm<?php echo $moduleId ?>_antispam" name="WJCForm<?php echo $moduleId ?>_antispam" />
        			</div>

        		</div>

        		<div class="modal-footer">
        			<button class="btn" type="submit"><?php echo JText::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND'); ?></button>
        		</div>

        	</form>

        </div>

    </div>

</div>
