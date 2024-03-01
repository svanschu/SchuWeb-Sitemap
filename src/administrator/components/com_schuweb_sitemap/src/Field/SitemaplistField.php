<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') || die();

/**
 * Create a select list of all available sitemaps
 * 
 * @since 5.1.0
 */
class SitemaplistField extends ListField
{
    /**
     * Type of the field
     * 
     * @since 5.1.0
     */
    protected $type = 'Sitemaplist';

    /**
     * Get the select list field information
     * 
     * @since 5.1.0
     */
    protected function getInput()
    {
        // Add options for each and every sitemap
        /** @var DatabaseDriver $db */
        $db = method_exists($this, 'getDatabase')
            ? $this->getDatabase()
            : Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select([
                $db->qn('id'),
                $db->qn('title')
            ])
            ->from($db->qn('#__schuweb_sitemap'));
        $db->setQuery($query);

        $sitemaplist = $db->loadObjectList() ?? [];

        foreach ($sitemaplist as $sitemap) {
            $this->addOption($sitemap->title, ['value' => $sitemap->id]);
        }

        return parent::getInput();
    }
}