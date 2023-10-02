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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

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
     *
     * @var bool Indicates if this is a google image sitemap or not
     *
     * @since __BUMP_VERSION__
     */
    private bool $isImages = false;

    /**
     *
     * @var bool Indicates if this is a google image sitemap or not
     *
     * @since __BUMP_VERSION__
     */
    private bool $isNews = false;

    /**
     * XML sitemap object
     * 
     * @var \XMLWriter
     * @since __BUMP_VERSION__
     */
    private \XMLWriter $xw;

    /**
     *
     * @var array  Stores the list of links that have been already included in
     *             the sitemap to avoid duplicated items
     * @since __BUMP_VERSION__
     */
    private array $_links;

    /**
     * Constructor
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        //TODO ??
        $this->registerTask('unfeatured', 'featured');

        $this->registerTask('createxml', 'createxml');
        $this->registerTask('createxmlnews', 'createxml');
        $this->registerTask('createxmlimages', 'createxml');
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

    /**
     * Create XML file
     * 
     * @return void
     * @since __BUMP_VERSION__
     */
    public function createxml()
    {
        if ($this->doTask == 'createxmlnews') $this->isNews = true;
        if ($this->doTask == 'createxmlimages') $this->isImages = true;

        $site_sitemap_model = $this->getModel('Sitemap', 'Site');

        $pks = (array) $this->input->getInt('cid');

        foreach ($pks as $pk) {
            $site_sitemap_model->setState('sitemap.id', $pk);

            $params = ComponentHelper::getParams('com_schuweb_sitemap');
            $site_sitemap_model->setState('params', $params);

            //TODO make it variable throught site settings
            $site_sitemap_model->setLanguageFilter(false);

            $site_sitemap_model->setXmlsitemap(true);

            $nodes = $site_sitemap_model->getNodes();

            $sitemapname = $site_sitemap_model->getName();
            if ($site_sitemap_model->isDefault()) {
                $sitemapname = 'sitemap';
            }

            $path = JPATH_SITE . '/' . $sitemapname . '.xml';
            $this->xw = xmlwriter_open_uri($path);
            xmlwriter_set_indent($this->xw, 1);
            $res = xmlwriter_set_indent_string($this->xw, ' ');

            xmlwriter_start_document($this->xw, '1.0', 'UTF-8');

            xmlwriter_start_element($this->xw, 'urlset');

            xmlwriter_start_attribute($this->xw, 'xmlns:xsi');
            xmlwriter_text($this->xw, "http://www.w3.org/2001/XMLSchema-instance");
            xmlwriter_end_attribute($this->xw);

            xmlwriter_start_attribute($this->xw, 'xsi:schemaLocation');
            xmlwriter_text($this->xw, "http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd");
            xmlwriter_end_attribute($this->xw);

            xmlwriter_start_attribute($this->xw, 'xmlns');
            xmlwriter_text($this->xw, "http://www.sitemaps.org/schemas/sitemap/0.9");
            xmlwriter_end_attribute($this->xw);

            if ($this->isImages) {
                xmlwriter_start_attribute($this->xw, 'xmlns:image');
                xmlwriter_text($this->xw, "http://www.google.com/schemas/sitemap-image/1.1");
                xmlwriter_end_attribute($this->xw);
            }

            if ($this->isNews) {
                xmlwriter_start_attribute($this->xw, 'xmlns:news');
                xmlwriter_text($this->xw, "http://www.google.com/schemas/sitemap-news/0.9");
                xmlwriter_end_attribute($this->xw);
            }

            xmlwriter_end_element($this->xw);

            foreach ($nodes as $node) {

                $this->printNode($node);
            }

            xmlwriter_end_document($this->xw);

            xmlwriter_flush($this->xw);
        }

        $this->setRedirect('index.php?option=com_schuweb_sitemap&view=sitemaps');
    }

    /**
     * Print Node of XML file
     * 
     * @return void
     * @since __BUMP_VERSION__
     */
    private function printNode(&$node)
    {
        // ignore "no link" && ignore links that have been added already
        if ($node->browserNav != 3 && empty($this->_links[$node->htmllink])) {

            if (isset($node->alias) && !$node->alias)
                $this->_links[$node->htmllink] = 1;

            if (!isset($node->priority))
                $node->priority = "0.5";

            if (!isset($node->changefreq))
                $node->changefreq = 'daily';

            xmlwriter_start_element($this->xw, 'url');
            xmlwriter_start_element($this->xw, 'loc');
            xmlwriter_write_raw($this->xw, $node->htmllink);
            xmlwriter_end_element($this->xw);

            $modified = null;
            if ($node->lastmod != 0) {
                $modified = (isset($node->modified) && $node->modified != FALSE && $node->modified != $this->nullDate && $node->modified != -1) ? $node->modified : NULL;
                if (!$modified && $this->isNews) {
                    $modified = time();
                }
                if ($modified && !is_numeric($modified)) {
                    $date = new Date($modified);
                    $modified = $date->toUnix();
                }
                if ($modified) {
                    $modified = gmdate('Y-m-d\TH:i:s\Z', $modified);
                }
            }

            if ($modified) {
                xmlwriter_start_element($this->xw, 'lastmod');
                xmlwriter_text($this->xw, $modified);
                xmlwriter_end_element($this->xw);
            }

            if ($node->changefreq) {
                xmlwriter_start_element($this->xw, 'changefreq');
                xmlwriter_text($this->xw, $node->changefreq);
                xmlwriter_end_element($this->xw);
            }

            if ($node->priority) {
                xmlwriter_start_element($this->xw, 'priority');
                xmlwriter_text($this->xw, $node->priority);
                xmlwriter_end_element($this->xw);
            }

            xmlwriter_end_element($this->xw);
        }

        if (isset($node->subnodes)) {
            foreach ($node->subnodes as $subnode) {
                $this->printNode($subnode);
            }
        }
    }
}