<?php
/**
 * @version             $Id$
 * @copyright           Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Guillermo Vargas (guille@vargas.co.cr)
 */
// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * @package    Xmap
 * @subpackage com_schuweb_sitemap
 */
class Schuweb_SitemapViewSitemap extends JViewLegacy
{

    protected $item;
    protected $list;
    protected $form;
    protected $state;

    /**
     * Display the view
     *
     * @access    public
     */
    function display($tpl = null)
    {
        $app = JFactory::getApplication();
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        $version = new JVersion;

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        JHTML::stylesheet('administrator/components/com_schuweb_sitemap/css/xmap.css');
        // Convert dates from UTC
        $offset = $app->getCfg('offset');
        if (intval($this->item->created)) {
            $this->item->created = JHtml::date($this->item->created, '%Y-%m-%d %H-%M-%S', $offset);
        }

        $this->handleMenues();

        $this->_setToolbar();

        parent::display($tpl);
        JRequest::setVar('hidemainmenu', true);
    }

    protected function handleMenues()
    {
        $menues = $this->get('Menues');
        // remove non existing menutypes from selection
        foreach ($this->item->selections as $menutype => $options)
        {
            if (!isset($menues[$menutype]))
            {
                unset($this->item->selections[$menutype]);
            }
        }
        foreach ($menues as $menu)
        {
            if (isset($this->item->selections[$menu->menutype]))
            {
                $this->item->selections[$menu->menutype]['selected'] = true;
                $this->item->selections[$menu->menutype]['title'] = $menu->title;
                $this->item->selections[$menu->menutype]['menutype'] = $menu->menutype;
            } else
            {
                $this->item->selections[$menu->menutype] = (array)$menu;
                $this->item->selections[$menu->menutype]['selected'] = false;
                $this->item->selections[$menu->menutype]['priority'] = 0.5;
                $this->item->selections[$menu->menutype]['changefreq'] = 'weekly';
            }
        }
    }
    /**
     * Display the view
     *
     * @access    public
     */
    function navigator($tpl = null)
    {
        require_once(JPATH_COMPONENT_SITE . '/helpers/schuweb_sitemap.php');
        $app = JFactory::getApplication();
        $this->state = $this->get('State');
        $this->item = $this->get('Item');

        # $menuItems = XmapHelper::getMenuItems($item->selections);
        # $extensions = XmapHelper::getExtensions();
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        JHTML::script('mootree.js', 'media/system/js/');
        JHTML::stylesheet('mootree.css', 'media/system/css/');

        $this->loadTemplate('class');
        $displayer = new XmapNavigatorDisplayer($this->state->params, $this->item);

        parent::display($tpl);
    }

    function navigatorLinks($tpl = null)
    {

        require_once(JPATH_COMPONENT_SITE . '/helpers/schuweb_sitemap.php');
        $link = urldecode(JRequest::getVar('link', ''));
        $name = JRequest::getCmd('e_name', '');
        $Itemid = JRequest::getInt('Itemid');

        $this->item = $this->get('Item');
        $this->state = $this->get('State');
        $menuItems = Schuweb_SitemapHelper::getMenuItems($this->item->selections);
        $extensions = Schuweb_SitemapHelper::getExtensions();

        $this->loadTemplate('class');
        $nav = new XmapNavigatorDisplayer($this->state->params, $this->item);
        $nav->setExtensions($extensions);

        $this->list = array();
        // Show the menu list
        if (!$link && !$Itemid) {
            foreach ($menuItems as $menutype => &$menu) {
                $menu = new stdclass();
                #$menu->id = 0;
                #$menu->menutype = $menutype;

                $node = new stdClass;
                $node->uid = "menu-" . $menutype;
                $node->menutype = $menutype;
                $node->ordering = $item->selections->$menutype->ordering;
                $node->priority = $item->selections->$menutype->priority;
                $node->changefreq = $item->selections->$menutype->changefreq;
                $node->browserNav = 3;
                $node->type = 'separator';
                if (!$node->name = $nav->getMenuTitle($menutype, @$menu->module)) {
                    $node->name = $menutype;
                }
                $node->link = '-menu-' . $menutype;
                $node->expandible = true;
                $node->selectable = false;
                //$node->name = $this->getMenuTitle($menutype,@$menu->module);    // get the mod_mainmenu title from modules table

                $this->list[] = $node;
            }
        } else {
            $parent = new stdClass;
            if ($Itemid) {
                // Expand a menu Item
                $items = &JSite::getMenu();
                $node = & $items->getItem($Itemid);
                if (isset($menuItems[$node->menutype])) {
                    $parent->name = $node->title;
                    $parent->id = $node->id;
                    $parent->uid = 'itemid' . $node->id;
                    $parent->link = $link;
                    $parent->type = $node->type;
                    $parent->browserNav = $node->browserNav;
                    $parent->priority = $this->item->selections->{$node->menutype}->priority;
                    $parent->changefreq = $this->item->selections->{$node->menutype}->changefreq;
                    $parent->menutype = $node->menutype;
                    $parent->selectable = false;
                    $parent->expandible = true;
                }
            } else {
                $parent->id = 1;
                $parent->link = $link;
            }
            $this->list = $nav->expandLink($parent);
        }

        parent::display('links');
        exit;
    }

    /**
     * Display the toolbar
     *
     * @access    private
     */
    function _setToolbar()
    {
        $user = JFactory::getUser();
        $isNew = ($this->item->id == 0);

        JToolBarHelper::title(JText::_('SCHUWEB_SITEMAP_PAGE_' . ($isNew ? 'ADD_SITEMAP' : 'EDIT_SITEMAP')), 'article-add.png');

        JToolBarHelper::apply('sitemap.apply', 'JTOOLBAR_APPLY');
        JToolBarHelper::save('sitemap.save', 'JTOOLBAR_SAVE');
        JToolBarHelper::save2new('sitemap.save2new');
        if (!$isNew) {
            JToolBarHelper::save2copy('sitemap.save2copy');
        }
        JToolBarHelper::cancel('sitemap.cancel', 'JTOOLBAR_CLOSE');
    }

}
