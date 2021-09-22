<?php

/**
 * @version          sw.build.version
 * @copyright        Copyright (C) 2005 - 2009 Joomla! Vargas. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 * @author           Guillermo Vargas (guille@vargas.co.cr)
 */
// No direct access
defined( '_JEXEC' ) or die();

jimport('joomla.application.component.view');

/**
 * HTML Site map View class for the SchuWeb_Sitemap component
 *
 * @package         SchuWeb_Sitemap
 * @subpackage      com_schuweb_sitemap
 * @since           2.0
 */
class SchuWeb_SitemapViewHtml extends JViewLegacy
{

    protected $state;

    function display($tpl = null)
    {
        // Initialise variables.
        $this->app = JFactory::getApplication();
        $jinput = $this->app->input;
        $this->user = JFactory::getUser();

        // Get model data.
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->items = $this->get('Items');

        $this->canEdit = JFactory::getUser()->authorise('core.admin', 'com_schuweb_sitemap');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JFactory::$application->enqueueMessage(implode("\n", $errors), 'warning');
            return false;
        }

        $this->extensions = $this->get('Extensions');
        // Add router helpers.
        $this->item->slug = $this->item->alias ? ($this->item->id . ':' . $this->item->alias) : $this->item->id;

        $this->item->rlink = JRoute::_('index.php?option=com_schuweb_sitemap&view=html&id=' . $this->item->slug);

        // Create a shortcut to the paramemters.
        $params = &$this->state->params;
        $offset = $this->state->get('page.offset');

        // If a guest user, they may be able to log in to view the full article
        // TODO: Does this satisfy the show not auth setting?
        if (!$this->item->params->get('access-view')) {
            if ($this->user->get('guest')) {
                // Redirect to login
                $uri = JUri::getInstance();
                $this->app->redirect('index.php?option=com_users&view=login&return=' . base64_encode($uri), JText::_('SchuWeb_Sitemap_Error_Login_to_view_sitemap')
                );
                return;
            } else {
                $this->app->enqueueMessage(JText::_('SchuWeb_Sitemap_Error_Not_auth'), 'warning');
                return;
            }
        }

        // Override the layout.
        if ($layout = $params->get('layout')) {
            $this->setLayout($layout);
        }

        // Load the class used to display the sitemap
        $this->loadTemplate('class');
        $this->displayer = new SchuWeb_SitemapHtmlDisplayer($params, $this->item);

        $this->displayer->setJView($this);
        $this->displayer->canEdit = $this->canEdit;

        $this->_prepareDocument();
        parent::display($tpl);

        $model = $this->getModel();
        $model->hit($this->displayer->getCount());
    }

    /**
     * Prepares the document
     */
    protected function _prepareDocument()
    {
        $app = JFactory::getApplication();
        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title, we need to get it from the menu item itself
        if ($menu = $menus->getActive()) {
            if (isset($menu->query['view']) && isset($menu->query['id'])) {
            
                if ($menu->query['view'] == 'html' && $menu->query['id'] == $this->item->id) {
                    $title = $menu->title;
                    if (empty($title)) {
                        $title = $app->get('sitename');
                    } else if ($app->get('sitename_pagetitles', 0) == 1) {
                        $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
                    } else if ($app->get('sitename_pagetitles', 0) == 2) {
                        $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
                    }
                    // set meta description and keywords from menu item's params
                    $params = new JRegistry();
                    $params->loadString($menu->getParams());
                    $this->document->setDescription($params->get('menu-meta_description'));
                    $this->document->setMetadata('keywords', $params->get('menu-meta_keywords'));
                }
            }
        }
        $this->document->setTitle($title);

        if ($app->get('MetaTitle') == '1') {
            $this->document->setMetaData('title', $title);
        }
    }

}
