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

namespace SchuWeb\Libraries\Button;

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The DefaultButton class.
 *
 * @since  __BUMP_VERSION__
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