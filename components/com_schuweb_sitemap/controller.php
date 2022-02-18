<?php

/**
 * @version        sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @author         Sven Schultschik (extensions@schultschik.de)
 */
// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * SchuWeb_Sitemap Component Controller
 *
 * @package        SchuWeb_Sitemap
 * @subpackage     com_schuweb_sitemap
 * @since          2.0
 */
class SchuWeb_SitemapController extends JControllerLegacy
{

    /**
     * Method to display a view.
     *
     * @param   boolean         If true, the view output will be cached
     * @param   array           An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  JController     This object to support chaining.
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = false)
    {
        $cachable = true;

        $app    = JFactory::getApplication();
        $input  = $app->input;

        $id         = $input->get('id', null, 'INT');
        $viewName   = $input->get('view');
        $viewLayout = $input->get('layout', 'default');

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
