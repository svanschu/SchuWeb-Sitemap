<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2023 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Site\View\Sitemap;

defined('_JEXEC') or die();

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * HTML Site map View class for the SchuWeb_Sitemap component
 *
 * @package         SchuWeb_Sitemap
 * @subpackage      com_schuweb_sitemap
 * @since           2.0
 */
class HtmlView extends BaseHtmlView
{
    /**
	 * The page parameters
	 *
	 * @var    \Joomla\Registry\Registry|null
	 * @since  5.0.0
	 */
	protected $params = null;

	/**
	 * The item model state
	 *
	 * @var    \Joomla\Registry\Registry
	 * @since  5.0.0
	 */
	protected $state;

    /**
	 * The item object details
	 *
	 * @var    \stdClass
	 * @since  5.0.0
	 */
	protected $item;

    /**
	 * The nodes object with all sitemap elements
	 *
	 * @var    \stdClass
	 * @since  5.0.0
	 */
	protected $nodes;

    /**
	 * The menu items for the sitemap
	 *
	 * @var    \stdClass
	 * @since  5.0.0
	 */
	protected $menuitems;

    /**
	 * The column width
	 *
	 * @var    int
	 * @since  5.0.0
	 */
    private $_width;

    /**
     * @var bool Indicates if this is a google news sitemap or not
     *
     * @since
     */
    public bool $isNews = false;

    /**
     *
     * @var bool Indicates if this is a google image sitemap or not
     *
     * @since  5.0.0
     */

    var bool $isImages = false;
    /**
     *
     * @var int  Counter for the number of links on the sitemap
     * 
     * @since  5.0.0
     */
    protected $count = 0;

    /**
     *
     * @var boolean  can edit
     * 
     * @since  5.0.0
     */
    public $canEdit = false;

    var $view = 'sitemap';

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @throws  \Exception
     */
    public function display($tpl = null)
    {
        $this->item = $this->get('Item');

        $state = $this->state = $this->get('State');
        $params = $this->params = $state->get('params');

        $this->nodes = $this->get('Nodes');

        // Initialise variables.
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        $this->canEdit = $user->authorise('core.admin', 'com_schuweb_sitemap');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app->enqueueMessage(implode("\n", $errors), 'warning');
            return;
        }

        // Add router helpers.
        $this->item->slug = $this->item->alias ? ($this->item->id . ':' . $this->item->alias) : $this->item->id;

        $this->item->rlink = Route::_('index.php?option=com_schuweb_sitemap&view=sitemap&id=' . $this->item->slug);

        // If a guest user, they may be able to log in to view the full article
        // TODO: Does this satisfy the show not auth setting?
        if (!$this->item->params->get('access-view')) {
            if ($user->get('guest')) {
                // Redirect to login
                $uri = Uri::getInstance();
                $app->redirect(
                    'index.php?option=com_users&view=login&return=' . base64_encode($uri), Text::_('SCHUWEB_SITEMAP_ERROR_LOGIN_TO_VIEW_SITEMAP')
                );
                return;
            } else {
                $app->enqueueMessage(Text::_('SCHUWEB_SITEMAP_ERROR_NOT_AUTH'), 'warning');
                return;
            }
        }

        // Override the layout.
        if ($layout = $params->get('layout')) {
            $this->setLayout($layout);
        }

        $columns = $this->item->params->get('columns',0);
        if( $columns > 1 ) { // calculate column widths
            $columns = min($this->get('TotalMenusNumber'), $columns);
            $this->_width    = (100 / $columns) - 1;
            $this->item->params->set('columns',$columns);
        }

        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title, we need to get it from the menu item itself
        if ($menu = $menus->getActive()) {
            if (isset($menu->query['view']) && isset($menu->query['id'])) {

                if ($menu->query['view'] == 'sitemap' && $menu->query['id'] == $this->item->id) {
                    $title = $menu->title;
                    if (empty($title)) {
                        $title = $app->get('sitename');
                    } else if ($app->get('sitename_pagetitles', 0) == 1) {
                        $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
                    } else if ($app->get('sitename_pagetitles', 0) == 2) {
                        $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
                    }
                    // set meta description and keywords from menu item's params
                    $params = new Registry();
                    $params->loadString($menu->getParams());
                    $this->document->setDescription($params->get('menu-meta_description'));
                    $this->document->setMetadata('keywords', $params->get('menu-meta_keywords'));
                }
            }
        }
        $this->document->setTitle($title);

        if ($app->get('MetaTitle') == '1') {
            $this->document->setMetaData('title', $title);
        }

        parent::display($tpl);

        $model = $this->getModel();
        //TODO does not work, realy needed?
        $model->hit($this->count);
    }

    /**
     * Get the column width
     */
    public function getWidth(): int
    {
        return $this->_width;
    }
}