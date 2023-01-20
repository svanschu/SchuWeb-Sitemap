<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Sven Schultschik (extensions@schultschik.de)
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * @package     SchuWeb Sitemap
 * @subpackage  com_schuweb_sitemap
 * @since       2.0
 */
class SchuWeb_SitemapControllerSitemap extends JControllerForm
{
    /**
     * Method override to check if the user can edit an existing record.
     *
     * @param    array    An array of input data.
     * @param    string   The name of the key for the primary key.
     *
     * @return   boolean
     */
    protected function _allowEdit($data = array(), $key = 'id')
    {
        // Initialise variables.
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;

        // Assets are being tracked, so no need to look into the category.
        return JFactory::getApplication()->getIdentity()->authorise('core.edit', 'com_schuweb_sitemap.sitemap.'.$recordId);
    }
}