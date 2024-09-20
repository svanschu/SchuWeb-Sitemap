<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\Event;
use stdClass;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;

/**
 * This class gives a concrete implementation of the AbstractEvent class.
 *
 * @see    \Joomla\CMS\Event\AbstractEvent
 * @since  5.2.0
 */
class MenuItemPrepareEvent extends AbstractEvent
{

    /**
     * Get the event's menu item object
     *
     * @return  \stdClass
     *
     * @since  5.2.0
     */
    public function getMenuItem(): stdClass
    {
        return $this->arguments['menu_item'];
    }
}
