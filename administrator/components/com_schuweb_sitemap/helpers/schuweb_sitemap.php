<?php
/**
 * @version     $Id$
 * @copyright   Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */


// No direct access
defined('_JEXEC') or die;

/**
 * Xmap component helper.
 *
 * @package     SchuWeb Sitemap
 * @subpackage  com_schuweb_sitemap
 * @since       2.0
 */
class Schuweb_SitemapHelper
{
    /**
     * Configure the Linkbar.
     *
     * @param    string  The name of the active view.
     */
    public static function addSubmenu($vName)
    {
        JHtmlSidebar::addEntry(
            JText::_('SCHUWEB_SITEMAP_Submenu_Sitemaps'),
            'index.php?option=com_schuweb_sitemap',
            $vName == 'sitemaps'
        );
        JHtmlSidebar::addEntry(
            JText::_('SCHUWEB_SITEMAP_Submenu_Extensions'),
            'index.php?option=com_plugins&view=plugins&filter_folder=schuweb_sitemap',
            $vName == 'extensions');
    }
}
