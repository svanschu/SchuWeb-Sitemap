<?php
/**
 * @version      $Id$
 * @copyright    Copyright (C) 2019 SchuWeb Extensions Sven Schultschik, All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Sitemap model.
 *
 * @package       Xmap
 * @subpackage    com_schuweb_sitemap
 */
class SchuWeb_SitemapModelSitemap extends JModelAdmin
{
    protected $_context = 'com_schuweb_sitemap';

    /**
     * Constructor.
     *
     * @param    array An optional associative array of configuration settings.
     * @see      JController
     */
    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->_item = 'sitemap';
        $this->_option = 'com_schuweb_sitemap';
    }

    /**
     * Method to auto-populate the model state.
     */
    protected function _populateState()
    {
        $app = JFactory::getApplication('administrator');

        // Load the User state.
        if (!($pk = (int)$app->getUserState('com_schuweb_sitemap.edit.sitemap.id'))) {
            $pk = (int)$app->input->getInt('id');
        }
        $this->setState('sitemap.id', $pk);

        // Load the parameters.
        $params = JComponentHelper::getParams('com_schuweb_sitemap');
        $this->setState('params', $params);
    }

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param string $type   The table type to instantiate
	 * @param string $prefix A prefix for the table class name. Optional.
	 * @param array  $config Configuration array for model. Optional.
	 *
	 * @return bool|Table A database object
	 * @since   2
	 */
    public function getTable($type = 'Sitemap', $prefix = 'SchuWeb_SitemapTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
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
        $pk = (!empty($pk)) ? $pk : (int)$this->getState('sitemap.id');
        $false = false;

        // Get a row instance.
        $table = $this->getTable();

        // Attempt to load the row.
        $return = $table->load($pk);

        // Prime required properties.
        if (empty($table->id)) {
            // Prepare data for a new record.
        }

        // Convert to the JObject before adding other data.
        $value = $table->getProperties(1);
        $value = ArrayHelper::toObject($value, 'JObject');

        // Convert the params field to an array.
        $registry = new JRegistry;
        $registry->loadString($table->attribs);
        $value->attribs = $registry->toArray();

        $item = parent::getItem($pk);

        $item->selections = new Registry($item->selections);
        $item->selections = $item->selections->toArray();

        return $item;
    }

	public function getMenus()
	{
		$db = JFactory::getDbo();
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
        $data = JFactory::getApplication()->getUserState('com_schuweb_sitemap.edit.sitemap.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        $data->attribs = json_decode($data->attribs, true);

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
        $app = JFactory::$application;

        // Initialise variables;
        $dispatcher = JEventDispatcher::getInstance();
        $table = $this->getTable();
        $pk = (!empty($data['id'])) ? $data['id'] : (int)$this->getState('sitemap.id');
        $isNew = true;

        // Load the row if saving an existing record.
        if ($pk > 0) {
            $table->load($pk);
            $isNew = false;
        }

        // Bind the data.
        if (!$table->bind($data)) {
            $app->enqueueMessage(JText::sprintf('JERROR_TABLE_BIND_FAILED', $table->getError()), 'error');
            return false;
        }

        // Prepare the row for saving
        $this->_prepareTable($table);

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
        $cache = JFactory::getCache('com_schuweb_sitemap');
        $cache->clean();

        $this->setState('sitemap.id', $table->id);

        return true;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     */
    protected function _prepareTable(&$table)
    {
        // TODO.
    }

    function _orderConditions($table = null)
    {
        $condition = array();
        return $condition;
    }

    function setDefault($id)
    {
        $table = $this->getTable();
        if ($table->load($id)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__schuweb_sitemap'))
                ->set($db->quoteName('is_default') . ' = 0')
                ->where($db->quoteName('id') . ' <> ' . $table->id);
            $this->_db->setQuery($query);
            $this->_db->execute();
            $table->is_default = 1;
            $table->store();

            // Clean the cache.
            $cache = JFactory::getCache('com_schuweb_sitemap');
            $cache->clean();
            return true;
        }
    }

    /**
     * Override to avoid warnings
     *
     */
    public function checkout($pk = null)
    {
        return true;
    }

    private function getDefaultSitemapId()
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from($db->quoteName('#__schuweb_sitemap'));
        $query->where('is_default=1');
        $db->setQuery($query);
        return $db->loadResult();
    }
}