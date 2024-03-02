<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
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

        $this->registerTask('unfeatured', 'setdefault');
        $this->registerTask('featured', 'setdefault');
    }


    /**
     * Method to toggle the default sitemap.
     *
     * @return      void
     * @since       2.0
     */
    function setDefault()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get items to publish from the request.
        $cid = (array) $this->input->getInt('cid', 0);
        $id  = @$cid[0];

        if (!$id) {
            $this->enqueueMessage(Text::_('SCHUWEB_SITEMAP_SELECT_DEFAULT'), 'warning');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Publish the items.
            if (!$model->setDefault($id)) {
                $this->enqueueMessage($model->getError(), 'warning');
            } else {
                $config   = ['ignore_request' => true, 'pk' => $id];
                $xmlModel = $this->getModel('SitemapXml', 'Site', $config);
                $xmlModel->createxml();
                $xmlModel->createxmlnews();
                $xmlModel->createxmlimages();
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

    /**
     * Create news sitemap xml file
     * 
     * @return void
     * @since 5.0.0
     */
    public function createxmlnews()
    {
        $this->_createxml('news');
    }

    /**
     * Create images sitemap xml file
     * 
     * @return void
     * @since 5.0.0
     */
    public function createxmlimages()
    {
        $this->_createxml('image');
    }

    /**
     * Create sitemap xml file
     * 
     * @return void
     * @since 5.0.0
     */
    public function createxml()
    {
        $this->_createxml();
    }

    /**
     * Create sitemap xml files depending on the given type
     * 
     * @var string $type type of the sitemap to generate
     * 
     * @since 5.1.0
     */
    private function _createxml(string $type = '')
    {
        $pks = (array) $this->input->getInt('cid');
        foreach ($pks as $pk) {
            $config             = ['ignore_request' => true, 'pk' => $pk];
            $site_sitemap_model = $this->getModel('SitemapXml', 'Site', $config);
            switch ($type) {
                case 'news':
                    $site_sitemap_model->createxmlnews();
                    break;
                case 'image':
                    $site_sitemap_model->createxmlimages();
                    break;
                default:
                    $site_sitemap_model->createxml();
            }
        }

        $this->setRedirect('index.php?option=com_schuweb_sitemap&view=sitemaps');
    }

}