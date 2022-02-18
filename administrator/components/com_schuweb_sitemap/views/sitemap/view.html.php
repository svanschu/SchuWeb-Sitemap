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
    protected mixed $form;
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
     * Display the toolbar
     *
     * @access    private
     */
	function addToolbar()
	{
        $isNew = ($this->item->id == 0);


        ToolBarHelper::title(Text::_('SCHUWEB_SITEMAP_PAGE_' . ($isNew ? 'ADD_SITEMAP' : 'EDIT_SITEMAP')), 'sitemap fa-sitemap');
        ToolbarHelper::apply('sitemap.apply', 'JTOOLBAR_APPLY');

        $toolbarButtons[] = ['save', 'sitemap.save'];
        $toolbarButtons[] = ['save2new', 'sitemap.save2new'];
        if (!$isNew) {
            $toolbarButtons[] = ['save2copy', 'sitemap.save2copy'];
        }
        ToolbarHelper::saveGroup(
            $toolbarButtons,
            'btn-success'
        );
        if ($isNew) {
            ToolbarHelper::cancel('sitemap.cancel');
        } else {
            ToolbarHelper::cancel('sitemap.cancel', 'JTOOLBAR_CLOSE');
        }
	}
}
