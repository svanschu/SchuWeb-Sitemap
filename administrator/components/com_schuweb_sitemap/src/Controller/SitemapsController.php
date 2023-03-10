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

namespace SchuWeb\Component\Sitemap\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;

/**
 * @package     SchuWeb Sitemap
 * @subpackage  com_schuweb_sitemap
 * @since       2.0
 */
class SitemapsController extends AdminController
{
    /**
     * The URL option for the component.
     *
     * @var    string
     * @since  1.6
     */
    protected $option = 'com_schuweb_sitemap';

    protected $text_prefix = 'com_schuweb_sitemap_SITEMAPS';

    /**
     * Constructor
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        
        $this->registerTask('unfeatured', 'featured');
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
        $id = @$cid[0];

        if (!$id) {
            $this->enqueueMessage(Text::_('Select an item to set as default'), 'warning');
        } else {
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
     * @param   string  $name    The name of the model.
     * @param   string  $prefix  The prefix for the PHP class name.
     * @param   array   $config  Array of configuration parameters.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     * 
     * @since    2.0
     */
    public function getModel($name = 'Sitemap', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}