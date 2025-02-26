<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Administrator\View\Sitemap;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;


/**
 * View to edit a sitemap.
 *
 * @since  5.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
	 * The \JForm object
	 *
	 * @var  \JForm
	 */
    protected $form;

    /**
	 * The active item
	 *
	 * @var  object
	 */
    protected $item;

    protected $list;
    
    protected $state;

    /**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
     * 
     * @since  5.0.0
	 */
    function display($tpl = null)
    {
        $app = Factory::getApplication();
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
            $this->item->created = HTMLHelper::date($this->item->created, '%Y-%m-%d %H-%M-%S', $offset);
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
                $this->item->selections[$menu->menutype]['order'] = isset($menu->order) ?: 0;
            } else {
                $this->item->selections[$menu->menutype] = (array)$menu;
                $this->item->selections[$menu->menutype]['selected'] = false;
                $this->item->selections[$menu->menutype]['priority'] = 0.5;
                $this->item->selections[$menu->menutype]['changefreq'] = 'weekly';
                $this->item->selections[$menu->menutype]['order'] = 0;
            }
        }
    }

    /**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   5.0.0
	 */
	function addToolbar()
	{
        $isNew = ($this->item->id == 0);


        ToolBarHelper::title(Text::_('SCHUWEB_SITEMAP_PAGE_' . ($isNew ? 'ADD_SITEMAP' : 'EDIT_SITEMAP')), 'sitemap fa-sitemap');
        ToolbarHelper::apply('sitemap.apply');

        $toolbarButtons[] = ['save', 'sitemap.save'];
        $toolbarButtons[] = ['save2new', 'sitemap.save2new'];
        if (!$isNew) {
            $toolbarButtons[] = ['save2copy', 'sitemap.save2copy'];
        }
        ToolbarHelper::saveGroup(
            $toolbarButtons
        );
        if ($isNew) {
            ToolbarHelper::cancel('sitemap.cancel');
        } else {
            ToolbarHelper::cancel('sitemap.cancel', 'JTOOLBAR_CLOSE');
        }
	}
}
