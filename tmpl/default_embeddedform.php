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
use Joomla\Module\WedalJoomlaCallback\Site\Helper\WedalJoomlaCallbackHelper;

$moduleid = (int) $form->moduleid;

$wrapperSuffix = WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('wrapper_suffix'));
$formSuffix = WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('form_suffix'));
$submitSuffix = WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('submit_suffix'));
$fieldwrapperSuffix = WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('fieldwrapper_suffix'));
$ymSubmit = $form->params->get('ym_submit')
	? WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('ym_submit'))
	: '';
$gaSubmit = $form->params->get('ga_submit')
	? WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('ga_submit'))
	: '';

$phonemask = $form->params->get('showphonemask')
	? WedalJoomlaCallbackHelper::escapeAttribute($form->params->get('phonemasktype', Text::_("MOD_WEDAL_JOOMLA_CALLBACK_SHOWPHONEMASKTYPE_TITLE")))
	: '';
?>

<div id="WJCForm<?php echo $moduleid ?>" class="wjcallbackform embeddedform <?php echo $wrapperSuffix ?>" data-id="<?php echo $moduleid ?>"<?php echo $phonemask !== '' ? ' data-phonemask="' . $phonemask . '"' : '' ?>>
    <div class="wjcallbackform-wrapper message-container">
        	<form method="post" name="WJCForm<?php echo $moduleid ?>" class="form-validate <?php echo $formSuffix ?>" enctype="multipart/form-data" <?php echo $ymSubmit !== '' ? 'data-ym-aimid="' . $ymSubmit . '"' : '' ?> <?php echo $gaSubmit !== '' ? 'data-ga-event="' . $gaSubmit . '"' : '' ?>>
    			<input type="text" name="wjcallback_website" value="" autocomplete="off" tabindex="-1" aria-hidden="true" class="wjcallback-honeypot">

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
			        <?php echo $field->renderField(array('class' => WedalJoomlaCallbackHelper::fieldWrapperClass($field) . ' ' . $fieldwrapperSuffix)); ?>
                <?php } ?>

			    <?php //Дополнительные поля ?>
			    <?php foreach ($form->form->getFieldset('customfields') as $field) { ?>
				    <?php echo $field->renderField(array('class' => WedalJoomlaCallbackHelper::fieldWrapperClass($field) . ' ' . $fieldwrapperSuffix)); ?>
			    <?php } ?>
    		</div>

    		<div class="modal-footer">
                <?php //Токен не выводится: страница может отдаваться из кэша. Его подставляет wjcallback.js живым запросом. ?>
                <button class="btn <?php echo $submitSuffix ?>" type="submit"><?php echo $form->params->get('send_buttontext', Text::_("MOD_WEDAL_JOOMLA_CALLBACK_SEND")) ?></button>
    		</div>

    	</form>
    </div>
</div>
