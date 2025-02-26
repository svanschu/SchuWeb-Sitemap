<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use SchuWeb\Component\Sitemap\Site\Event\MenuItemPrepareEvent;
use SchuWeb\Component\Sitemap\Site\Event\TreePrepareEvent;

/**
 * SchuWeb_Sitemap Component Sitemap Model
 *
 * @package        SchuWeb_Sitemap
 * @subpackage     com_schuweb_sitemap
 * @since          2.0
 */
class SitemapModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_schuweb_sitemap.sitemap';

    /**
     * List of sitemap extensions
     */
    private $extensions = null;

    static $items = array();

    /**
     * Is the sitemap the default sitemap?
     * 
     * @var bool
     * @since 5.0.0
     */
    private bool $default;

    /**
     * The nodes object details
     *
     * @var    \stdClass
     * @since  5.0.0
     */
    private $nodes;

    /**
     * The total number of menus
     *
     * @var    int
     * @since  5.0.0
     */
    private $totalmenusnumber;

    /**
     * Ist language filter active
     * 
     * @var bool
     * @since 5.0.0
     */
    private bool $languageFilter;

    /**
     * Language object
     * 
     * @var Language
     * @since 5.0.0
     */
    private Language $language;


    /**
     * Do we generate an XML?
     * 
     * @var bool
     * @since 5.0.0
     */
    private bool $xmlsitemap = false;

    /**
     *
     * @var bool Indicates if this is a google image sitemap or not
     *
     * @since 5.0.0
     */
    private bool $imagesitemap = false;

    /**
     *
     * @var bool Indicates if this is a google news sitemap or not
     *
     * @since 5.0.0
     */
    private bool $newssitemap = false;

    /**
     * @var bool Indicates if the duplicated entries should get removed
     *
     * @since 5.0.0
     */
    private bool $removeDuplicates;

    /**
     * @var bool Indicates if the duplicated menus should get removed
     *
     * @since 5.0.0
     */
    private bool $removeDuplicateMenus;

    /**
     * @var string This is the name of the news publication for the news sitemap
     * 
     * @since 5.0.0
     */
    private string $news_publication_name;

    /**
     * Method to auto-populate the model state.
     *
     * @return     void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $jinput = $app->input;
        // Load state from the request.
        $pk = $jinput->getInt('id');

        // If not sitemap specified, select the default one
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        $query = $db->getQuery(true);
        $query->select('attribs')
            ->from('#__schuweb_sitemap');
        if (!$pk) {
            $query->select('id')
                ->where('is_default=1');
        } else {
            $query->where($db->qn('id') . '='.$pk);
        }

        $db->setQuery($query);
        $result = $db->loadObject();

        if (!$pk) {
            $pk = $result->id;
        }

        $this->setState('sitemap.id', $pk);

        $offset = $jinput->getInt('limitstart');
        $this->setState('list.offset', $offset);

        // Load the parameters.
        $registry = new Registry('_default');
        if (!is_null($result) && !empty($result->attribs)) {
            $registry->loadString($result->attribs);
        }
        $params = $app->getParams();
        $params->merge($registry);
        if ($params->get('page_heading') == '') {
            $gparams = ComponentHelper::getParams('com_menus');
            if ($gparams->get('page_heading') != '') {
                $params->set('page_heading', $gparams->get('page_heading'));
            }
        }
        $this->setState('params', $params);

        // TODO: Tune these values based on other permissions.
        $this->setState('filter.published', 1);
        $this->setState('filter.access', true);
    }

    public function getNodes()
    {
        $this->nodes = new \stdClass();

        $menus = $this->getMenus();

        foreach ($menus as $menutype => &$items) {

            $node = new \stdClass();

            $node->uid        = "menu-" . $menutype;
            $node->menutype   = $menutype;
            $node->priority   = null;
            $node->changefreq = null;
            $node->browserNav = 3;
            $node->type       = 'separator';

            $node->name = $this->getMenuTitle($menutype); // Get the name of this menu

            $this->nodes->$menutype = $node;

            $this->getSubNodes($this->nodes->$menutype, $node, $items);
        }

        if ($this->removeDuplicates)
            $this->removingDuplicates($this->nodes);

        return $this->nodes;
    }

    private function removingDuplicates(&$nodes, &$links = array(), &$mlinks = array())
    {
        foreach ($nodes as $key => $node) {
            if (isset($node->link) && !(isset($node->type) && in_array($node->type, ["separator", "heading"]))) {
                $link = $node->link;
                //TODO find better way for dpcalendar
                if (str_contains($link, 'option=com_dpcalendar')) continue;

                if ($this->removeDuplicateMenus && str_contains($link, "Itemid"))
                    $link = substr($link, 0, strpos($link, 'Itemid'));
                if (empty($links[$link])) {
                    $links[$link] = true;
                    if (isset($node->subnodes))
                        $this->removingDuplicates($node->subnodes, $links, $mlinks);
                } else {
                    unset($nodes->$key);
                }
            } else {
                if (isset($node->subnodes))
                    $this->removingDuplicates($node->subnodes, $links, $mlinks);
            }
        }

        if (!$this->removeDuplicateMenus)
            return;

        // http://localhost:43000/schuweb-sitemap-dev-j4/index.php?option=com_content&view=category
        // &layout=blog&id=17&Itemid=266
        // http://localhost:43000/schuweb-sitemap-dev-j4/index.php?option=com_content&view=category
        // &id=17&Itemid=103
        // http://localhost:43000/schuweb-sitemap-dev-j4/index.php?option=com_content&view=category&id=18&Itemid=266
        // http://localhost:43000/schuweb-sitemap-dev-j4/index.php?option=com_content&view=category&id=18&Itemid=103
        foreach ($nodes as $key => $node) {
            if (!isset($node->link))
                continue;

            $matches = array();
            if (preg_match('/^.*option=(.[^&]*).*&view=(.[^&]*).*&id=(\d+).*$/', $node->link, $matches)) {
                // TODO make this extensible for plugins
                $option = $matches[1];
                $view   = $matches[2];
                $id     = $matches[3];
                if (empty($option) || empty($view) || empty($id))
                    continue;
                if (empty($mlinks[$option][$view][$id])) {
                    $mlinks[$option][$view][$id] = true;
                } else {
                    if (!isset($node->subnodes) || empty((array) ($node->subnodes))) {
                        unset($nodes->$key);
                    }
                }
            }

        }
    }

    private function getSubNodes(&$pathref, $menu, &$items)
    {
        $extensions = $this->getExtensions();

        foreach ($items as $item) { // Add each menu entry to the root tree.
            $excludeExternal = false;

            $node = new \stdClass;

            $id                        = $node->id = $item->id;
            $node->uid                 = $item->uid;
            $node->name                = $item->title; // displayed name of node
            $node->browserNav          = $item->browserNav; // how to open link
            $node->priority            = $item->priority;
            $node->changefreq          = $item->changefreq;
            $node->type                = $item->type; // menuentry-type
            $node->menutype            = $menu->menutype; // menuentry-type
            $node->home                = $item->home; // If it's a home menu entry
            $node->htmllink            = $node->link = $item->link;
            $node->option              = $item->option;
            $node->modified            = @$item->modified;
            $node->secure              = $item->params->get('secure');

            $node->params =& $item->params;

            if ($node->home == 1) {
                // Correct the URL for the home page.
                $node->htmllink = Uri::root();
            }
            switch ($item->type) {
                case 'separator':
                case 'heading':
                    $node->browserNav = 3;
                    break;
                case 'url':
                    if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $node->htmllink = $node->htmllink . '&Itemid=' . $node->id;
                    } else {
                        $excludeExternal = $this->xmlsitemap;
                    }
                    break;
                case 'alias':
                    // If this is an alias use the item id stored in the parameters to make the link.
                    $node->htmllink = 'index.php?Itemid=' . $item->params->get('aliasoptions');
                    $node->alias = true;
                    break;
                default:
                    if (!$node->home) {
                        $node->htmllink .= '&Itemid=' . $node->id;
                    }
                    break;
            }

            if ($excludeExternal || $this->proceed($node)) {

                if (!isset($node->browserNav))
                    $node->browserNav = 0;

                if ($node->browserNav != 3) {
                    $node->htmllink = Route::link('site', $node->htmllink, true, @$node->secure, $this->isXmlsitemap());
                }

                $node->name = htmlspecialchars($node->name);

                if ($node->option) {
                    //@deprecated will be removed with v6
                    $element_name = substr($node->option, 4);
                    if (!empty($extensions[$element_name])) {
                        $node->uid = $element_name;
                        $className = 'SchuWeb_Sitemap_' . $element_name;
                        //TODO use Joomla dispatcher event based instead
                        call_user_func_array(array($className, 'getTree'), array(&$this, &$node, &$extensions[$element_name]->params));
                    }
                    //end @deprecated will be removed with v6

                    //include the plugins for schuweb_sitemap
                    PluginHelper::importPlugin('schuweb_sitemap', null, true, $this->getDispatcher());

                    // Trigger the onGetTree event.
                    $this->getDispatcher()->dispatch('onGetTree', new TreePrepareEvent('onGetTree', [
                        'sitemap' => $this,
                        'node'    => $node
                    ]));
                }

                if (!isset($pathref->subnodes))
                    $pathref->subnodes = new \stdClass();

                $pathref->subnodes->$id = $node;

                $this->getSubNodes($pathref->subnodes->$id, $node, $item->items);
            }
        }
    }

    private function proceed($node)
    {
        if ($this->isExcluded($node->id, $node->uid) && !$this->canEdit) {
            return false;
        }

        return true;
    }

    /**
     * Method to get sitemap data.
     *
     * @param    integer    The id of the article.
     *
     * @return   mixed      Menu item data object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Initialize variables.
        $app = Factory::getApplication();
        $pk  = (!empty($pk)) ? $pk : (int) $this->getState('sitemap.id');

        if ($this->_item === null) {
            $this->_item = array();
        }

        if (!isset($this->_item[$pk])) {
            try {
                $query = $db->getQuery(true);

                $query->select($this->getState('item.select', 'a.*'));
                $query->from('#__schuweb_sitemap AS a');

                $query->where('a.id = ' . (int) $pk);

                // Filter by published state.
                $published = $this->getState('filter.published');
                if (is_numeric($published)) {
                    $query->where('a.state = ' . (int) $published);
                }

                // Filter by access level.
                if ($access = $this->getState('filter.access')) {
                    $user   = $app->getIdentity();
                    $groups = implode(',', $user->getAuthorisedViewLevels());
                    $query->where('a.access IN (' . $groups . ')');
                }

                $db->setQuery($query);

                $data = $db->loadObject();

                if (empty($data)) {
                    throw new Exception(Text::_('COM_SCHUWEB_SITEMAP_ERROR_SITEMAP_NOT_FOUND'));
                }

                // Check for published state if filter set.
                if (is_numeric($published) && $data->state != $published) {
                    throw new Exception(Text::_('COM_SCHUWEB_SITEMAP_ERROR_SITEMAP_NOT_FOUND'));
                }

                // Convert parameter fields to objects.
                $registry = new Registry('_default');
                if (!is_null($data) && !empty($data->attribs)) {
                    $registry->loadString($data->attribs);
                }
                $data->params = clone $this->getState('params');
                $data->params->merge($registry);
                $this->setState('params', $data->params);

                $this->removeDuplicates      = $registry->get('remove_duplicate') == 1 ? true : false;
                $this->removeDuplicateMenus  = $registry->get('remove_duplicate_menu') == 1 ? true : false;
                $this->news_publication_name = $registry->get('news_publication_name', $app->get('sitename'));

                // Convert the selections field to an array.
                $registry = new Registry('_default');
                if (!is_null($data) && !empty($data->selections)) {
                    $registry->loadString($data->selections);
                }
                $data->selections = $registry->toArray();

                // only display the Menüs which are activated
                foreach ($data->selections as $key => $selection) {
                    if (!isset($selection["enabled"]) || is_null($selection["enabled"]) || $selection["enabled"] != 1) {
                        unset($data->selections[$key]);
                    }
                }

                // Compute access permissions.
                if ($access) {
                    // If the access filter has been set, we already know this user can view.
                    $data->params->set('access-view', true);
                } else {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    $user = $app->getIdentity();
                    if (is_null($user))
                        $groups = [0 => 1];
                    else
                        $groups = $user->getAuthorisedViewLevels();

                    $data->params->set('access-view', in_array($data->access, $groups));
                }
                // TODO: Type 2 permission checks?

                $this->_item[$pk] = $data;

                $this->default     = $data->is_default;
                $this->name        = $data->alias;
            } catch (Exception $e) {
                $app->enqueueMessage(Text::_($e->getMessage()), 'error');

                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    public function getMenuTitle($menutype, $module = 'mod_menu')
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (is_null($user))
            $groups = [0 => 1];
        else
            $groups = $user->getAuthorisedViewLevels();
        $userLevelsImp = implode(',', (array) $groups);

        $query = $db->getQuery(true);
        $query->select($db->quoteName('title'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . ' = ' . $db->quote($module))
            ->where($db->quoteName('params') . ' LIKE ' . $db->quote('%menutype:' . $menutype . '%'))
            ->where($db->quoteName('access') . ' IN (' . $db->quote($userLevelsImp) . ')')
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('client_id') . ' = 0');
        // Filter by language
        if ($this->isLanguageFilter()) {
            $query->where($db->quoteName('language') . ' IN (' . $db->quote($this->getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
        }
        $query->setLimit('1');

        $db->setQuery($query);

        $module = $db->loadObject();

        if (empty($module)) {
            $query = $db->getQuery(true);
            $query->select($db->quoteName('title'))
                ->from($db->quoteName('#__menu_types'))
                ->where($db->quoteName('menutype') . ' = ' . $db->quote($menutype));
            $db->setQuery($query);

            $module = $db->loadObject();
        }

        $title = '';

        if ($module) {
            $title = $module->title;
        }

        return $title;
    }

    public function getTotalMenusNumber()
    {
        if (empty($this->totalmenusnumber))
            $this->totalmenusnumber = count($this->getMenus());
        return $this->totalmenusnumber;
    }

    private function getMenus()
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        $selections = $item->selections;

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (is_null($user))
            $groups = [0 => 1];
        else
            $groups = $user->getAuthorisedViewLevels();
        $list = array();

        $extensions = $this->getExtensions();

        foreach ($selections as $menutype => $menuOptions) {
            // Initialize variables.
            // Get the menu items as a tree.
            $query = $db->getQuery(true);
            $query->select(
                'n.id, n.title, n.alias, n.path, n.level, n.link, '
                . 'n.type, n.params, n.home, n.parent_id'
                . ',n.' . $db->quoteName('browserNav')
            );
            $query->from('#__menu AS n');
            $query->join('INNER', ' #__menu AS p ON p.lft = 0');
            $query->where('n.lft > p.lft');
            $query->where('n.lft < p.rgt');
            $query->order('n.lft');

            // Filter over the appropriate menu.
            $query->where('n.menutype = ' . $db->quote($menutype));

            // Filter over authorized access levels and publishing state.
            $query->where('n.published = 1');
            $query->where('n.access IN (' . implode(',', (array) $groups) . ')');

            // Filter by language
            if ($this->isLanguageFilter()) {
                $query->where('n.language in (' . $db->quote($this->getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
            }

            try {
                // Get the list of menu items.
                $db->setQuery($query);
                $tmpList         = $db->loadObjectList('id');
                $list[$menutype] = array();
            } catch (\RuntimeException $e) {
                $app->enqueueMessage(Text::_($e->getMessage()), 'error');
                return array();
            }

            // Set some values to make nested HTML rendering easier.
            foreach ($tmpList as $item) {
                $item->items = array();

                $params    = new Registry($item->params);
                $item->uid = 'itemid' . $item->id;

                if (preg_match('#^/?index.php.*option=(com_[^&]+)#', $item->link, $matches)) {
                    $item->option    = $matches[1];
                    $componentParams = clone (ComponentHelper::getParams($item->option));
                    $componentParams->merge($params);
                    //$params->merge($componentParams);
                    $params = $componentParams;
                } else {
                    $item->option = null;
                }

                $item->params = $params;

                if ($item->type != 'separator') {
                    $item->priority            = $menuOptions['priority'];
                    $item->changefreq          = $menuOptions['changefreq'];

                    if (!is_null($item->option)) {
                        $element_name = substr($item->option, 4);
                        //@deprecated will be removed with v6
                        if (!empty($extensions[$element_name])) {
                            $className = 'schuweb_sitemap_' . $element_name;
                            $obj       = new $className;
                            if (method_exists($obj, 'prepareMenuItem')) {
                                $obj->prepareMenuItem($item, $extensions[$element_name]->params);
                            }
                        }
                        //end @deprecated will be removed with v6

                        //include the plugins for schuweb_sitemap
                        PluginHelper::importPlugin('schuweb_sitemap', null, true, $this->getDispatcher());

                        // Trigger the onGetMenus event.
                        $this->getDispatcher()->dispatch('onGetMenus', new MenuItemPrepareEvent('onGetMenus', [
                            'menu_item' => $item,
                        ]));
                    }
                } else {
                    $item->priority            = null;
                    $item->changefreq          = null;
                }

                if ($item->parent_id > 1) {
                    $tmpList[$item->parent_id]->items[$item->id] = $item;
                } else {
                    $list[$menutype][$item->id] = $item;
                }
            }
        }
        return $list;
    }

    private function getExtensions()
    {
        if (empty($this->extensions)) {
            /** @var DatabaseDriver $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $this->extensions = array();
            // Get the menu items as a tree.
            $query = $db->getQuery(true);
            $query->select('*');
            $query->from('#__extensions AS n');
            $query->where('n.folder = \'schuweb_sitemap\'');
            $query->where('n.enabled = 1');

            // Get the list of menu items.
            $db->setQuery($query);
            $extensions = $db->loadObjectList('element');

            foreach ($extensions as $element => $extension) {
                if (file_exists(JPATH_PLUGINS . '/' . $extension->folder . '/' . $element . '/' . $element . '.php')) {
                    require_once(JPATH_PLUGINS . '/' . $extension->folder . '/' . $element . '/' . $element . '.php');
                    $params                     = new Registry($extension->params);
                    $extension->params          = $params->toArray();
                    $this->extensions[$element] = $extension;
                }
            }
        }
        return $this->extensions;
    }

    /**
     * Increment the hit counter for the sitemap.
     *
     * @param    int        Optional primary key of the sitemap to increment.
     *
     * @return   boolean    True if successful; false otherwise and internal error set.
     */
    public function hit($count)
    {
        // Initialize variables.
        $pk = (int) $this->getState('sitemap.id');

        $view = Factory::$application->input->getCmd('view', 'html');
        if ($view != 'xml' && $view != 'html') {
            return false;
        }

        $this->_db->setQuery(
            'UPDATE #__schuweb_sitemap' .
            ' SET views_' . $view . ' = views_' . $view . ' + 1, count_' . $view . ' = ' . $count . ', lastvisit_' . $view . ' = ' . Factory::getDate()->toUnix() .
            ' WHERE id = ' . (int) $pk
        );

        try {
            $this->_db->execute();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage(Text::_($e->getMessage()), 'error');
            return false;
        }

        return true;
    }

    public function getSitemapItems($view = null)
    {
        if (!isset($view)) {
            $view = Factory::getApplication()->input->getCmd('view');
        }

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $pk = (int) $this->getState('sitemap.id');

        if (self::$items !== NULL && isset(self::$items[$view])) {
            return;
        }
        $query = "select * from #__schuweb_sitemap_items where view='$view' and sitemap_id=" . $pk;
        $db->setQuery($query);
        $rows               = $db->loadObjectList();
        self::$items[$view] = array();
        foreach ($rows as $row) {
            self::$items[$view][$row->itemid]            = array();
            self::$items[$view][$row->itemid][$row->uid] = array();
            $pairs                                       = explode(';', $row->properties);
            foreach ($pairs as $pair) {
                if (strpos($pair, '=') !== FALSE) {
                    list($property, $value)                                 = explode('=', $pair);
                    self::$items[$view][$row->itemid][$row->uid][$property] = $value;
                }
            }
        }
        return self::$items;
    }

    function chageItemPropery($uid, $itemid, $view, $property, $value)
    {
        $items = $this->getSitemapItems($view);
        
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $pk    = (int) $this->getState('sitemap.id');

        $isNew = false;
        if (empty($items[$view][$itemid][$uid])) {
            $items[$view][$itemid][$uid] = array();
            $isNew                       = true;
        }
        $items[$view][$itemid][$uid][$property] = $value;
        $sep                                    = $properties = '';
        foreach ($items[$view][$itemid][$uid] as $k => $v) {
            $properties .= $sep . $k . '=' . $v;
            $sep        = ';';
        }
        if (!$isNew) {
            $query = 'UPDATE #__schuweb_sitemap_items SET properties=\'' . $db->escape($properties) . "' where uid='" . $db->escape($uid) . "' and itemid=$itemid and view='$view' and sitemap_id=" . $pk;
        } else {
            $query = 'INSERT #__schuweb_sitemap_items (uid,itemid,view,sitemap_id,properties) values ( \'' . $db->escape($uid) . "',$itemid,'$view',$pk,'" . $db->escape($properties) . "')";
        }
        $db->setQuery($query);

        if ($db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    function toggleItem($uid, $itemid)
    {
        $sitemap = $this->getItem();

        $excludedItems = $this->getExcludedItems();
        if (isset($excludedItems[$itemid])) {
            $excludedItems[$itemid] = (array) $excludedItems[$itemid];
        }
        if (!$this->isExcluded($itemid, $uid)) {
            $excludedItems[$itemid][] = $uid;
            $state                    = 0;
        } else {
            if (is_array($excludedItems[$itemid]) && count($excludedItems[$itemid])) {
                $excludedItems[$itemid] = array_filter($excludedItems[$itemid], function ($v) use ($uid) {
                    return ($v != $uid);
                });
            } else {
                unset($excludedItems[$itemid]);
            }
            $state = 1;
        }

        $registry = new Registry('_default');
        $registry->loadArray($excludedItems);
        $str = $registry->toString();

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        $query = "UPDATE #__schuweb_sitemap set excluded_items='" . $db->escape($str) . "' where id=" . $sitemap->id;
        $db->setQuery($query);
        $db->execute();
        return $state;
    }

    public function getExcludedItems()
    {
        static $_excluded_items;
        if (!isset($_excluded_items)) {
            $_excluded_items = [];
            $registry        = new Registry('_default');
            
            $item = $this->getItem();
            if ($item) {
               $excluded_items = $item->excluded_items;
                if (!empty($excluded_items)) {
                    $registry->loadString($excluded_items);
                }
            }
            
            $_excluded_items = $registry->toArray();
        }
        return $_excluded_items;
    }

    public function isExcluded($itemid, $uid)
    {
        $excludedItems = $this->getExcludedItems();
        $items         = NULL;
        if (!empty($excludedItems[$itemid])) {
            if (is_object($excludedItems[$itemid])) {
                $excludedItems[$itemid] = (array) $excludedItems[$itemid];
            }
            $items =& $excludedItems[$itemid];
        }
        if (!$items) {
            return false;
        }
        return (in_array($uid, $items));
    }

    /**
     * Set ist language filter active
     */
    public function setLanguageFilter(bool $languageFilter): self
    {
        $this->languageFilter = $languageFilter;

        return $this;
    }

    /**
     * Get ist language filter active
     */
    public function isLanguageFilter(): bool
    {
        if (!isset($this->languageFilter))
            $this->languageFilter = Factory::getApplication()->getLanguageFilter();
        return $this->languageFilter;
    }

    /**
     * Get the value of language
     */
    private function getLanguage(): Language
    {
        if (!isset($this->language))
            $this->language = Factory::getApplication()->getLanguage();
        return $this->language;
    }

    /**
     * Set the value of language
     */
    public function setLanguage(Language $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set the value of xmlsitemap
     */
    public function setXmlsitemap(bool $xmlsitemap): self
    {
        $this->xmlsitemap = $xmlsitemap;

        return $this;
    }

    /**
     * Get the value of xmlsitemap
     */
    public function isXmlsitemap(): bool
    {
        return $this->xmlsitemap;
    }

    /**
     * Get the value of default
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * Get the value of imagesitemap
     */
    public function isImagesitemap(): bool
    {
        return $this->imagesitemap;
    }

    /**
     * Set the value of imagesitemap
     */
    public function setImagesitemap(bool $imagesitemap): self
    {
        $this->imagesitemap = $imagesitemap;

        return $this;
    }

    /**
     * Get the value of newssitemap
     */
    public function isNewssitemap(): bool
    {
        return $this->newssitemap;
    }

    /**
     * Set the value of newssitemap
     */
    public function setNewssitemap(bool $newssitemap): self
    {
        $this->newssitemap = $newssitemap;

        return $this;
    }

    /**
     * Get the value of news_publication_name
     */
    public function getNewsPublicationName(): string
    {
        return $this->news_publication_name;
    }
}