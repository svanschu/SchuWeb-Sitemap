<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Sven Schultschik (extensions@schultschik.de)
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
	 * Returns a list of installed extension, where SchuWeb sitemap has the fitting plugin installed
	 *
	 * @return mixed
	 *
	 * @since
	 */
	public static function getExtensionsList()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('e.*')
			->from($db->quoteName('#__extensions') . 'AS e')
			->join('INNER', '#__extensions AS p ON SUBSTRING(e.element,5)=p.element and p.enabled=0 and p.type=\'plugin\' and p.folder=\'schuweb_sitemap\'')
			->where('e.type=\'component\' and e.enabled=1');

		$db->setQuery($query);
        return $db->loadObjectList();
	}

}
