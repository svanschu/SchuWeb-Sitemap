<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

jimport('joomla.database.query');

/**
 * Sitemaps Model Class
 *
 * @package         Joomla.Administrator
 * @subpackage      com_schuweb_sitemap
 * @since           2.0
 */
class SitemapsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array    An optional associative array of configuration settings.
     * @throws  Exception
     * @since   1.6
     * @see     JController
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'catid', 'a.catid', 'category_title',
                'state', 'a.state',
                'access', 'a.access', 'access_level',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'featured', 'a.is_default',
                'language', 'a.language',
                'publish_up', 'a.publish_up',
                'publish_down', 'a.publish_down',
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @since       2.0
     */
    protected function populateState($ordering = null, $direction = null)
    {
        // Adjust the context to support modal layouts.
        if ($layout = Factory::$application->input->getVar('layout')) {
            $this->context .= '.' . $layout;
        }

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
        $this->setState('filter.access', $access);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        // List state information.
        parent::populateState('a.title', 'asc');
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param string $id A prefix for the store id.
     *
     * @return  string      A store id.
     *
     * @since    5.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   __BUMP_VERSION__
     */
    public function getItems()
    {
        $sitemaps = parent::getItems();

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select([
            $db->quoteName('id'),
            $db->quoteName('title'),
            $db->quoteName('params')])
            ->from($db->quoteName('#__scheduler_tasks'))
            ->where([
                $db->quoteName('type') . ' = ' . $db->quote('PLG_TASK_SCHUWEBSITEMAP'),
                $db->quoteName('state') . ' = 1'
            ]);

        $db->setQuery($query);
        $tasks = $db->loadObjectList();

        if (empty($tasks))
            return $sitemaps;

        foreach ($sitemaps as $sitemap) {
            foreach ($tasks as $k => $task) {
                if (!($task->params instanceof Registry)) {
                    $task->params = new Registry($task->params);
                }

                if ($sitemap->id == (int) $task->params->get('sitemap')) {
                    unset($task->params);
                    $sitemap->task = $task;
                    unset($tasks[$k]);
                    break;
                }
            }
        }

        return $sitemaps;
    }
    
    /**
     *
     * @return  \JDatabaseQuery
     *
     * @since    5.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDbo();
        // Create a new query object.
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.*')
        );
        $query->from('#__schuweb_sitemap AS a');

        // Join over the asset groups.
        $query->select('ag.title AS access_level');
        $query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

        // Filter by access level.
        if ($access = $this->getState('filter.access')) {
            $query->where('a.access = ' . (int)$access);
        }

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('a.state = ' . (int)$published);
        } else if ($published === '') {
            $query->where('(a.state = 0 OR a.state = 1)');
        }

        // Filter by search in title.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int)substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
            }
        }

        // Add the list ordering clause.
        $query->order($db->escape($this->state->get('list.ordering', 'a.title')) . ' ' . $db->escape($this->state->get('list.direction', 'ASC')));

        return $query;
    }

    /**
     * Detect which plugins are installed, but disabled for full sitemap
     *
     * @return string
     *
     * @since    5.0.0
     */
    public function getExtensionsMessage(): string
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('e.*')
            ->from($db->quoteName('#__extensions') . 'AS e')
            ->join('INNER', '#__extensions AS p ON SUBSTRING(e.element,5)=p.element and p.enabled=0 and p.type=\'plugin\' and p.folder=\'schuweb_sitemap\'')
            ->where($db->quoteName('e.type') . '=' . $db->quote('component'))
            ->where($db->quoteName('e.enabled') . '=1')
            ->where($db->quoteName('p.state') . '=0');

        $db->setQuery($query);
        $extensions = $db->loadObjectList();
        if (count($extensions)) {
            $sep = $extensionsNameList = '';
            foreach ($extensions as $extension) {
                $extensionsNameList .= "$sep$extension->element";
                $sep = ', ';
            }

            $url = 'index.php?option=com_plugins&view=plugins&filter[folder]=schuweb_sitemap';

            return Text::sprintf('SCHUWEB_SITEMAP_MESSAGE_EXTENSIONS_DISABLED', $url, $extensionsNameList);
        } else {
            return "";
        }
    }

    /**
     * Detect which plugins are missing for full sitemap
     *
     * @return string
     *
     * @since 4.0
     */
    public function getNotInstalledMessage(): string
    {
        $db = $this->getDbo();

        $supportedExtensions = array('com_zoo', 'com_weblinks', 'com_kunena', 'com_dpcalendar');

        $query = $db->getQuery(true);
        $query->select($db->quoteName('e.extension_id') . ',' . $db->quoteName('e.element'))
            ->from($db->quoteName('#__extensions') . 'AS e')
            ->whereIn($db->quoteName('e.element'), $supportedExtensions, ParameterType::STRING);

        $db->setQuery($query);
        $extensions = $db->loadObjectList();

        $query = $db->getQuery(true);
        $query->select($db->quoteName('element'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . '=' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . '=' . $db->quote('schuweb_sitemap'))
            ->where($db->quoteName('state') . '=0');
        $db->setQuery($query);
        $plugins = $db->loadAssocList();

        $pluginList = array();
        foreach ($plugins as $plugin) {
            $pluginList[] = $plugin['element'];
        }

        if (count($extensions)) {
            $sep = $extensionsNameList = '';
            foreach ($extensions as $extension) {
                if (!in_array(substr($extension->element, 4), $pluginList)) {
                    $extensionsNameList .= "$sep$extension->element";
                    $sep = ', ';
                }
            }
        }

        if (!empty($extensionsNameList)) {
            return Text::sprintf('SCHUWEB_SITEMAP_MESSAGE_EXTENSIONS_NOT_INSTALLED', $extensionsNameList);
        } else {
            return "";
        }
    }
}
