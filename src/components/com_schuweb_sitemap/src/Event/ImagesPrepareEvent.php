<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\Event;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Event\Result\ResultAware;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Event\Result\ResultTypeArrayAware;

/**
 * This class gives a concrete implementation of the AbstractEvent class.
 *
 * @see    \Joomla\CMS\Event\AbstractEvent
 * @since  __BUMP_VERSION__
 */
class ImagesPrepareEvent extends AbstractEvent implements ResultAwareInterface
{
    use ResultAware;
    use ResultTypeArrayAware;

    /**
     * Get the text of the content article
     *
     * @return  string
     *
     * @since  __BUMP_VERSION__
     */
    public function getText(): string
    {
        return $this->arguments['text'];
    }
}
