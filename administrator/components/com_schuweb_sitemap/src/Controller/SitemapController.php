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

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Controller for a single sitemap
 *
 * @since  __BUMP_VERSION__
 */
class SitemapController extends FormController
{
     /**
     * The URL option for the component.
     *
     * @var    string
     * @since  1.6
     */
    protected $option = "com_schuweb_sitemap";


    /**
     * Method override to check if the user can edit an existing record.
     *
     * @param    array    An array of input data.
     * @param    string   The name of the key for the primary key.
     *
     * @return   boolean
     */
    // protected function _allowEdit($data = array(), $key = 'id')
    // {
    //     // Initialise variables.
    //     $recordId = (int) isset($data[$key]) ? $data[$key] : 0;

    //     // Assets are being tracked, so no need to look into the category.
    //     return JFactory::getApplication()->getIdentity()->authorise('core.edit', 'com_schuweb_sitemap.sitemap.'.$recordId);
    // }
}