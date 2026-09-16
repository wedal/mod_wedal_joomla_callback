<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\Filesystem\File;

/**
 * Вложения заявки: приём загруженных файлов, проверка типа, хранение в tmp_path на время отправки и удаление после неё.
 */
final class AttachmentHelper
{
	private const SAFE_ATTACHMENT_TYPES = array(
		'jpg' => array('image/jpeg'),
		'jpeg' => array('image/jpeg'),
		'png' => array('image/png'),
		'gif' => array('image/gif'),
		'webp' => array('image/webp'),
		'avif' => array('image/avif'),
		'bmp' => array('image/bmp', 'image/x-ms-bmp'),
		'tif' => array('image/tiff'),
		'tiff' => array('image/tiff'),
		'pdf' => array('application/pdf'),
		'doc' => array('application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2'),
		'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
		'xls' => array('application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2'),
		'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
	);

	// Правило accept по умолчанию: совпадает со значением настройки attachmentformat в манифесте и покрывает белый список SAFE_ATTACHMENT_TYPES целиком. Пустое правило означает именно его, а не отказ во вложении.
	private const DEFAULT_ATTACHMENT_ACCEPT = 'image/*,.pdf,.doc,.docx,.xls,.xlsx';

	/** @var object Приложение Joomla */
	private $app;

	/** @var string[] Пути файлов, записанных в tmp_path за время запроса. */
	private $storedPaths = array();

	/**
	 * @param   object  $app  Приложение Joomla.
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Принимает вложения всех полей формы типа file: дополнительных полей и штатного поля модуля.
	 *
	 * @param   WedalJoomlaCallbackHelper  $form  Форма с параметрами модуля.
	 *
	 * @return  array[]  Принятые файлы — в том виде, в каком их отдаёт receiveField().
	 */
	public function receive($form)
	{
		$files = array();

		// Поля типа file среди дополнительных полей
		foreach ($form->form->getFieldset('customfields') as $field) {
			if (!empty($field->getAttribute('name')) && !empty($field->getAttribute('type')) && $field->getAttribute('type') == 'file') {
				$files = array_merge($files, $this->receiveField($field->getAttribute('name'), $form));
			}
		}

		// Стандартное вложение
		if ($form->params->get('showattachment')) {
			$files = array_merge($files, $this->receiveField('attachments', $form));
		}

		return $files;
	}

	/**
	 * Принимает файлы одного поля: проверяет тип и переносит файл в tmp_path под случайным именем.
	 *
	 * @param   string                     $file_field_name  Имя поля вложения.
	 * @param   WedalJoomlaCallbackHelper  $form             Форма с параметрами модуля.
	 *
	 * @return  array  Принятые файлы: к каждому добавлены stored_name, mime_type и path.
	 */
	public function receiveField($file_field_name, $form)
	{
		$files = $this->normalizeUploadedFiles($this->app->getInput()->files->get($file_field_name));

		if (empty($files)) {
			return array();
		}

		$tmpPath = $this->app->get('tmp_path');
		$accept = $this->getAcceptRule($file_field_name, $form);
		$returned_files = array();

		foreach ($files as $key => $file)
		{
			if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
				continue;
			}

			$fileExt = strtolower(File::getExt($file['name']));
			$mimeType = false;
			$finfo = new \finfo(FILEINFO_MIME_TYPE);

			if ($finfo) {
				$mimeType = $finfo->file($file['tmp_name']);
			}

			if (!$this->isValidFileType($fileExt, $mimeType, $accept)) {
				$this->logDroppedAttachment($file_field_name, $fileExt, $mimeType, 'the type is not allowed');

				continue;
			}

			$storedName = bin2hex(random_bytes(16)) . '.' . $fileExt;
			$dest = $tmpPath . '/' . $storedName;

			if (File::upload($file['tmp_name'], $dest)) {
				$this->storedPaths[] = $dest;

				$file['stored_name'] = $storedName;
				$file['mime_type'] = $mimeType;
				$file['path'] = $dest;
				$returned_files[$key] = $file;
			} else {
				$this->logDroppedAttachment($file_field_name, $fileExt, $mimeType, 'the file could not be written to tmp_path');
			}
		}

