<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * SMS-уведомление о заявке: текст из вступления и выбранных полей, отправка через
 * клиент SmsRu и строка о доставке для письма.
 */
final class SmsHelper
{
	private const PARTNER_ID = '410554';

	/** @var WedalJoomlaCallbackHelper Форма с параметрами модуля и значениями полей. */
	private $form;

	/**
	 * @param   WedalJoomlaCallbackHelper  $form  Форма с параметрами модуля и значениями полей.
	 */
	public function __construct($form)
	{
		$this->form = $form;
	}

	/**
	 * Отправляет уведомление о заявке через SMS.ru.
	 *
	 * @return  string|false  Строка о доставке для письма либо false, если сообщение не отправлено.
	 */
	public function send()
	{
		$params = $this->form->params;

		if (!$params->get('sms_api_key') || !$params->get('sms_recipient_number')) {
			return false;
		}

		$sms = new SmsRu($params->get('sms_api_key'));

		$smsdata = array(
			'to' => $params->get('sms_recipient_number'),
			'msg' => $this->buildMessage(),
			'partner_id' => self::PARTNER_ID,
		);

		if ($params->get('sms_transliterate')) {
			$smsdata['translit'] = 1;
		}

		$sms_response = $sms->send($smsdata);

		if (!$sms_response->isSuccessful()) {
			LogHelper::add(sprintf(
				'The SMS notification was not sent: %s (sms.ru code %d).',
				$sms_response->getStatusText(),
				$sms_response->getStatusCode()
			));

			return false;
		}

		return Text::sprintf(
			'MOD_WEDAL_JOOMLA_CALLBACK_SMS_SEND_SUCCESS',
			implode(', ', $sms_response->getSmsIds()),
			$sms_response->getBalance()
		);
	}

	// Собирает текст сообщения: вступление и значения выбранных полей в пределах заданной длины.
	private function buildMessage()
	{
		$params = $this->form->params;
		$sms_message = '';

		if ($params->get('sms_introtext')) {
			$sms_message .= $params->get('sms_introtext');
		}

		$sms_send_fields = $params->get('sms_send_fields');

		if (!empty($sms_send_fields)) {
			$sms_send_fields_array = explode(',', str_replace(' ', '', $sms_send_fields));
			$sms_send_fields_limit = $params->get('sms_send_fields_limit', 100);
			$sms_message_field_values = array();

			foreach ($sms_send_fields_array as $sms_send_field) {
				if (!empty($this->form->values[$sms_send_field]))	{
					if (is_array($this->form->values[$sms_send_field])) {
						$sms_send_field_value = implode(', ', $this->form->values[$sms_send_field]);
					} else {
						$sms_send_field_value = (string) $this->form->values[$sms_send_field];
					}
					$sms_message_field_values[] = mb_strimwidth($sms_send_field_value, 0, $sms_send_fields_limit, '..');
				}
			}

			$sms_message .= implode(',', $sms_message_field_values);
			$sms_message = mb_strimwidth($sms_message, 0, $params->get('sms_send_fields_total_limit', 450), '');
		}

		return $sms_message;
	}
}
