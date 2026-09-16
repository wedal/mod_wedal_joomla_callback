<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\Filesystem\File;

/**
 * Письмо с заявкой владельцу сайта.
 *
 * Отправка разбита на два шага, и между ними хелпер отправляет SMS. prepare() проверяет
 * получателя и собирает конверт: если письмо отправить нельзя, SMS не уходит. send() собирает
 * тело по макету *_message — в него уже попадает результат отправки SMS.
 */
final class EmailHelper
{
	/** @var object Приложение Joomla: адрес и имя отправителя из настроек сайта. */
	private $app;

	/** @var WedalJoomlaCallbackHelper Форма с параметрами модуля, полями и значениями. */
	private $form;

	/** @var \Joomla\CMS\Mail\MailerInterface|null Почтовик, собранный в prepare(). */
	private $mailer;

	/**
	 * @param   object                     $app   Приложение Joomla.
	 * @param   WedalJoomlaCallbackHelper  $form  Форма с параметрами модуля, полями и значениями.
	 */
	public function __construct($app, $form)
	{
		$this->app = $app;
		$this->form = $form;
	}

	/**
	 * Проверяет адрес получателя и собирает конверт письма: отправитель, получатели, адрес для ответа.
	 *
	 * Почтовик Joomla бросает исключение на некорректный адрес, поэтому вызывать метод
	 * нужно под обработчиком ошибок.
	 *
	 * @return  bool  false — адрес получателя негоден, причина записана в журнал.
	 */
	public function prepare()
	{
		$params = $this->form->params;

		$to = $params->get('email', '');
		if (!$to) {
			$to = $this->app->get('mailfrom');
		}

		if (!MailHelper::isEmailAddress($to)) {
			LogHelper::add('The recipient address is not a valid email address.', Log::ERROR);

			return false;
		}

		$this->mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
		$this->mailer->setSender(array($this->app->get('mailfrom'), $this->app->get('fromname')));

		$this->mailer->addRecipient($to);

		if ($params->get('email_additional', '')) {
			$additional_recipients = preg_split('/\r\n|[\r\n]/', $params->get('email_additional', ''));

			foreach ($additional_recipients as $additional_recipient) {
				if (MailHelper::isEmailAddress($additional_recipient)) {
					$this->mailer->addRecipient($additional_recipient);
				}
			}
		}

		if (!empty($this->form->values['email'])) {
			$this->mailer->addReplyTo($this->form->values['email']);
		}

		return true;
	}

	/**
	 * Собирает тело, прикрепляет вложения и отправляет письмо, подготовленное prepare().
	 *
	 * @param   string|null  $page_url     Проверенный адрес страницы отправки.
	 * @param   string|null  $sms_status   Результат SmsHelper::send() или null, если SMS выключены.
	 * @param   array        $attachments  Принятые вложения из AttachmentHelper.
	 *
	 * @return  bool  Письмо принято почтовиком.
	 */
	public function send($page_url, $sms_status, array $attachments)
	{
		if ($this->mailer === null) {
			return false;
		}

		$mailtitle = $this->form->params->get('mailtitle', '');
		if (!$mailtitle) {
			$mailtitle = Text::_('MOD_WEDAL_JOOMLA_CALLBACK_MAILTITLE_DEFAULT');
		}

		$body = $this->renderMessageBody($page_url, $sms_status);

		foreach ($attachments as $file) {
			$this->mailer->addAttachment($file['path'], File::makeSafe($file['name']));
		}

		$this->mailer->setSubject($mailtitle);
		$this->mailer->setBody($body);
		$this->mailer->isHTML();

		return $this->mailer->send() === true;
	}

	/** Собирает тело письма по макету *_message.
	 *
	 * Макет получает переменные $form, $page_url и $sms_status: на эти имена опираются
	 * и его переопределения в шаблонах сайта.
	 *
	 * @param   string|null  $page_url    Проверенный адрес страницы отправки.
	 * @param   string|null  $sms_status  Результат SmsHelper::send() или null, если SMS выключены.
	 *
	 * @return  string
	 */
	private function renderMessageBody($page_url, $sms_status)
	{
		$form = $this->form;

		ob_start();

		try {
			require ModuleHelper::getLayoutPath('mod_wedal_joomla_callback', $form->params->get('layout', 'default') . '_message');
		} finally {
			$body = (string) ob_get_clean();
		}

		return $body;
	}
}
