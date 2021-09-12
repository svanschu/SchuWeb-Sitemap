<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */
// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * Component Controller
 *
 * @package     SchuWeb Sitemap
 * @subpackage  com_schuweb_sitemap
 */
class SchuWeb_SitemapController extends JControllerLegacy
{

    function __construct()
    {
        parent::__construct();

        $this->registerTask('navigator-links', 'navigatorLinks');
    }

    /**
     * Display the view
     */
    public function display($cachable = false, $urlparams = false)
    {
        require_once JPATH_COMPONENT . '/helpers/schuweb_sitemap.php';

        // Get the document object.
        $document = JFactory::getDocument();

        $jinput = JFactory::$application->input;

        // Set the default view name and format from the Request.
        $vName = $jinput->getWord('view', 'sitemaps');
        $vFormat = $document->getType();
        $lName = $jinput->getWord('layout', 'default');

        // Get and render the view.
        if ($view = $this->getView($vName, $vFormat)) {
            // Get the model for the view.
            $model = $this->getModel($vName);

            // Push the model into the view (as default).
            $view->setModel($model, true);
            $view->setLayout($lName);

            // Push document object into the view.
            $view->document = &$document;

            $view->display();

        }
    }

    function navigator()
    {
        $document = JFactory::getDocument();
        $app = JFactory::getApplication('administrator');
        $jinput = $app->input;

        $id = $jinput->getInt('sitemap', 0);
        if (!$id) {
            $id = $this->getDefaultSitemapId();
        }

        if (!$id) {
            $app->enqueueMessage(JText::_('SCHUWEB_SITEMAP_Not_Sitemap_Selected'), 'warning');
            return false;
        }

        $app->setUserState('com_schuweb_sitemap.edit.sitemap.id', $id);

        $view = $this->getView('sitemap', $document->getType());
        $model = $this->getModel('Sitemap');
        $view->setLayout('navigator');
        $view->setModel($model, true);

        // Push document object into the view.
        $view->document = &$document;

        $view->navigator();
    }

    function navigatorLinks()
    {
        $document = JFactory::getDocument();
        $app = JFactory::getApplication('administrator');
        $jinput = $app->input;

        $id = $jinput->getInt('sitemap', 0);
        if (!$id) {
            $id = $this->getDefaultSitemapId();
        }

        if (!$id) {
            $app->enqueueMessage(JText::_('SCHUWEB_SITEMAP_Not_Sitemap_Selected'), 'warning');
            return false;
        }

        $app->setUserState('com_schuweb_sitemap.edit.sitemap.id', $id);

        $view = $this->getView('sitemap', $document->getType());
        $model = $this->getModel('Sitemap');
        $view->setLayout('navigator');
        $view->setModel($model, true);

        // Push document object into the view.
        $view->document = &$document;

        $view->navigatorLinks();
    }

    private function getDefaultSitemapId()
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from($db->quoteName('#__schuweb_sitemap'));
        $query->where('is_default=1');
        $db->setQuery($query);
        return $db->loadResult();
    }

}