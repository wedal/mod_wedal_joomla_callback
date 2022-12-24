<?php
defined('_JEXEC') || die();

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container
			->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\WedalJoomlaCallback'))
			->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\WedalJoomlaCallback\\Site\\Helper'))
			//->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\WedalJoomlaCallback\\Site\\Fields'))
			->registerServiceProvider(new Module);
	}
};