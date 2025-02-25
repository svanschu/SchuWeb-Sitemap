<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\ParameterType;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

class com_schuweb_sitemapInstallerScript extends InstallerScript
{

    private string $_oldRelease;

    /**
     * Extension script constructor.
     *
     * @since   4.0.0
     */
    public function __construct()
    {
        // Define the minumum versions to be supported.
        $this->minimumJoomla = '4.0';
        $this->minimumPhp = '8';

        $this->_oldRelease = $this->getParam('version');
        if (version_compare($this->_oldRelease, "3.2.0", "lt")) {
            $this->deleteFolders = array("/components/com_schuweb_sitemap/assets/css");
        }
    }


    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param string $type - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param InstallerAdapter $parent - Parent object calling object.
     *
     * @return void
     * @since 3.2.0
     */
    public function preflight($type, $parent)
    {
        parent::preflight($type, $parent);

        if (strcmp($type, "update") !== 0)
            return;

        $this->removeFiles();
    }

    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param   string     $type    - Type of PostFlight action. Possible values
     *                              are:
     *                              - * install
     *                              - * update
     *                              - * discover_install
     * @param   \stdClass  $parent  - Parent object calling object.
     *
     * @return void
     *
     * @throws \Exception
     * @since  5.0.0
     */
    public function postflight($type, $parent)
    {
        if ($type == "update") {
            $this->upgradev4v5();
        }

        if ($type != "install")
		{
			return;
		}

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        // Get all menus
		$query = $db->getQuery(true);

		$query->select($db->quoteName('menutype'))
			->from($db->quoteName('#__menu_types'));

		$db->setQuery($query);
		$menus = $db->loadObjectList();

		// Create a default sitemap with all needed components and all menus
		$columns = array(
			'title',
			'alias',
			'attribs',
			'selections',
			'is_default',
			'state',
			'access',
		);

		// {"mainmenu":{"enabled":"1","priority":"0.5","changefreq":"weekly"}}
		$selections = array();

		foreach ($menus as $menu)
		{
			$selections[$menu->menutype] = array(
				"enabled"    => 1,
				"priority"   => 1,
				"changefreq" => "weekly",
			);
		}

		$attribs = '{"showintro":"1","show_menutitle":"1","classname":"",'
			. '"columns":"","compress_xml":"1",'
			. '"include_link":"1","xmlLastMod":"1",'
			. '"xmlInsertChangeFreq":"1","xmlInsertPriority":"1",'
			. '"news_publication_name":""}';

		$values = array(
			$db->quote('Sitemap'),
			$db->quote('sitemap'),
			$db->quote($attribs),
			$db->quote(json_encode($selections)),
			1,
			1,
			1,
		);

		$query = $db->getQuery(true);

		$query->insert($db->quoteName('#__schuweb_sitemap'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		$db->execute();
    }

    /**
     * Upgrade from SchuWeb Sitemap v4 and earlier to v5
     *
     * @since 5.0.0
     */
    private function upgradev4v5()
    {

        if (version_compare($this->_oldRelease, '5', '>='))
            return;

        $extensionId = $this->getInstances(false);

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);

        $query->select(array($db->quoteName('id'), $db->quoteName('link')))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('component_id') . ' = :cid')
            ->bind(':cid', $extensionId, ParameterType::INTEGER);
        $db->setQuery($query);

        $menus = $db->loadObjectList();

        # from: index.php?option=com_schuweb_sitemap&view=html&id=1
        # to:   index.php?option=com_schuweb_sitemap&view=sitemap&id=1
        foreach ($menus as $k => $menu) {
            if (str_contains($menu->link, 'view=html')) {
                $menu->link = substr_replace($menu->link, 'sitemap', 42, -5);

                $query = $db->getQuery(true);

                $fields = array(
                    $db->quoteName('link') . '=' . $db->quote($menu->link),
                );

                $conditions = array(
                    $db->quoteName('id') . '=' . $db->quote($menu->id),
                );

                $query->update($db->quoteName('#__menu'))
                    ->set($fields)
                    ->where($conditions);


                $db->setQuery($query);

                if (!$db->execute()) {
                    Factory::getApplication()->enqueueMessage(Text::_('SCHUWEB_SITEMAP_UPGRADE_V4_V5_FAILED'), 'error');
                }
            }
        }

        Factory::getApplication()->enqueueMessage(Text::_('SCHUWEB_SITEMAP_UPGRADE_V4_V5'), 'warning');
    }

    /**
     * Copy of Joomla\CMS\Installer\InstallerScript which has a bug
     * If bug is fixed in J5.xx it can be remove
     * PR: https://github.com/joomla/joomla-cms/pull/42192
     *
     * @param   boolean  $isModule  True if the extension is a module as this can have multiple instances
     *
     * @return  array  An array of ID's of the extension
     *
     * @since   5.0.0
     */
    public function getInstances($isModule)
    {
        $extension = $this->extension;

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        $query = $db->getQuery(true);

        // Select the item(s) and retrieve the id

        // Select the item(s) and retrieve the id
        if ($isModule) {
            $query->select($db->quoteName('id'))
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('module') . ' = :extension');
        } else {
            $query->select($db->quoteName('extension_id') . ' AS id')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = :extension');
        }


        $query->bind(':extension', $extension);

        // Set the query and obtain an array of id's
        return $db->setQuery($query)->loadColumn();
    }
}