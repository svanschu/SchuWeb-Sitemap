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
class SchuWeb_SitemapHelper
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
	/**
	 * Returns a list of installed extension, where SchuWeb sitemap has the fitting plugin installed
	 *
	 * @return mixed
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getExtensionsList()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('e.*')
			->from($db->quoteName('#__extensions') . 'AS e')
			->join('INNER', '#__extensions AS p ON e.element=p.element and p.enabled=0 and p.type=\'plugin\' and p.folder=\'schuweb_sitemap\'')
			->where('e.type=\'component\' and e.enabled=1');

		$db->setQuery($query);
		$extensions = $db->loadObjectList();

		return $extensions;
	}

}
