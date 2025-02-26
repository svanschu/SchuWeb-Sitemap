<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Libraries\Button;

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The DefaultButton class.
 *
 * @since  5.0.0
 */
class DefaultButton extends \Joomla\CMS\Button\FeaturedButton
{
    /**
     * Configure this object.
     *
     * @return  void
     *
     * @since  4.0.0
     */
    protected function preprocess()
    {
        $this->addState(
            0,
            'setdefault',
            'icon-unfeatured',
            Text::_('SCHUWEB_SITEMAP_TOGGLE_DEFAULT'),
            ['tip_title' => Text::_('SCHUWEB_SITEMAP_HEADING_NOT_DEFAULT')]
        );
        $this->addState(
            1,
            'setdefault',
            'icon-color-featured icon-star',
            Text::_('SCHUWEB_SITEMAP_TOGGLE_DEFAULT'),
            ['tip_title' => Text::_('SCHUWEB_SITEMAP_HEADING_DEFAULT')]
        );
    }
}
