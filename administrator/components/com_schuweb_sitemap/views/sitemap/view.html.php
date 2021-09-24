<?php
/**
 * @version             sw.build.version
 * @copyright           Copyright (C) 2016 - 2021 Sven Schultschik. All rights reserved.
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Sven Schultschik (https://extensions.schultschik.com)
 */
// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * @package    SchuWeb Sitemap
 * @subpackage com_schuweb_sitemap
 */
class SchuWeb_SitemapViewSitemap extends JViewLegacy
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

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }
        
        // Convert dates from UTC
        $offset = $app->get('offset');
        if (intval($this->item->created)) {
            $this->item->created = JHtml::date($this->item->created, '%Y-%m-%d %H-%M-%S', $offset);
        }

        $this->handleMenus();

        $this->addToolbar();

        parent::display($tpl);
        $app->input->set('hidemainmenu', true);
    }

    private function handleMenus()
    {
        $menus = $this->get('Menus');
        // remove not anymore existing menus (menutypes) from selection
        foreach ($this->item->selections as $menutype => $options) {
            if (!isset($menus[$menutype])) {
                unset($this->item->selections[$menutype]);
            }
        }
        foreach ($menus as $menu) {
            if (isset($this->item->selections[$menu->menutype])) {
                $this->item->selections[$menu->menutype]['selected'] = true;
                $this->item->selections[$menu->menutype]['title'] = $menu->title;
                $this->item->selections[$menu->menutype]['menutype'] = $menu->menutype;
            } else {
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

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        JHTML::script('mootree.js', 'media/system/js/');
        JHTML::stylesheet('mootree.css', 'media/system/css/');

        $this->loadTemplate('class');

        parent::display($tpl);
    }

    function navigatorLinks($tpl = null)
    {
        $input = JFactory::$application->input;

        require_once(JPATH_COMPONENT_SITE . '/helpers/schuweb_sitemap.php');
        $link = urldecode($input->getVar('link', ''));
        $name = $input->getCmd('e_name', '');
        $Itemid = $input->getInt('Itemid');

        $this->item = $this->get('Item');
        $this->state = $this->get('State');
        $menuItems = SchuWeb_SitemapHelper::getMenuItems($this->item->selections);
        $extensions = SchuWeb_SitemapHelper::getExtensions();

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
                $node->ordering = $this->item->selections->$menutype->ordering;
                $node->priority = $this->item->selections->$menutype->priority;
                $node->changefreq = $this->item->selections->$menutype->changefreq;
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
                $items = JSite::getMenu();
                $node = &$items->getItem($Itemid);
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
	function addToolbar()
	{
		$isNew = ($this->item->id == 0);

		if (version_compare(JVERSION, '4', 'lt'))
		{
			JToolBarHelper::title(JText::_('SCHUWEB_SITEMAP_PAGE_' . ($isNew ? 'ADD_SITEMAP' : 'EDIT_SITEMAP')), 'article-add.png');
			JToolBarHelper::apply('sitemap.apply', 'JTOOLBAR_APPLY');
			JToolBarHelper::save('sitemap.save', 'JTOOLBAR_SAVE');
			JToolBarHelper::save2new('sitemap.save2new');
			if (!$isNew)
			{
				JToolBarHelper::save2copy('sitemap.save2copy');
			}
			JToolBarHelper::cancel('sitemap.cancel', 'JTOOLBAR_CLOSE');
		}
		else
		{
			ToolBarHelper::title(Text::_('SCHUWEB_SITEMAP_PAGE_' . ($isNew ? 'ADD_SITEMAP' : 'EDIT_SITEMAP')), 'sitemap fa-sitemap');
			ToolbarHelper::apply('sitemap.apply', 'JTOOLBAR_APPLY');

			$toolbarButtons[] = ['save', 'sitemap.save'];
			$toolbarButtons[] = ['save2new', 'sitemap.save2new'];
			if (!$isNew)
			{
				$toolbarButtons[] = ['save2copy', 'sitemap.save2copy'];
			}
			ToolbarHelper::saveGroup(
				$toolbarButtons,
				'btn-success'
			);
			if ($isNew)
			{
				ToolbarHelper::cancel('sitemap.cancel');
			}
			else
			{
				ToolbarHelper::cancel('sitemap.cancel', 'JTOOLBAR_CLOSE');
			}
		}
	}
}
