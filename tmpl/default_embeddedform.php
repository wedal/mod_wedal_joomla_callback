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

<div id="WJCForm<?php echo $form->moduleid ?>" class="wjcallbackform embeddedform <?php echo $form->params->get('wrapper_suffix') ?>" data-id="<?php echo $form->moduleid ?>">
    <div class="wjcallbackform-wrapper message-container">
    	<form method="post" action="<?php JURI::current(); ?>" class="form-validate <?php echo $form->params->get('form_suffix') ?>">

            <?php if (!empty($form->formtitle)) { ?>
                <div class="modal-header">
                    <span class="modal-title"><?php echo $form->formtitle ?></span>
                </div>
            <?php } ?>

    		<div class="modal-body message-container">
                <?php if (!empty($form->formdesc)) { ?>
        			<div class="informtext one-click-desc">
        				<?php echo $form->formdesc; ?>
        			</div>
                <?php } ?>

                <?php //Базовые поля и их переопределения ?>
                <?php foreach ($form->form->getFieldset('fields') as $field) { ?>
			        <?php echo $field->renderField(array('class' => $field->id . ' ' . $form->params->get('fieldwrapper_suffix'))); ?>
                <?php } ?>

			    <?php //Дополнительные поля ?>
			    <?php foreach ($form->form->getFieldset('customfields') as $field) { ?>
				    <?php echo $field->renderField(array('class' => $field->id . ' ' . $form->params->get('fieldwrapper_suffix'))); ?>
			    <?php } ?>
    		</div>

    		<div class="modal-footer">
                <?php echo JHtml::_( 'form.token' ); ?>
    			<button class="btn <?php echo $form->params->get('submit_suffix') ?>" type="submit"><?php echo Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND'); ?></button>
    		</div>

    	</form>
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
