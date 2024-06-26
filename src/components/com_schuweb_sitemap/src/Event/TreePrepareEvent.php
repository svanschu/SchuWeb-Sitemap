<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\Event;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;
use SchuWeb\Component\Sitemap\Site\Model\SitemapModel;

/**
 * This class gives a concrete implementation of the AbstractEvent class.
 *
 * @see    \Joomla\CMS\Event\AbstractEvent
 * @since  __BUMP_VERSION__
 */
class TreePrepareEvent extends AbstractEvent
{

    /**
     * Get the event's sitemap model object
     *
     * @return  SitemapModel
     *
     * @since  __BUMP_VERSION__
     */
    public function getSitemap(): SitemapModel
    {
        return $this->arguments['sitemap'];
    }

    /**
     * Get the node object
     *
     * @return  \stdClass
     *
     * @since  __BUMP_VERSION__
     */
    public function getNode(): \stdClass
    {
        return $this->arguments['node'];
    }
}
