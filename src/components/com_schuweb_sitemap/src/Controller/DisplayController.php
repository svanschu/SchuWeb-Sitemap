<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Factory;

/**
 * Sitemaps Component Controller
 *
 * @since  5.0.0
 */
class DisplayController extends BaseController
{
    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * Recognized key values include 'name', 'default_task', 'model_path', and
     * 'view_path' (this list is not meant to be comprehensive).
     * @param   MVCFactoryInterface  $factory  The factory.
     * @param   CMSApplication       $app      The JApplication for the dispatcher
     * @param   \JInput              $input    Input
     *
     * @since   5.0.0
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
     *
     * @return  static  A \JControllerLegacy object to support chaining.
     *
     * @since   5.0.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        $cachable = true;

        $app = Factory::getApplication();

        $viewName = $this->input->get('view');
        $viewLayout = $this->input->get('layout', 'default');

        $user = $app->getIdentity();

        if ($user->get('id') || !in_array($viewName, array('html', 'xml')) || $viewLayout == 'xsl') {
            $cachable = false;
        }

        if ($viewName) {
            $document = $app->getDocument();
            $viewType = $document->getType();

            $view = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));

            $sitemapmodel = $this->getModel('Sitemap');

            $view->setModel($sitemapmodel, true);
        }

        $safeurlparams = array('id' => 'INT', 'itemid' => 'INT', 'uid' => 'CMD', 'action' => 'CMD', 'property' => 'CMD', 'value' => 'CMD');

        parent::display($cachable, $safeurlparams);

        return $this;
    }
}