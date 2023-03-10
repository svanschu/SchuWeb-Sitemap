<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_schuweb_sitemap
 * 
 * @version     sw.build.version
 * @copyright   Copyright (C) 2023 Sven Schultschik. All rights reserved
 * @license     GNU General Public License version 3; see LICENSE
 * @author      Sven Schultschik (extensions@schultschik.de)
 */

namespace SchuWeb\Component\Sitemap\Administrator\Extension;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Psr\Container\ContainerInterface;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;

class SitemapComponent extends MVCComponent implements
    BootableExtensionInterface
{

    use HTMLRegistryAwareTrait;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   __BUMP_VERSION__
     */
    public function boot(ContainerInterface $container)
    {
    }
}