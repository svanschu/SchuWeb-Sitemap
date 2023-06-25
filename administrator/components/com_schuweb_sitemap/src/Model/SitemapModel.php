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

namespace SchuWeb\Component\Sitemap\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Database\ParameterType;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;

/**
 * Item Model for a sitemap.
 *
 * @since  __BUMP_VERSION__
 */
class SitemapModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  __BUMP_VERSION__
     */
    public $typeAlias = 'com_schuweb_sitemap.sitemap';

    /**
     * The context used for the associations table
     *
     * @var    string
     * @since  __BUMP_VERSION__
     */
    protected $associationsContext = 'com_schuweb_sitemap.item';

    //protected $_context = 'com_schuweb_sitemap';

    /**
     * Constructor.
     *
     * @param    array An optional associative array of configuration settings.
     * @see      JController
     */
    // public function __construct($config = array())
    // {
    //     parent::__construct($config);

    //     $this->_item = 'sitemap';
    //     $this->_option = 'com_schuweb_sitemap';
    // }

    /**
     * Method to auto-populate the model state.
     */
    protected function populateState()
    {
        parent::populateState();

        $app = Factory::getApplication();

        // Load the User state.
        if (!($pk = (int) $app->getUserState('com_schuweb_sitemap.edit.sitemap.id'))) {
            $pk = (int) $app->input->getInt('id');
        }
        $this->setState('sitemap.id', $pk);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_schuweb_sitemap');
        $this->setState('params', $params);
    }

    /**
     * Method to get a single record.
     *
     * @param integer    The id of the primary key.
     *
     * @return   mixed      Object on success, false on failure.
     * @throws Exception
     * @since 1
     */
    public function getItem($pk = null)
    {
        // Initialise variables.
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('sitemap.id');

        // Get a row instance.
        $table = $this->getTable();

        // Attempt to load the row.
        $table->load($pk);

        // Prime required properties.
        if (empty($table->id)) {
            //TODO Prepare data for a new record.
        }

        // Convert to the JObject before adding other data.
        $value = $table->getProperties(1);
        $value = ArrayHelper::toObject($value, 'JObject');

        // Convert the params field to an array.
        $registry = new Registry;
        $registry->loadString($table->attribs);
        $value->attribs = $registry->toArray();

        $item = parent::getItem($pk);

        $item->selections = new Registry($item->selections);
        $item->selections = $item->selections->toArray();

        return $item;
    }

    public function getMenus()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('m.*')
            ->from('#__menu_types AS m')
            ->order('m.title');
        $db->setQuery($query);
        return $db->loadObjectList('menutype');
    }

    /**
     * Method to get the record form.
     *
     * @param    array $data Data for the form.
     * @param    boolean $loadData True if the form is to load its own data (default case), false if not.
     * @return   mixed                   A JForm object on success, false on failure
     * @since    2.0
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_schuweb_sitemap.sitemap', 'sitemap', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     * @since    1.6
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_schuweb_sitemap.edit.sitemap.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        if (is_array($data)) {
            if (!is_array($data['attribs'])) {
                $data['attribs'] = json_decode($data['attribs'], true);
            }
        } else {
            if (!is_array($data->attribs)) {
                $data->attribs = json_decode($data->attribs, true);
            }
        }

        return $data;
    }


    /**
     * Method to save the form data.
     *
     * @param    array    The form data.
     * @return    boolean    True on success.
     * @since    1.6
     */
    public function save($data)
    {
        $app = Factory::$application;

        // Initialise variables;
        $table = $this->getTable();
        $pk = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('sitemap.id');

        // Load the row if saving an existing record.
        if ($pk > 0) {
            $table->load($pk);
        }

        // Bind the data.
        if (!$table->bind($data)) {
            $app->enqueueMessage(Text::sprintf('JERROR_TABLE_BIND_FAILED', $table->getError()), 'error');
            return false;
        }

        // Check the data.
        if (!$table->check()) {
            $app->enqueueMessage($table->getError(), 'error');
            return false;
        }

        if (!$table->is_default) {
            // Check if there is no default sitemap. Then, set it as default if not
            $result = $this->getDefaultSitemapId();
            if (!$result) {
                $table->is_default = 1;
            }
        }

        // Store the data.
        if (!$table->store()) {
            $app->enqueueMessage($table->getError(), 'error');
            return false;
        }

        if ($table->is_default) {
            $query = $this->_db->getQuery(true)
                ->update($this->_db->quoteName('#__schuweb_sitemap'))
                ->set($this->_db->quoteName('is_default') . ' = 0')
                ->where($this->_db->quoteName('id') . ' <> ' . $table->id);

            $this->_db->setQuery($query);
            $this->_db->execute();
        }

        // Clean the cache.
        $cache = Factory::getCache('com_schuweb_sitemap');
        $cache->clean();

        $this->setState('sitemap.id', $table->id);

        return true;
    }

    // function setDefault($id)
    // {
    //     $table = $this->getTable();
    //     if ($table->load($id)) {
    //         $db = Factory::getDbo();
    //         $query = $db->getQuery(true)
    //             ->update($db->quoteName('#__schuweb_sitemap'))
    //             ->set($db->quoteName('is_default') . ' = 0')
    //             ->where($db->quoteName('id') . ' <> ' . $table->id);
    //         $this->_db->setQuery($query);
    //         $this->_db->execute();
    //         $table->is_default = 1;
    //         $table->store();

    //         // Clean the cache.
    //         $cache = Factory::getCache('com_schuweb_sitemap');
    //         $cache->clean();
    //         return true;
    //     }
    // }

    /**
     * Override to avoid warnings
     *
     */
    // public function checkout($pk = null)
    // {
    //     return true;
    // }

    private function getDefaultSitemapId()
    {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from($db->quoteName('#__schuweb_sitemap'));
        $query->where('is_default=1');
        $db->setQuery($query);
        return $db->loadResult();
    }
}