<?php

/**
 * @version             sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Sven Schultschik (extensions@schultschik.de)
 */

use Joomla\CMS\User\User;

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.view');

/**
 * XML Sitemap View class for the SchuWeb_Sitemap component
 *
 * @package      SchuWeb_Sitemap
 * @subpackage   com_schuweb_sitemap
 * @since        2.0
 */
class SchuWeb_SitemapViewXml extends JViewLegacy
{

    protected $state;
    protected $print;

    protected $_obLevel;

    protected bool $isImages;

    function display($tpl = null)
    {
        // Initialise variables.
        $app = JFactory::getApplication();
        $this->user = $app->getIdentity();
        $jinput = $app->input;
        $isNewsSitemap = $jinput->getInt('news',0) != 0;
        $this->isImages = $jinput->getInt('images',0) != 0;

        $model = $this->getModel('Sitemap');
        $this->setModel($model);

        // force to not display errors on XML sitemap
        @ini_set('display_errors', 0);
        # Increase memory and max execution time for XML sitemaps to make it work
        # with very large sites
        @ini_set('memory_limit','512M');
        @ini_set('max_execution_time',300);

        $layout = $this->getLayout();

        $this->item = $this->get('Item');
        $this->state = $this->get('State');
        $this->canEdit = $this->user->authorise('core.admin', 'com_schuweb_sitemap');

        // For now, news sitemaps are not editable
        $this->canEdit = $this->canEdit && !$isNewsSitemap;

        if ($layout == 'xsl') {
            return $this->displayXSL($layout);
        }

        // Get model data.
        $this->items = $this->get('Items');
        $this->sitemapItems = $this->get('SitemapItems');
        $this->extensions = $this->get('Extensions');

        $freq = 31536000;
        foreach ($this->items as $item) {
            foreach ($item as $iitem) {
                foreach ($iitem->items as $iitems) {
                    switch ($iitems->changefreq) {
                        case "never":
                        case "yearly":
                            if ($freq > 31536000)
                                $freq = 31536000;
                            break;
                        case "monthly":
                            if ($freq > 18144000)
                                $freq = 18144000;
                            break;
                        case "weekly":
                            if ($freq > 604800)
                                $freq = 604800;
                            break;
                        case "daily": //60 Sekunden x 60 Minuten x 24 Stunden
                            if ($freq > 86400)
                                $freq = 86400;
                            break;
                        case "hourly":
                            if ($freq > 3600)
                                $freq = 3600;
                            break;
                    }
                }
            }
        }

        $this->changeFreq = $freq;

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app->enqueueMessage(implode("\n", $errors), 'warning');
            return false;
        }

        // Add router helpers.
        $this->item->slug = $this->item->alias ? ($this->item->id . ':' . $this->item->alias) : $this->item->id;

        $this->item->rlink = JRoute::_('index.php?option=com_schuweb_sitemap&view=xml&id=' . $this->item->slug);

        // Create a shortcut to the paramemters.
        $params = &$this->state->params;

        if (!$this->item->params->get('access-view')) {
            if ($this->user->get('guest')) {
                // Redirect to login
                $uri = JUri::getInstance();
                $app->redirect(
                    'index.php?option=com_users&view=login&return=' . base64_encode($uri),
                    JText::_('SchuWeb_Sitemap_Error_Login_to_view_sitemap')
                );
                return;
            } else {
                $app->enqueueMessage(JText::_('SchuWeb_Sitemap_Error_Not_auth'), 'warning');
                return;
            }
        }

        // Override the layout.
        if ($layout = $params->get('layout')) {
            $this->setLayout($layout);
        }

        // Load the class used to display the sitemap
        $this->loadTemplate('class');
        $this->displayer = new SchuWeb_SitemapXmlDisplayer($params, $this->item);

        $this->displayer->setJView($this);

        $this->displayer->isNews = $isNewsSitemap;
        $this->displayer->isImages = $this->isImages;
        $this->displayer->canEdit = $this->canEdit;

        $doCompression = ($this->item->params->get('compress_xml') && !ini_get('zlib.output_compression') && ini_get('output_handler') != 'ob_gzhandler');
        $this->endAllBuffering();
        if ($doCompression) {
            ob_start();
        }

        parent::display($tpl);

        $model = $this->getModel();
        $model->hit($this->displayer->getCount());

        if ($doCompression) {
            $data = ob_get_contents();
            $app->setBody($data);
            @ob_end_clean();
            echo $app->toString(true);
        }
        $this->recreateBuffering();
        exit;
    }

    function displayXSL()
    {
        $this->setLayout('default');

        $this->endAllBuffering();
        parent::display('xsl');
        $this->recreateBuffering();
        exit;
    }

    private function endAllBuffering()
    {
        $this->_obLevel = ob_get_level();
        $level = FALSE;
        while (ob_get_level() > 0 && $level !== ob_get_level()) {
            @ob_end_clean();
            $level = ob_get_level();
        }
    }
    private function recreateBuffering()
    {
        while($this->_obLevel--) {
            ob_start();
        }
    }

}
