<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

/**
 * Ответ sms.ru, приведённый к одному виду.
 *
 * @see SmsRu
 */
final class SmsRuResult
{
	/**
	 * Код для неудач на стороне модуля: нет ключа API, нет получателя, оборвалась
	 * связь, ответ не разобрался. У sms.ru все коды трёхзначные, начиная со 100,
	 * так что ноль ни с одним из них не совпадает.
	 */
	public const LOCAL_ERROR_CODE = 0;

	/** Предел длины текста отказа: в журнал модуля не должен уходить ответ произвольного размера. */
	private const STATUS_TEXT_LIMIT = 300;

	/** @var bool Запрос выполнен и принят: и конвертом, и каждым получателем. */
	private $successful = false;

	/** @var int Код ответа: status_code sms.ru либо LOCAL_ERROR_CODE. */
	private $statusCode = self::LOCAL_ERROR_CODE;

	/** @var string Описание отказа. У успешного ответа пустое. */
	private $statusText = '';

	/** @var array<string, string> Идентификаторы сообщений: номер получателя => sms_id. */
	private $smsIds = array();

	/** @var float|null Остаток на счёте или null, если ответ его не содержал. */
	private $balance = null;


	private function __construct()
	{
	}

	/**
	 * Разбирает тело ответа sms.ru.
	 *
	 * @param   string  $body  Тело ответа на запрос с json=1.
	 *
	 * @return  self
	 */
	public static function fromJson($body)
	{
		$payload = json_decode((string) $body);

		// Сюда попадает и пустой ответ, и HTML страницы ошибки шлюза, и ответ. Без обязательного поля status: разобрать такое нечем.
		if (!($payload instanceof \stdClass) || !isset($payload->status)) {
			return self::fromLocalError('The sms.ru response is not a valid API reply.');
		}

		$result = new self();
		$result->statusCode = isset($payload->status_code) ? (int) $payload->status_code : self::LOCAL_ERROR_CODE;
		$result->statusText = isset($payload->status_text) ? self::sanitize($payload->status_text) : '';
		$result->balance = isset($payload->balance) && is_numeric($payload->balance) ? (float) $payload->balance : null;
		$result->successful = (string) $payload->status === SmsRu::STATUS_OK;

		$result->readRecipients($payload);

		return $result;
	}

	/**
	 * Неудача, до sms.ru не дошедшая: оборванная связь, пустой ключ API,
	 * неразобранный ответ.
	 *
	 * @param   string  $message  Описание для журнала.
	 *
	 * @return  self
	 */
	public static function fromLocalError($message)
	{
		$result = new self();
		$result->statusText = self::sanitize($message);

		return $result;
	}

	/**
	 * Разбирает объект sms — по записи на получателя.
	 *
	 * Конверт может быть успешным, а отдельный номер — отклонённым, и тогда
	 * сообщение до него не дошло. Такой ответ успехом не считается, а код и
	 * текст первого отказавшего получателя занимают место кода конверта:
	 * иначе в журнал попало бы «всё хорошо» на неотправленное сообщение.
	 *
	 * @param   \stdClass  $payload  Разобранное тело ответа sms.ru.
	 *
	 * @return  void
	 */
	private function readRecipients(\stdClass $payload)
	{
		if (!isset($payload->sms)) {
			return;
		}

		foreach ((array) $payload->sms as $phone => $entry) {
			if (!($entry instanceof \stdClass)) {
				continue;
			}

			if (isset($entry->status) && (string) $entry->status === SmsRu::STATUS_OK) {
				$this->smsIds[(string) $phone] = isset($entry->sms_id) ? (string) $entry->sms_id : '';
				continue;
			}

			if ($this->successful) {
				$this->successful = false;
				$this->statusCode = isset($entry->status_code) ? (int) $entry->status_code : self::LOCAL_ERROR_CODE;
				$this->statusText = isset($entry->status_text) ? self::sanitize($entry->status_text) : '';
			}
		}
	}

	/**
	 * Приводит описание отказа к одной строке.
	 *
	 * Текст приходит от sms.ru, а уходит в журнал модуля: переводом строки в нём
	 * можно было бы разорвать запись журнала и подделать в ней следующую. Поэтому
	 * пробельные символы схлопываются, а длина ограничена.
	 *
	 * @param   mixed  $text  Текст из ответа API.
	 *
	 * @return  string
	 */
	private static function sanitize($text)
	{
		$text = preg_replace('/[[:cntrl:]\s]+/u', ' ', (string) $text);

		if ($text === null) {
			return '';
		}

		return mb_substr(trim($text), 0, self::STATUS_TEXT_LIMIT);
	}

	/**
	 * Принято ли сообщение: и конвертом запроса, и каждым получателем.
	 *
	 * @return  bool
	 */
	public function isSuccessful()
	{
		return $this->successful;
	}

	/**
	 * Код ответа: status_code sms.ru либо LOCAL_ERROR_CODE.
	 *
	 * @return  int
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * Описание отказа. У успешного ответа пустое.
	 *
	 * @return  string
	 */
	public function getStatusText()
	{
		return $this->statusText;
	}

	/**
	 * Идентификаторы принятых sms.ru сообщений.
	 *
	 * @return  array<string, string>  Номер получателя => идентификатор сообщения.
	 */
	public function getSmsIds()
	{
		return $this->smsIds;
	}

	/**
	 * Остаток на счёте, пришедший вместе с ответом на отправку.
	 *
	 * @return  float|null  Остаток либо null, если ответ его не содержал.
	 */
	public function getBalance()
	{
		return $this->balance;
	}
}
