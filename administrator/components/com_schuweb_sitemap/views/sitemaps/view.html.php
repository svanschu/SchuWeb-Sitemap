<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * @package     SchuWeb Sitemap
 * @subpackage  com_schuweb_sitemap
 * @since       2.0
 */
class SchuWeb_SitemapViewSitemaps extends JViewLegacy
{
    protected $state;
    protected $items;
    protected $pagination;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {
	    if (version_compare(JVERSION, '4', 'lt'))
	    {
		    if ($this->getLayout() !== 'modal')
		    {
			    JHtmlSidebar::addEntry(JText::_('SCHUWEB_SITEMAP_Submenu_Sitemaps'), 'index.php?option=com_schuweb_sitemap', true);
			    JHtmlSidebar::addEntry(JText::_('SCHUWEB_SITEMAP_Submenu_Extensions'), 'index.php?option=com_plugins&view=plugins&filter_folder=schuweb_sitemap');
		    }
	    }

	    $this->filterForm    = $this->get('FilterForm');
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        $message = $this->get('ExtensionsMessage');
        if ($message) {
            JFactory::getApplication()->enqueueMessage($message);
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JFactory::$application->enqueueMessage(implode("\n", $errors), 'error');
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
        $doc = JFactory::getDocument();

        JToolBarHelper::addNew('sitemap.add');
        JToolBarHelper::custom('sitemap.edit', 'edit.png', 'edit_f2.png', 'JTOOLBAR_EDIT', true);

       // $doc->addStyleDeclaration('.icon-48-sitemap {background-image: url(components/com_schuweb_sitemap/images/sitemap-icon.png);}');
        JToolBarHelper::title(JText::_('SCHUWEB_SITEMAP_SITEMAPS_TITLE'), 'sitemap.png');
        JToolBarHelper::custom('sitemaps.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_Publish', true);
        JToolBarHelper::custom('sitemaps.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);

        JToolBarHelper::custom('sitemaps.setdefault', 'featured.png', 'featured_f2.png', 'SCHUWEB_SITEMAP_TOOLBAR_SET_DEFAULT', true);

        if ($state->get('filter.published') == -2) {
            JToolBarHelper::deleteList('', 'sitemaps.delete', 'JTOOLBAR_DELETE');
        } else {
            JToolBarHelper::trash('sitemaps.trash', 'JTOOLBAR_TRASH');
        }


        if (class_exists('JHtmlSidebar')) {
            JHtmlSidebar::addFilter(
                JText::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
            );

            JHtmlSidebar::addFilter(
                JText::_('JOPTION_SELECT_ACCESS'),
                'filter_access',
                JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
            );

            $this->sidebar = JHtmlSidebar::render();
        }
    }
}
