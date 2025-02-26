<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 - 2025 Sven Schultschik. All rights reserved
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
 * @since  5.2.0
 */
class TreePrepareEvent extends AbstractEvent
{

    /**
     * Get the event's sitemap model object
     *
     * @return  SitemapModel
     *
     * @since  5.2.0
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
     * @since  5.2.0
     */
    public function getNode(): \stdClass
    {
        return $this->arguments['node'];
    }
}
