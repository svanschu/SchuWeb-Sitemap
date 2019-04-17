<?php
/**
 * @version     $Id$
 * @copyright   Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * @package     SchuWeb Sitemap
 * @subpackage  com_schuweb_sitemap
 * @since       2.0
 */
class SchuWeb_SitemapControllerSitemaps extends JControllerAdmin
{

    protected $text_prefix = 'com_schuweb_sitemap_SITEMAPS';

    /**
     * Constructor
     */
    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->registerTask('unpublish',    'publish');
        $this->registerTask('trash',        'publish');
        $this->registerTask('unfeatured',   'featured');
    }


    /**
     * Method to toggle the default sitemap.
     *
     * @return      void
     * @since       2.0
     */
    function setDefault()
    {
        $input = $this->input;
        // Check for request forgeries
        $this->checkToken();

        // Get items to publish from the request.
        $cid = $input->getVar('cid', 0, '', 'array');
        $id  = @$cid[0];

        if (!$id) {
            $this->enqueueMessage(JText::_('Select an item to set as default'), 'warning');
        }
        else
        {
            // Get the model.
            $model = $this->getModel();

            // Publish the items.
            if (!$model->setDefault($id)) {
                $this->enqueueMessage($model->getError(), 'warning');
            }
        }

        $this->setRedirect('index.php?option=com_schuweb_sitemap&view=sitemaps');
    }

    /**
     * Proxy for getModel.
     *
     * @param    string    $name    The name of the model.
     * @param    string    $prefix    The prefix for the PHP class name.
     *
     * @return    JModel
     * @since    2.0
     */
    public function getModel($name = 'Sitemap', $prefix = 'SchuWeb_SitemapModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }
}