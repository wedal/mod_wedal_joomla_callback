<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;

/**
 * Уведомление о заявке в Telegram: текст заявки и вложения через Bot API.
 */
final class TelegramHelper
{
	// Пределы ожидания запросов, секунды: соединение, текст заявки, загрузка вложений.
	private const CONNECT_TIMEOUT = 5;
	private const TIMEOUT = 10;
	private const UPLOAD_TIMEOUT = 30;

	// Больше элементов в одной группе sendMediaGroup не принимает.
	private const MEDIA_GROUP_LIMIT = 10;

	private const PHOTO_TYPES = array('image/jpeg', 'image/png', 'image/webp');

	/** @var WedalJoomlaCallbackHelper Форма с параметрами модуля, полями и значениями. */
	private $form;

	/**
	 * Создаёт помощник уведомлений в Telegram.
	 *
	 * @param   WedalJoomlaCallbackHelper  $form  Форма с параметрами модуля, полями и значениями.
	 */
	public function __construct($form)
	{
		$this->form = $form;
	}

	/**
	 * Отправляет уведомление и вложения в Telegram.
	 *
	 * @param   array        $attached_files  Принятые вложения из AttachmentHelper.
	 * @param   string|null  $page_url        Проверенный адрес страницы отправки.
	 *
	 * @return  bool  false — не доставлен текст заявки или хотя бы одна пачка вложений.
	 */
	public function send($attached_files, $page_url = null)
	{
		$params = $this->form->params;

		if (!$params->get('telegram_api_key') || !$params->get('telegram_chat_id')) {
			return false;
		}

		//Отправка запроса
		$tg_query = array(
			"chat_id" 	=> $params->get('telegram_chat_id'),
			"text"  	=> $this->buildMessage($page_url),
			"parse_mode" => "html",
		);

		if (!$this->request('sendMessage', http_build_query($tg_query), self::TIMEOUT)) {
			return false;
		}

		//Отправка вложений
		if (empty($attached_files)) {
			return true;
		}

		$delivered = true;

		foreach ($this->buildMediaBatches($attached_files) as $batch) {
			if (!$this->sendMediaBatch($batch)) {
				$delivered = false;
			}
		}

		return $delivered;
	}

	/**
	 * Раскладывает вложения по запросам Telegram.
	 *
	 * @param   array  $attached_files  Принятые вложения из AttachmentHelper.
	 *
	 * @return  array[]  Пачки вида ['type' => 'photo'|'document', 'files' => [...]].
	 */
	private function buildMediaBatches($attached_files)
	{
		$groups = array('photo' => array(), 'document' => array());

		foreach ($attached_files as $file) {
			$groups[$this->getMediaType($this->getAttachmentMimeType($file))][] = $file;
		}

		$batches = array();

		foreach ($groups as $type => $files) {
			foreach (array_chunk($files, self::MEDIA_GROUP_LIMIT) as $chunk) {
				$batches[] = array('type' => $type, 'files' => $chunk);
			}
		}

		return $batches;
	}

	/**
	 * Тип содержимого определяет finfo при приёме файла.
	 *
	 * @param   array  $file  Принятое вложение из AttachmentHelper.
	 *
	 * @return  string  Тип содержимого либо application/octet-stream, если он неизвестен.
	 */
	private function getAttachmentMimeType($file)
	{
		return isset($file['mime_type']) && is_string($file['mime_type']) && $file['mime_type'] !== ''
			? $file['mime_type']
			: 'application/octet-stream';
	}

	/**
	 * Фотографией уходит только то, что Telegram точно принимает как изображение. Остальное — документом: так владелец сайта получает файл в исходном виде, а не ошибку доставки.
	 *
	 * @param   string  $mimeType  Тип содержимого вложения.
	 *
	 * @return  string  photo или document.
	 */
	private function getMediaType($mimeType)
	{
		return in_array(strtolower((string) $mimeType), self::PHOTO_TYPES, true) ? 'photo' : 'document';
	}

