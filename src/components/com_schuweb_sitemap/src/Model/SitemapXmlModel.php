<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\Model;

use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * SchuWeb_Sitemap Component Sitemap Model
 *
 * @package        SchuWeb_Sitemap
 * @subpackage     com_schuweb_sitemap
 * @since          5.1.0
 */
class SitemapXmlModel extends BaseDatabaseModel
{
    /**
     * XML sitemap object
     * 
     * @var \XMLWriter
     * @since 5.1.0
     */
    private \XMLWriter $xw;

    /**
     *
     * @var array  Stores the list of links that have been already included in
     *             the sitemap to avoid duplicated items
     * @since 5.1.0
     */
    private array $_links;

    /**
     * @var int id of the sitemap to be created
     * 
     * @since 5.1.0
     */
    private int $_pk;

    /**
     * Which types of sitemap should be generated
     * 
     * @var array
     * @since 5.1.0
     */
    private array $_types;

    /**
     * Constructor
     *
     * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
     * @param   ?MVCFactoryInterface  $factory  The factory.
     *
     * @since   5.1.0
     * @throws  \Exception
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        parent::__construct($config, $factory);

        if (!is_null($config['pk']) && !empty($config['pk']) && is_int($config['pk']))
            $this->_pk = $config['pk'];
        else
            throw new \RuntimeException('Invalid value for pk');
    }

    /**
     * Create news sitemap xml file
     * 
     * @return void
     * @since 5.1.0
     */
    public function createxmlnews()
    {
        $site_sitemap_model = $this->getMVCFactory()->createModel('Sitemap', 'Site', ['ignore_request' => true]);
        $site_sitemap_model->setNewssitemap(true);
        $this->createSitemapXml($site_sitemap_model);
    }

    /**
     * Create images sitemap xml file
     * 
     * @return void
     * @since 5.1.0
     */
    public function createxmlimages()
    {
        $site_sitemap_model = $this->getMVCFactory()->createModel('Sitemap', 'Site', ['ignore_request' => true]);
        $site_sitemap_model->setImagesitemap(true);
        $this->createSitemapXml($site_sitemap_model);
    }

    /**
     * Create sitemap xml file
     * 
     * @return void
     * @since 5.1.0
     */
    public function createxml()
    {
        $site_sitemap_model = $this->getMVCFactory()->createModel('Sitemap', 'Site', ['ignore_request' => true]);
        $this->createSitemapXml($site_sitemap_model);
    }

