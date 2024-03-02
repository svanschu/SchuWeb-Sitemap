<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;

/**
 * 
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
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('e.*')
			->from($db->quoteName('#__extensions') . 'AS e')
			->join('INNER', '#__extensions AS p ON SUBSTRING(e.element,5)=p.element and p.enabled=0 and p.type=\'plugin\' and p.folder=\'schuweb_sitemap\'')
			->where('e.type=\'component\' and e.enabled=1');

		$db->setQuery($query);
        return $db->loadObjectList();
	}

}