	/**
	 * Отправляет одну пачку вложений.
	 *
	 * @param   array  $batch  Пачка из buildMediaBatches().
	 *
	 * @return  bool  Пачка принята Bot API.
	 */
	private function sendMediaBatch($batch)
	{
		$query = array('chat_id' => $this->form->params->get('telegram_chat_id'));
		$media = array();

		foreach ($batch['files'] as $file) {
			$filename = $file['stored_name'];

			$query[$filename] = new \CURLFile($file['path'], $this->getAttachmentMimeType($file), File::makeSafe($file['name']));
			$media[] = array('type' => $batch['type'], 'media' => 'attach://' . $filename);
			//@todo: добавить caption для изображений из label полей
		}

		if (count($media) === 1) {
			// sendMediaGroup требует не меньше двух элементов, поэтому одиночное вложение уходит своим методом и отдельным полем файла.
			$filename = $batch['files'][0]['stored_name'];
			$method = $batch['type'] === 'photo' ? 'sendPhoto' : 'sendDocument';
			$query[$batch['type']] = $query[$filename];
			unset($query[$filename]);
		} else {
			$method = 'sendMediaGroup';
			$query['media'] = json_encode($media);
		}

		return $this->request($method, $query, self::UPLOAD_TIMEOUT);
	}

	/**
	 * Выполняет запрос к Bot API.
	 *
	 * @param   string        $method   Метод Bot API.
	 * @param   string|array  $fields   Тело запроса: строка запроса либо массив с CURLFile.
	 * @param   int           $timeout  Предел ожидания ответа, секунды.
	 *
	 * @return  bool  Ответ получен, и Bot API сообщил об успехе.
	 */
	private function request($method, $fields, $timeout)
	{
		$ch = curl_init('https://api.telegram.org/bot' . $this->form->params->get('telegram_api_key') . '/' . $method);

		if ($ch === false) {
			return false;
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		unset($ch);

		return $this->isSuccessfulResponse($result, $httpCode);
	}

	/**
	 * Собирает текст уведомления для Telegram.
	 *
	 * @param   string|null  $page_url  Проверенный адрес страницы отправки.
	 *
	 * @return  string
	 */
	private function buildMessage($page_url = null)
	{
		$tg_message = '';

		if ($this->form->params->get('telegram_introtext')) {
			$tg_message .= $this->escapeHtml($this->form->params->get('telegram_introtext')) . "\n\n";
		}

		foreach ($this->form->values as $key => $value) {
			if (is_array($value)) {
				$value = implode(', ', $value);
			}

			$tg_message .= $this->escapeHtml($this->form->form->getFieldAttribute($key, 'label')) . ': ' . $this->escapeHtml($value) . "\n";
		}

		if (!empty($page_url)) {
			$tg_message .= "\n" . $this->escapeHtml(Text::_('MOD_WEDAL_JOOMLA_CALLBACK_SEND_FROM_URL')) . "\n" . $this->escapeHtml($page_url);
		}

		return $tg_message;
	}

	/**
	 * Экранирует значение для сообщения Telegram с parse_mode=html.
	 *
	 * @param   mixed  $value  Значение из параметров модуля или полей заявки.
	 *
	 * @return  string
	 */
	private function escapeHtml($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	/**
	 * Проверяет транспортный и API-результат Telegram, не раскрывая ответ пользователю.
	 *
	 * @param   mixed  $response  Тело ответа, как его вернул cURL.
	 * @param   int    $httpCode  Код ответа HTTP.
	 *
	 * @return  bool  Запрос доставлен, и Bot API вернул ok.
	 */
	private function isSuccessfulResponse($response, $httpCode)
	{
		if (!is_string($response) || $httpCode < 200 || $httpCode >= 300) {
			return false;
		}

		$payload = json_decode($response);

		return is_object($payload) && !empty($payload->ok);
	}
}
