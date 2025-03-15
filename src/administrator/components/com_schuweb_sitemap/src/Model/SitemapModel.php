<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

/**
 * Item Model for a sitemap.
 *
 * @since  5.0.0
 */
class SitemapModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  5.0.0
     */
    public $typeAlias = 'com_schuweb_sitemap.sitemap';

    /**
     * The context used for the associations table
     *
     * @var    string
     * @since  5.0.0
     */
    protected $associationsContext = 'com_schuweb_sitemap.item';

    /**
     * Method to auto-populate the model state.
     */
    protected function populateState()
    {
        parent::populateState();

        $app = Factory::getApplication();

        // Load the User state.
        $userstate = $app->getUserState('com_schuweb_sitemap.edit.sitemap.id');
        if (is_null($userstate) || !($pk = (int) $userstate[array_key_first($userstate)])) {
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
     * @return  mixed      Object on success, false on failure.
     * @throws  \Exception
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
        $value = ArrayHelper::toObject($value, '\Joomla\CMS\Object\CMSObject');

        // Convert the params field to an array.
        $registry = new Registry;
        if (!is_null($table) && !empty($table->attribs)) {
            $registry->loadString($table->attribs);
        }
        $value->attribs = $registry->toArray();

        $item = parent::getItem($pk);

        $item->selections = new Registry($item->selections);
        $item->selections = $item->selections->toArray();

        return $item;
    }

    public function getMenus()
    {
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
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
            if (!is_null($data->attribs) && !is_array($data->attribs)) {
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
            /** @var DatabaseDriver $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__schuweb_sitemap'))
                ->set($db->quoteName('is_default') . ' = 0')
                ->where($db->quoteName('id') . ' <> ' . $table->id);

            $db->setQuery($query);
            $db->execute();
        }

        $this->setState('sitemap.id', $table->id);

        return true;
    }

    public function setDefault($id): bool
    {
        $table = $this->getTable();
        if ($table->load($id)) {
            /** @var DatabaseDriver $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__schuweb_sitemap'))
                ->set($db->quoteName('is_default') . ' = 0')
                ->where($db->quoteName('id') . ' <> ' . $table->id);
            $db->setQuery($query);
            $db->execute();
            $table->is_default = 1;
            $table->store();

            return true;
        }

        return false;
    }

    private function getDefaultSitemapId()
    {
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select('id');
        $query->from($db->quoteName('#__schuweb_sitemap'));
        $query->where('is_default=1');
        $db->setQuery($query);
        return $db->loadResult();
    }
}