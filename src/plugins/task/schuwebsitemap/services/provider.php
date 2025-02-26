<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use SchuWeb\Plugin\Task\SchuWebSitemap\Extension\SchuWebSitemap;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.2.0
     */
    public function register(Container $container)
    {
        if (!ComponentHelper::isEnabled('com_schuweb_sitemap'))
		{
			return;
		}

        $container->registerServiceProvider(new MVCFactory('SchuWeb\\Component\\Sitemap'));

        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new SchuWebSitemap(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('task', 'schuwebsitemap')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
