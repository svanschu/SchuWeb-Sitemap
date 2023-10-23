<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_schuweb_sitemap
 * 
 * @version     sw.build.version
 * @copyright   Copyright (C) 2023 Sven Schultschik. All rights reserved
 * @license     GNU General Public License version 3; see LICENSE
 * @author      Sven Schultschik (extensions@schultschik.de)
 */

namespace SchuWeb\Component\Sitemap\Site\Model;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Text;

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
     * @since __BUMP_VERSION__
     */
    private bool $default;

    /**
     * The nodes object details
     *
     * @var    \stdClass
     * @since  __BUMP_VERSION__
     */
    private $nodes;

    /**
     * The total number of menus
     *
     * @var    int
     * @since  __BUMP_VERSION__
     */
    private $totalmenusnumber;

    /**
     * Ist language filter active
     * 
     * @var bool
     * @since __BUMP_VERSION__
     */
    private bool $languageFilter;

    /**
     * Language object
     * 
     * @var Language
     * @since __BUMP_VERSION__
     */
    private Language $language;


    /**
     * Do we generate an XML?
     * 
     * @var bool
     * @since __BUMP_VERSION__
     */
    private bool $xmlsitemap = false;

    /**
     *
     * @var bool Indicates if this is a google image sitemap or not
     *
     * @since __BUMP_VERSION__
     */
    private bool $imagesitemap = false;

    /**
     *
     * @var bool Indicates if this is a google news sitemap or not
     *
     * @since __BUMP_VERSION__
     */
    private bool $newssitemap = false;

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
        if (!$pk) {
            $db = $this->getDbo();
            $query = $db->getQuery(true);
            $query->select('id')->from('#__schuweb_sitemap')->where('is_default=1');
            $db->setQuery($query);
            $pk = $db->loadResult();
        }

        $this->setState('sitemap.id', $pk);

        $offset = $jinput->getInt('limitstart');
        $this->setState('list.offset', $offset);

        // Load the parameters.
        $params = $app->getParams();
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

            $node->uid = "menu-" . $menutype;
            $node->menutype = $menutype;
            $node->priority = null;
            $node->changefreq = null;
            $node->browserNav = 3;
            $node->type = 'separator';

            $node->name = $this->getMenuTitle($menutype); // Get the name of this menu

            $this->nodes->$menutype = $node;

            $this->getSubNodes($this->nodes->$menutype, $node, $items);
        }

        return $this->nodes;
    }

    private function getSubNodes(&$pathref, $menu, &$items)
    {
        $extensions = $this->getExtensions();

        foreach ($items as $item) { // Add each menu entry to the root tree.
            $excludeExternal = false;

            $node = new \stdClass;

            $id = $node->id = $item->id;
            $node->uid = $item->uid;
            $node->name = $item->title; // displayed name of node
            $node->browserNav = $item->browserNav; // how to open link
            $node->priority = $item->priority;
            $node->changefreq = $item->changefreq;
            $node->type = $item->type; // menuentry-type
            $node->menutype = $menu->menutype; // menuentry-type
            $node->home = $item->home; // If it's a home menu entry
            $node->htmllink = $node->link = $item->link;
            $node->option = $item->option;
            $node->modified = @$item->modified;
            $node->secure = $item->params->get('secure');
            $node->lastmod = $item->lastmod;
            $node->xmlInsertChangeFreq = $item->xmlInsertChangeFreq;
            $node->xmlInsertPriority = $item->xmlInsertPriority;

            $node->params =& $item->params;

            if ($node->home == 1) {
                // Correct the URL for the home page.
                $node->htmllink = Uri::base();
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
                    $node->htmllink = Route::link('site', $node->htmllink, true, @$node->secure);
                }

                $node->name = htmlspecialchars($node->name);

                if ($node->option) {
                    $element_name = substr($node->option, 4);
                    if (!empty($extensions[$element_name])) {
                        $node->uid = $element_name;
                        $className = 'SchuWeb_Sitemap_' . $element_name;
                        //TODO use Joomla dispatcher event based instead
                        call_user_func_array(array($className, 'getTree'), array(&$this, &$node, &$extensions[$element_name]->params));
                    }
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
        // Initialize variables.
        $db = $this->getDbo();
        $app = Factory::getApplication();
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('sitemap.id');

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
                    $user = $app->getIdentity();
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
                $registry->loadString($data->attribs);
                $data->params = clone $this->getState('params');
                $data->params->merge($registry);


                // Convert the selections field to an array.
                $registry = new Registry('_default');
                $registry->loadString($data->selections);
                $data->selections = $registry->toArray();

                $lastmod = $data->params->get('xmlLastMod');
                $xmlInsertChangeFreq = $data->params->get('xmlInsertChangeFreq');
                $xmlInsertPriority = $data->params->get('xmlInsertPriority');
                // only display the Menüs which are activated
                foreach ($data->selections as $key => $selection) {
                    if (!isset($selection["enabled"]) || is_null($selection["enabled"]) || $selection["enabled"] != 1) {
                        unset($data->selections[$key]);
                    } else {
                        $data->selections[$key]["lastmod"] = $lastmod;
                        $data->selections[$key]["xmlInsertChangeFreq"] = $xmlInsertChangeFreq;
                        $data->selections[$key]["xmlInsertPriority"] = $xmlInsertPriority;
                    }
                }

                // Compute access permissions.
                if ($access) {
                    // If the access filter has been set, we already know this user can view.
                    $data->params->set('access-view', true);
                } else {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    $user = $app->getIdentity();
                    $groups = $user->getAuthorisedViewLevels();

                    $data->params->set('access-view', in_array($data->access, $groups));
                }
                // TODO: Type 2 permission checks?

                $this->_item[$pk] = $data;

                $this->default = $data->is_default;
                $this->name = $data->alias;
            } catch (Exception $e) {
                $app->enqueueMessage(Text::_($e->getMessage()), 'error');

                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }

    public function getMenuTitle($menutype, $module = 'mod_menu')
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $db = $this->getDbo();

        $userLevelsImp = implode(',', (array) $user->getAuthorisedViewLevels());

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

        $db = $this->getDBO();
        $app = Factory::getApplication();
        $user = $app->getIdentity();
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
            $query->where('n.access IN (' . implode(',', (array) $user->getAuthorisedViewLevels()) . ')');

            // Filter by language
            if ($this->isLanguageFilter()) {
                $query->where('n.language in (' . $db->quote($this->getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
            }

            try {
                // Get the list of menu items.
                $db->setQuery($query);
                $tmpList = $db->loadObjectList('id');
                $list[$menutype] = array();
            } catch (\RuntimeException $e) {
                $app->enqueueMessage(Text::_($e->getMessage()), 'error');
                return array();
            }

            // Set some values to make nested HTML rendering easier.
            foreach ($tmpList as $item) {
                $item->items = array();

                $params = new Registry($item->params);
                $item->uid = 'itemid' . $item->id;

                if (preg_match('#^/?index.php.*option=(com_[^&]+)#', $item->link, $matches)) {
                    $item->option = $matches[1];
                    $componentParams = clone (ComponentHelper::getParams($item->option));
                    $componentParams->merge($params);
                    //$params->merge($componentParams);
                    $params = $componentParams;
                } else {
                    $item->option = null;
                }

                $item->params = $params;

                if ($item->type != 'separator') {
                    $item->priority = $menuOptions['priority'];
                    $item->changefreq = $menuOptions['changefreq'];
                    $item->lastmod = $menuOptions['lastmod'];
                    $item->xmlInsertChangeFreq = $menuOptions['xmlInsertChangeFreq'];
                    $item->xmlInsertPriority = $menuOptions['xmlInsertPriority'];

                    $element_name = substr($item->option, 4);
                    if (!empty($extensions[$element_name])) {
                        $className = 'schuweb_sitemap_' . $element_name;
                        $obj = new $className;
                        if (method_exists($obj, 'prepareMenuItem')) {
                            $obj->prepareMenuItem($item, $extensions[$element_name]->params);
                        }
                    }
                } else {
                    $item->priority = null;
                    $item->changefreq = null;
                    $item->lastmod = null;
                    $item->xmlInsertChangeFreq = null;
                    $item->xmlInsertPriority = null;
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
            $db = $this->getDBO();

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
                    $params = new Registry($extension->params);
                    $extension->params = $params->toArray();
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
        } catch (RuntimeException $e) {
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

        $db = $this->getDBO();
        $pk = (int) $this->getState('sitemap.id');

        if (self::$items !== NULL && isset(self::$items[$view])) {
            return;
        }
        $query = "select * from #__schuweb_sitemap_items where view='$view' and sitemap_id=" . $pk;
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        self::$items[$view] = array();
        foreach ($rows as $row) {
            self::$items[$view][$row->itemid] = array();
            self::$items[$view][$row->itemid][$row->uid] = array();
            $pairs = explode(';', $row->properties);
            foreach ($pairs as $pair) {
                if (strpos($pair, '=') !== FALSE) {
                    list($property, $value) = explode('=', $pair);
                    self::$items[$view][$row->itemid][$row->uid][$property] = $value;
                }
            }
        }
        return self::$items;
    }

    function chageItemPropery($uid, $itemid, $view, $property, $value)
    {
        $items = $this->getSitemapItems($view);
        $db = $this->getDBO();
        $pk = (int) $this->getState('sitemap.id');

        $isNew = false;
        if (empty($items[$view][$itemid][$uid])) {
            $items[$view][$itemid][$uid] = array();
            $isNew = true;
        }
        $items[$view][$itemid][$uid][$property] = $value;
        $sep = $properties = '';
        foreach ($items[$view][$itemid][$uid] as $k => $v) {
            $properties .= $sep . $k . '=' . $v;
            $sep = ';';
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
            $state = 0;
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

        $db = $this->getDBO();
        $query = "UPDATE #__schuweb_sitemap set excluded_items='" . $db->escape($str) . "' where id=" . $sitemap->id;
        $db->setQuery($query);
        $db->execute();
        return $state;
    }

    public function getExcludedItems()
    {
        static $_excluded_items;
        if (!isset($_excluded_items)) {
            $_excluded_items = array();
            $registry = new Registry('_default');
            $registry->loadString($this->getItem()->excluded_items);
            $_excluded_items = $registry->toArray();
        }
        return $_excluded_items;
    }

    public function isExcluded($itemid, $uid)
    {
        $excludedItems = $this->getExcludedItems();
        $items = NULL;
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
}