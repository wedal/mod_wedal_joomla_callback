<?php
/*
Информация о том, как работать с полями в макете:
- https://api.joomla.org/cms-3/classes/Joomla.CMS.Form.Form.html
- https://docs.joomla.org/Basic_form_guide
- https://docs.joomla.org/Advanced_form_guide

Макеты разметки полей находятся в каталоге:
/layouts/joomla/form
и могут быть переопределены в ваш шаблон
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>

<div id="WJCForm<?php echo $form->moduleid ?>" class="wjcallbackform <?php echo $form->moduleclass_sfx ?>" role="dialog" data-id="<?php echo $form->moduleid ?>">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        	<form method="post" name="WJCForm<?php echo $form->moduleid ?>" action="<?php JURI::current(); ?>" class="form-validate">

        		<div class="modal-header">
                    <?php if (!empty($form->formtitle)) { ?>
                        <div class="form-header">
                            <h2 class="modal-title"><?php echo $form->formtitle ?></h2>
                        </div>
                    <?php } ?>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">&times;</button>
        		</div>

        		<div class="modal-body">

                    <?php if (!empty($form->formdesc)) { ?>
            			<div class="informtext one-click-desc">
            				<?php echo $form->formdesc; ?>
            			</div>
                    <?php } ?>

			        <?php //Базовые поля и их переопределения ?>
			        <?php foreach ($form->form->getFieldset('fields') as $field) { ?>
				        <?php echo $field->renderField(array('class' => $field->id)); ?>
			        <?php } ?>

			        <?php //Дополнительные поля ?>
			        <?php foreach ($form->form->getFieldset('customfields') as $field) { ?>
				        <?php echo $field->renderField(array('class' => $field->id)); ?>
			        <?php } ?>

        		</div>

        		<div class="modal-footer">
                    <?php echo JHtml::_( 'form.token' ); ?>
        			<button class="btn btn-primary" type="submit"><?php echo Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND'); ?></button>
        		</div>
        	</form>
        </div>
    </div>
</div>

<?php if (!empty($form->params->get('showphonemask'))) {
    echo '
    <script type="text/javascript">
        jQuery(document).ready(function($) {
           var mask = Maska.create("#WJCForm'.$form->moduleid.' #phone", {
              mask: "'.$form->params->get('phonemasktype', Text::_("MOD_WEDAL_JOOMLA_CALLBACK_SHOWPHONEMASKTYPE_TITLE")).'"
           });
        });
    </script>
    ';
    }
?>