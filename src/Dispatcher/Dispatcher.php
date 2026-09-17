<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Extension\ModuleInterface;
use Joomla\CMS\Language\Text;
use Joomla\Input\Input;

/**
 * Dispatcher class for mod_wedal_jooomla_callback
 *
 * @since  4.0.0
 */
class Dispatcher extends AbstractModuleDispatcher
{
	/**
	 * The module extension. Used to fetch the module helper.
	 *
	 * @var   ModuleInterface|null
	 * @since 1.0.0
	 */
	private $moduleExtension;


	public function __construct(\stdClass $module, CMSApplicationInterface $app, Input $input)
	{
		parent::__construct($module, $app, $input);

		$this->moduleExtension = $this->app->bootModule('mod_wedal_joomla_callback', 'site');
	}


	/**
	 * Returns the layout data.
	 *
	 * @return  array|false  
	 *
	 * @since   4.0.0
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();

		$data['form'] = $this->moduleExtension->getHelper('WedalJoomlaCallbackHelper');
		if (!$data['form']->getForm($data['module'])) {
			return false;
		}

		$wa = $this->app->getDocument()->getWebAssetManager();
		$wa->registerAndUseScript('wjcallback', 'mod_wedal_joomla_callback/wjcallback.js', [] ,['defer' => true]);
		$wa->registerAndUseStyle('wjcallback', 'mod_wedal_joomla_callback/wjcallback.css');

		Text::script('MOD_WEDAL_JOOMLA_CALLBACK_DELIVERY_ERROR');

		if ($data['params']->get('showphonemask')) {
			$wa->registerAndUseScript('wjphonemask', 'mod_wedal_joomla_callback/wjphonemask.js', [] ,['defer' => true]);
		}

		// Всплывающая форма грузится по нажатию отдельным запросом, и ресурсы CAPTCHA
		// в его ответ не попадают. Подключаем их к странице заранее.
		if (!$data['params']->get('moduletype')) {
			$data['form']->warmUpCaptchaAssets();
		}

		$data['params']->set('layout', $data['params']->get('layout', 'default') . ($data['params']->get('moduletype') ? '_embeddedform' : ''));

		return $data;
	}
}
