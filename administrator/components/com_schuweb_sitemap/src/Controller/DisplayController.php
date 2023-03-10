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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

/**
 * Default Controller of SchuWeb Sitemap component
 *
 * @package     Joomla.Administrator
 * @subpackage  com_schuweb_sitemap
 * 
 * @since    __BUMP_VERSION__
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method.
     *
     * @var string
     */
    protected $default_view = 'sitemaps';

    public function display($cachable = false, $urlparams = array())
    {
        // require_once JPATH_COMPONENT . '/helpers/schuweb_sitemap.php';

        // $app    = Factory::getApplication();
        // // Get the document object.
        // $document = $app->getDocument();

        // $jinput = $app->input;

        // // Set the default view name and format from the Request.
        // $vName = $jinput->getWord('view', 'sitemaps');
        // $vFormat = $document->getType();
        // $lName = $jinput->getWord('layout', 'default');

        // // Get and render the view.
        // if ($view = $this->getView($vName, $vFormat)) {
        //     // Get the model for the view.
        //     $model = $this->getModel($vName);

        //     // Push the model into the view (as default).
        //     $view->setModel($model, true);
        //     $view->setLayout($lName);

        //     // Push document object into the view.
        //     $view->document = &$document;

        //     $view->display();

        // }

        return parent::display($cachable, $urlparams);
    }

}