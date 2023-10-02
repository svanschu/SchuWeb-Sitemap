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

namespace SchuWeb\Component\Sitemap\Administrator\View\Sitemaps;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\Sidebar;

/**
 * Main "SchuWeb Sitemap" Admin View
 */
class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $items;
    protected $pagination;

    /**
     * Display the main "SchuWeb Sitemap" view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null)
    {
        $this->filterForm = $this->get('FilterForm');

        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        $message = $this->get('ExtensionsMessage');
        if ($message) {
            Factory::getApplication()->enqueueMessage($message);
        }

        $message = $this->get('NotInstalledMessage');
        if ($message) {
            Factory::getApplication()->enqueueMessage($message);
        }

            // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            Factory::$application->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

        parent::display($tpl);
    }

    /**
     * Display the toolbar
     *
     * @access      private
     */
    protected function addToolbar()
    {
        $state = $this->get('State');

        $toolbar = Toolbar::getInstance();

        ToolBarHelper::addNew('sitemap.add');
        ToolBarHelper::custom('sitemap.edit', 'edit.png', 'edit_f2.png', 'JTOOLBAR_EDIT', true);

       // $doc->addStyleDeclaration('.icon-48-sitemap {background-image: url(components/com_schuweb_sitemap/images/sitemap-icon.png);}');
        ToolBarHelper::title(Text::_('SCHUWEB_SITEMAP_SITEMAPS_TITLE'), 'sitemap.png');
        ToolBarHelper::custom('sitemaps.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_Publish', true);
        ToolBarHelper::custom('sitemaps.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
        ToolBarHelper::custom('sitemaps.setdefault', 'featured.png', 'featured_f2.png', 'SCHUWEB_SITEMAP_TOOLBAR_SET_DEFAULT', true);

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('SCHUWEB_SITEMAP_TOOLBAR_CREATE_XML')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();

        $childBar->standardButton('refresh', 'SCHUWEB_SITEMAP_TOOLBAR_CREATE_SITEMAP_XML', 'sitemaps.createxml')
            ->listCheck(true);
        $childBar->standardButton('refresh', 'SCHUWEB_SITEMAP_TOOLBAR_CREATE_NEWS_XML', 'sitemaps.createxmlnews')
            ->listCheck(true);
        $childBar->standardButton('refresh', 'SCHUWEB_SITEMAP_TOOLBAR_CREATE_IMAGES_XML', 'sitemaps.createxmlimages')
            ->listCheck(true);


        if ($state->get('filter.published') == -2) {
            ToolBarHelper::deleteList('', 'sitemaps.delete');
        } else {
            ToolBarHelper::trash('sitemaps.trash');
        }


        if (class_exists('JHtmlSidebar')) {
            \JHtmlSidebar::addFilter(
                Text::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
            );

            \JHtmlSidebar::addFilter(
                Text::_('JOPTION_SELECT_ACCESS'),
                'filter_access',
                HTMLHelper::_('select.options', HTMLHelper::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
            );

            $this->sidebar = \JHtmlSidebar::render();
        }
    }
}