		return $returned_files;
	}

	/**
	 * Удаляет из tmp_path всё, что записал помощник. Вызывается, когда заявка обработана, —
	 * даже если обработка оборвалась на середине.
	 *
	 * @return  void
	 */
	public function cleanup()
	{
		foreach ($this->storedPaths as $path) {
			if (File::exists($path)) {
				File::delete($path);
			}
		}

		$this->storedPaths = array();
	}

	/** Приводит вложения поля к списку файлов.
	 * @param   mixed  $files  Значение поля из Files::get().
	 *
	 * @return  array  Список файлов, у каждого непустое имя.
	 */
	private function normalizeUploadedFiles($files)
	{
		if (!is_array($files) || $files === array()) {
			return array();
		}

		if (array_key_exists('tmp_name', $files) && !is_array($files['tmp_name'])) {
			$files = array($files);
		}

		$normalized = array();

		foreach ($files as $key => $file) {
			if (is_array($file) && !empty($file['name']) && !is_array($file['name'])) {
				$normalized[$key] = $file;
			}
		}

		return $normalized;
	}

	/** Возвращает правило accept для поля вложения.
	 *
	 * @param   string  $file_field_name  Имя поля вложения.
	 * @param   mixed   $form             Объект формы.
	 *
	 * @return  string
	 */
	private function getAcceptRule($file_field_name, $form)
	{
		if ($file_field_name === 'attachments') {
			$accept = trim((string) $form->params->get('attachmentformat', ''));
		} else {
			$field = $form->form->getField($file_field_name);
			$accept = $field ? trim((string) $field->getAttribute('accept')) : '';
		}

		return $accept === '' ? self::DEFAULT_ATTACHMENT_ACCEPT : $accept;
	}

	// Записывает причину, по которой вложение не ушло.
	private function logDroppedAttachment($file_field_name, $fileExt, $mimeType, $reason)
	{
		LogHelper::add(sprintf(
			'The attachment from the field "%s" was dropped: %s (extension "%s", detected type "%s").',
			$this->forLog($file_field_name, '/^[a-z0-9_\-]{1,64}$/i'),
			$reason,
			$this->forLog($fileExt, '/^[a-z0-9]{1,10}$/i'),
			$this->forLog($mimeType, '#^[a-z0-9.+\-]+/[a-z0-9.+\-]+$#i')
		));
	}

	// Расширение файла приходит из его имени, то есть от посетителя. В журнал попадает только значение подходящей формы: остальное — 'unknown', иначе строку журнала можно было бы разорвать переводом строки и подделать в ней запись любого уровня.
	private function forLog($value, $pattern)
	{
		return is_string($value) && preg_match($pattern, $value) ? strtolower($value) : 'unknown';
	}

	// Сверяет расширение и MIME-тип файла с разрешёнными форматами.
	public function isValidFileType($file_ext, $filetype, $accept) {

		if (!$accept || !is_string($filetype) || !is_string($file_ext)) {
			return false;
		}

		$file_ext = strtolower($file_ext);
		$filetype = strtolower($filetype);
		$accept = strtolower($accept);

		// Первый рубеж — белый список
		if (!isset(self::SAFE_ATTACHMENT_TYPES[$file_ext])
			|| !in_array($filetype, self::SAFE_ATTACHMENT_TYPES[$file_ext], true)) {
			return false;
		}

		$rules_ext = array();
		$rules_mime = array();
		$rules_full_mime = array();

		//Разбираем все правила на отдельные расширения и MIME
		foreach (explode(',', str_replace(' ', '', $accept)) as $accept_rule) {
			if (strripos($accept_rule,'/')) {
				if (strripos($accept_rule,'/*')) {
					$rules_full_mime[] = stristr($accept_rule,'/*',true);
				} else {
					$rules_mime[] = $accept_rule;
				}

			} else {
				$rules_ext[] = $accept_rule;
			}
		}

		// Второй рубеж — правило accept формы: оно может только сузить белый список.
		if (in_array($file_ext, $rules_ext, true) || in_array('.' . $file_ext, $rules_ext, true)) {
			return true;
		}

		if (in_array($filetype, $rules_mime, true)) {
			return true;
		}

		//Остается случай, когда accept задан в формате image/*
		return in_array(stristr($filetype,'/',true), $rules_full_mime, true);
	}
}
