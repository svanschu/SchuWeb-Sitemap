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

namespace SchuWeb\Component\Sitemap\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;

/**
 * @package         Joomla
 * @subpackage      com_schuweb_sitemap
 * @since           2.0
 */
class SitemapTable extends Table
{

    /**
     * @var int Primary key
     */
    var $id = null;
    /**
     * @var string
     */
    var $title = null;
    /**
     * @var string
     */
    var $alias = null;
    /**
     * @var string
     */
    var $introtext = null;
    /**
     * @var string
     */
    var $metakey = null;
    /**
     * @var string
     */
    var $attribs = null;
    /**
     * @var string
     */
    var $selections = null;
    /**
     * @var string
     */
    var $created = null;
    /**
     * @var string
     */
    var $metadesc = null;
    /**
     * @var string
     */
    var $excluded_items = null;
    /**
     * @var int
     */
    var $is_default = 0;
    /**
     * @var int
     */
    var $state = 0;
    /**
     * @var int
     */
    var int $access = 0;
    /**
     * @var int
     */
    var $count_xml = 0;
    /**
     * @var int
     */
    var $count_html = 0;
    /**
     * @var int
     */
    var $views_xml = 0;
    /**
     * @var int
     */
    var $views_html = 0;
    /**
     * @var int
     */
    var $lastvisit_xml = 0;
    /**
     * @var int
     */
    var $lastvisit_html = 0;

    /**
     * @var array
     */
    protected $_jsonEncode = array('params', 'selections');

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   __BUMP_VERSION__
     */
    function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__schuweb_sitemap', 'id', $db);
    }

    /**
     * Overloaded bind function
     *
     * @access      public
     *
     * @param array hash named $array
     * @param string $ignore
     *
     * @return      null|string  null is operation was satisfactory, otherwise returns an error
     * @see         JTable:bind
     * @since       2.0
     */
    function bind($array, $ignore = '')
    {
        if (isset($array['attribs']) && is_array($array['attribs'])) {
            $registry = new Registry();
            $registry->loadArray($array['attribs']);
            $array['attribs'] = $registry->toString();
        }

        if (isset($array['selections']) && is_array($array['selections']) && $array['selections'][0] != null) {
            $selections = array();
            foreach ($array['selections'] as $i => $menu) {
                $selections[$menu] = array(
                    'priority' => $array['selections_priority'][$i],
                    'changefreq' => $array['selections_changefreq'][$i],
                );
            }

            $registry = new Registry();
            $registry->loadArray($selections);
            $array['selections'] = $registry->toString();
        }

        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new Registry();
            $registry->loadArray($array['metadata']);
            $array['metadata'] = $registry->toString();
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Overloaded check function
     *
     * @access      public
     * @return      boolean
     * @see         JTable::check
     * @since       2.0
     */
    function check()
    {
        $app = Factory::$application;

        if (empty($this->title)) {
            $app->enqueueMessage(Text::_('Sitemap must have a title'), 'error');
            return false;
        }

        if (empty($this->alias)) {
            $this->alias = $this->title;
        }
        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $datenow = Factory::getDate();
            $this->alias = $datenow->format("Y-m-d-H-i-s");
        }

        return true;
    }

    /**
     * Overriden JTable::store to set modified data and user id.
     *
     * @param       boolean True to update fields even if they are null.
     * @return      boolean True on success.
     * @since       2.0
     */
    public function store($updateNulls = false)
    {
        $date = Factory::getDate();
        if (!$this->id) {
            $this->created = $date->toSql();
        }
        return parent::store($updateNulls);
    }

    /**
     * Method to set the publishing state for a row or list of rows in the database
     * table.
     *
     * @param       mixed   An optional array of primary key values to update.  If not
     *                      set the instance property value is used.
     * @param       integer The publishing state. eg. [0 = unpublished, 1 = published]
     * @param       integer The user id of the user performing the operation.
     * @return      boolean True on success.
     * @since       2.0
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        // Initialize variables.
        $k = $this->_tbl_key;

        // Sanitize input.
        ArrayHelper::toInteger($pks);
        $state = (int) $state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            } // Nothing to set publishing state on, return false.
            else {
                Factory::$application->enqueueMessage(Text::_('No_Rows_Selected'), 'error');
                return false;
            }
        }

        // Build the WHERE clause for the primary keys.
        $where = $k . '=' . implode(' OR ' . $k . '=', $pks);

        // Update the publishing state for rows with the given primary keys.
        $query = $this->_db->getQuery(true)
            ->update($this->_db->quoteName('#__schuweb_sitemap'))
            ->set($this->_db->quoteName('state') . ' = ' . (int) $state)
            ->where($where);

        $this->_db->setQuery($query);
        $this->_db->execute();

        // If the JTable instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks)) {
            $this->state = $state;
        }

        return true;
    }

}