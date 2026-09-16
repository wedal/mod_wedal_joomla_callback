<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;

/**
 * Журнал модуля — logs/mod_wedal_joomla_callback.php.
 */
final class LogHelper
{
	// Категория и файл журнала модуля.
	private const CATEGORY = 'mod_wedal_joomla_callback';
	private const FILE = 'mod_wedal_joomla_callback.php';

	/**
	 * Пишет запись в журнал модуля.
	 *
	 * @param   string  $message   Текст записи. Значения от посетителя попадают в него только проверенными:
	 *                             переводом строки можно разорвать запись и подделать следующую.
	 * @param   int     $priority  Уровень записи — константа Log.
	 *
	 * @return  void
	 */
	public static function add($message, $priority = Log::WARNING)
	{
		try {
			Log::addLogger(array('text_file' => self::FILE), Log::ALL, array(self::CATEGORY));
			Log::add($message, $priority, self::CATEGORY);
		} catch (\Throwable $exception) {
			// Потерянная запись журнала лучше прерванной заявки.
		}
	}
}
