<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use SchuWeb\Component\Sitemap\Administrator\Extension\SitemapComponent;
use Joomla\CMS\HTML\Registry;


#JTable::addIncludePath( JPATH_COMPONENT.'/tables' );

#jimport('joomla.form.form');
#JForm::addFieldPath( JPATH_COMPONENT.'/models/fields' );

// Register helper class
#JLoader::register('SchuWeb_SitemapHelper', dirname(__FILE__) . '/helpers/schuweb_sitemap.php');

// Include dependancies
#jimport('joomla.application.component.controller');

#$controller = JControllerLegacy::getInstance('SchuWeb_Sitemap');
#$controller->execute(JFactory::getApplication()->input->get('task'));
#$controller->redirect();

/**
 * The content service provider.
 *
 * @since   5.0.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since    5.0.0
	 */
	public function register(Container $container) : void
    {
        $container->registerServiceProvider(new CategoryFactory('\\SchuWeb\\Component\\Sitemap'));
        $container->registerServiceProvider(new MVCFactory('\\SchuWeb\\Component\\Sitemap'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\SchuWeb\\Component\\Sitemap'));
        $container->registerServiceProvider(new RouterFactory('\\SchuWeb\\Component\\Sitemap'));
        
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new SitemapComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};