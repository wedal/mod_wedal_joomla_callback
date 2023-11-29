<?php
namespace Joomla\Module\WedalJoomlaCallback\Site\Dispatcher;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Extension\ModuleInterface;
use Joomla\CMS\Factory;
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
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();

		$data['form'] = $this->moduleExtension->getHelper('WedalJoomlaCallbackHelper');
		$data['form']->getForm($data['module']->id);

		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->registerAndUseScript('wjcallback', 'mod_wedal_joomla_callback/wjcallback.js', [] ,['defer ' => true]);
		$wa->registerAndUseStyle('wjcallback', 'mod_wedal_joomla_callback/wjcallback.css');

		if ($data['params']->get('showphonemask')) {
			$wa->registerAndUseScript('maska', 'mod_wedal_joomla_callback/maska.js', [] ,['defer ' => true]);
		}

		$data['params']->set('layout', $data['params']->get('layout', 'default') . ($data['params']->get('moduletype') ? '_embeddedform' : ''));

		return $data;
	}
}