    /**
     * Create actually the xml file for sitemap, news and images
     * 
     * @return void
     * @since 5.1.0
     */
    private function createSitemapXml(&$site_sitemap_model)
    {
        $site_sitemap_model->setState('sitemap.id', $this->_pk);

        $params = ComponentHelper::getParams('com_schuweb_sitemap');
        $site_sitemap_model->setState('params', $params);

        //TODO make it variable throught site settings
        $site_sitemap_model->setLanguageFilter(false);

        $site_sitemap_model->setXmlsitemap(true);

        $nodes = $site_sitemap_model->getNodes();

        $params = $site_sitemap_model->getState('params');

        $sitemapname = $site_sitemap_model->getName();
        if ($site_sitemap_model->isDefault())
            $sitemapname = 'sitemap';
        if ($site_sitemap_model->isNewssitemap())
            $sitemapname .= '_news';
        if ($site_sitemap_model->isImagesitemap())
            $sitemapname .= '_images';

        $this->xw = xmlwriter_open_memory();
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

        if ($site_sitemap_model->isImagesitemap()) {
            xmlwriter_start_attribute($this->xw, 'xmlns:image');
            xmlwriter_text($this->xw, "http://www.google.com/schemas/sitemap-image/1.1");
            xmlwriter_end_attribute($this->xw);
        }

        if ($site_sitemap_model->isNewssitemap()) {
            xmlwriter_start_attribute($this->xw, 'xmlns:news');
            xmlwriter_text($this->xw, "http://www.google.com/schemas/sitemap-news/0.9");
            xmlwriter_end_attribute($this->xw);
        }

        foreach ($nodes as $node) {
            $this->printNode(
                $node,
                $site_sitemap_model
            );
        }

        xmlwriter_end_element($this->xw);

        xmlwriter_end_document($this->xw);

        $xml = xmlwriter_output_memory($this->xw);

        if ($params->get('compress_xml')) {
            $dom = new \DOMDocument("1.0");

            // Preserve redundant spaces (`true` by default)
            $dom->preserveWhiteSpace = false;

            // Disable automatic document indentation
            $dom->formatOutput = false;

            $dom->loadXML($xml);

            /** @var string $minifiedXml  */
            $xml = $dom->saveXML();
        }

        $path    = JPATH_SITE . '/' . $sitemapname . '.xml';
        $xmlfile = fopen($path, "w");
        if (!$xmlfile) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('SCHUWEB_SITEMAP_XML_ERROR_OPEN_FILE', $path), CMSApplicationInterface::MSG_CRITICAL);
            return;
        }
        fwrite($xmlfile, $xml);
        fclose($xmlfile);
    }

    /**
     * Print Node of XML file
     * 
     * @return void
     * @since 5.1.0
     */
    private function printNode(&$node, &$site_sitemap_model)
    {
        $params = $site_sitemap_model->getState('params');

        $newssitemap           = $site_sitemap_model->isNewssitemap();
        $news_publication_name = $site_sitemap_model->getNewsPublicationName();

        if ($node->browserNav != 3 && !isset($node->htmllink)) {
            $node->htmllink = Route::link('site', $node->link, true, @$node->secure, true);
        }

        $diff = -1;
        if ($newssitemap && isset($node->modified)) {
            $oldest   = date_add(date_create(), date_interval_create_from_date_string('-2 days'));
            $interval = date_diff($oldest, date_create($node->modified));
            $diff     = intval($interval->format('%R%a')) + 1;
        }

        // ignore "no link" && ignore links that have been added already
        if (
            $node->browserNav != 3 && empty($this->_links[$node->htmllink])
            // Ignore nodes without modified date on news sitemap
            && !($newssitemap && (!isset($node->modified) || $diff < 0))
            // Ignore nodes without images on image sitemap
            && !($site_sitemap_model->isImagesitemap() && !isset($node->images))
        ) {

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

            if ($newssitemap) {
                xmlwriter_start_element($this->xw, 'news:news');
                xmlwriter_start_element($this->xw, 'news:publication');
                xmlwriter_start_element($this->xw, 'news:name');
                xmlwriter_text($this->xw, $news_publication_name);
                xmlwriter_end_element($this->xw);
                xmlwriter_start_element($this->xw, 'news:language');
                //ToDo support multi lang
                $app         = Factory::getApplication();
                $languageObj = $app->getLanguage();
                xmlwriter_text($this->xw, $languageObj->get('tag'));
                xmlwriter_end_element($this->xw);
                xmlwriter_end_element($this->xw);
                xmlwriter_start_element($this->xw, 'news:publication_date');
                xmlwriter_text($this->xw, (new Date($node->modified))->toISO8601(true));
                xmlwriter_end_element($this->xw);
                xmlwriter_start_element($this->xw, 'news:title');
                xmlwriter_text($this->xw, $node->name);
                xmlwriter_end_element($this->xw);
                xmlwriter_end_element($this->xw);
            }

            if ($site_sitemap_model->isImagesitemap()) {
                foreach ($node->images as $image) {
                    xmlwriter_start_element($this->xw, 'image:image');
                    xmlwriter_start_element($this->xw, 'image:loc');
                    xmlwriter_text($this->xw, $image->src);
                    xmlwriter_end_element($this->xw);
                    xmlwriter_end_element($this->xw);
                }
            }

            if ($params->get('xmlLastMod') != 0) {
                $modified = (isset($node->modified)
                    && $node->modified != FALSE
                    && $node->modified != -1)
                    ? $node->modified : NULL;

                if (!$modified && $newssitemap) {
                    $modified = time();
                }
                if ($modified && !is_numeric($modified)) {
                    $date     = new Date($modified);
                    $modified = $date->toUnix();
                }
                if ($modified) {
                    $modified = gmdate('Y-m-d\TH:i:s\Z', $modified);
                }

                if ($modified && !$newssitemap && !$site_sitemap_model->isImagesitemap()) {
                    xmlwriter_start_element($this->xw, 'lastmod');
                    xmlwriter_text($this->xw, $modified);
                    xmlwriter_end_element($this->xw);
                }
            }

            if (
                $params->get('xmlInsertChangeFreq')
                && $node->changefreq
                && !$newssitemap
                && !$site_sitemap_model->isImagesitemap()
            ) {
                xmlwriter_start_element($this->xw, 'changefreq');
                xmlwriter_text($this->xw, $node->changefreq);
                xmlwriter_end_element($this->xw);
            }

            if (
                $params->get('xmlInsertPriority')
                && $node->priority
                && !$newssitemap
                && !$site_sitemap_model->isImagesitemap()
            ) {
                xmlwriter_start_element($this->xw, 'priority');
                xmlwriter_text($this->xw, $node->priority);
                xmlwriter_end_element($this->xw);
            }

            xmlwriter_end_element($this->xw);
        }

        if (isset($node->subnodes)) {
            foreach ($node->subnodes as $subnode) {
                $this->printNode($subnode, $site_sitemap_model);
            }
        }
    }
}