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
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

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

        $toolbar->addNew('sitemap.add');

        ToolBarHelper::title(Text::_('SCHUWEB_SITEMAP_SITEMAPS_TITLE'), 'tree-2');

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();

        $childBar->publish('sitemaps.publish')->listCheck(true);
        $childBar->unpublish('sitemaps.unpublish')->listCheck(true);
        $childBar->standardButton('featured', 'SCHUWEB_SITEMAP_TOOLBAR_SET_DEFAULT', 'sitemaps.setdefault')->listCheck(true);

        if ($state->get('filter.published') == ContentComponent::CONDITION_TRASHED) {
            $childBar->delete('sitemaps.delete')->listCheck(true);
        } else {
            $childBar->trash('sitemaps.trash')->listCheck(true);
        }

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
    }
}