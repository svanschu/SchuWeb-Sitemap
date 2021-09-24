<?php
/**
 * @package SchuWeb Sitemap
 *
 * @version             sw.build.version
 * @author Sven Schultschik
 * @copyright (C) 2010-2021 Sven Schultschik. All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.schultschik.de
 **/

defined( '_JEXEC' ) or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\Component\Newsfeeds\Site\Model\CategoryModel;
use Joomla\Component\Newsfeeds\Site\Helper\RouteHelper;

class schuweb_sitemap_com_newsfeeds
{

    static private $_initialized = false;
    /*
     * This function is called before a menu item is printed. We use it to set the
     * proper uniqueid for the item and indicate whether the node is expandible or not
     */

    static function prepareMenuItem($node, &$params)
    {
        $newsfeed_query = parse_url($node->link);
        parse_str(html_entity_decode($newsfeed_query['query']), $newsfeed_vars);
        $view = ArrayHelper::getValue($newsfeed_vars, 'view', '');
        if ($view == 'newsfeed') {
            $id = intval(ArrayHelper::getValue($newsfeed_vars, 'id', 0));
            if ($id) {
                $node->uid = 'com_newsfeedsi' . $id;
                $node->expandible = false;
            }
        } elseif ($view == 'categories') {
            $node->uid = 'com_newsfeedscategories';
            $node->expandible = true;
        } elseif ($view == 'category') {
            $catid = intval(ArrayHelper::getValue($newsfeed_vars, 'id', 0));
            $node->uid = 'com_newsfeedsc' . $catid;
            $node->expandible = true;
        }
    }

    static function getTree($sitemap, $parent, &$params)
    {
        self::initialize($params);

        $app = JFactory::getApplication();
        $newsfeeds_params = $app->getParams('com_newsfeeds');

        $newsfeed_query = parse_url($parent->link);
        parse_str(html_entity_decode($newsfeed_query['query']), $newsfeed_vars);
        $view = ArrayHelper::getValue($newsfeed_vars, 'view', 0);

        $app = JFactory::getApplication();
        $menu = $app->getMenu();
        $menuparams = $menu->getParams($parent->id);

        if ($view == 'category') {
            $catid = intval(ArrayHelper::getValue($newsfeed_vars, 'id', 0));
        } elseif ($view == 'categories') {
            $catid = 0;
        } else { // Only expand category menu items
            return;
        }

        $include_newsfeeds = ArrayHelper::getValue($params, 'include_newsfeeds', 1, '');
        $include_newsfeeds = ( $include_newsfeeds == 1
            || ( $include_newsfeeds == 2 && $sitemap->view == 'xml')
            || ( $include_newsfeeds == 3 && $sitemap->view == 'html'));
        $params['include_newsfeeds'] = $include_newsfeeds;

        $priority = ArrayHelper::getValue($params, 'cat_priority', $parent->priority, '');
        $changefreq = ArrayHelper::getValue($params, 'cat_changefreq', $parent->changefreq, '');
        if ($priority == '-1')
            $priority = $parent->priority;
        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $params['cat_priority'] = $priority;
        $params['cat_changefreq'] = $changefreq;

        $priority = ArrayHelper::getValue($params, 'newsfeed_priority', $parent->priority, '');
        $changefreq = ArrayHelper::getValue($params, 'newsfeed_changefreq', $parent->changefreq, '');
        if ($priority == '-1')
            $priority = $parent->priority;

        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $params['newsfeed_priority'] = $priority;
        $params['newsfeed_changefreq'] = $changefreq;

        $options = array();
        $options['countItems'] = false;
        $options['catid'] = rand();
        $categories = JCategories::getInstance('Newsfeeds', $options);
        $category = $categories->get($catid? $catid : 'root', true);

        $params['count_clicks'] = $newsfeeds_params->get('count_clicks');

        schuweb_sitemap_com_newsfeeds::getCategoryTree($sitemap, $parent, $params, $category);
    }

    static function getCategoryTree($sitemap, $parent, &$params, $category)
    {
        $children = $category->getChildren();
        $sitemap->changeLevel(1);
        foreach ($children as $cat) {
            $node = new stdclass;
            $node->id = $parent->id;
            $node->uid = $parent->uid . 'c' . $cat->id;
            $node->name = $cat->title;
	        if (version_compare(JVERSION, '4', 'lt'))
	        {
		        $node->link = NewsfeedsHelperRoute::getCategoryRoute($cat);
	        }else{
		        $node->link = RouteHelper::getCategoryRoute($cat);
	        }
            $node->priority = $params['cat_priority'];
            $node->changefreq = $params['cat_changefreq'];

            $attribs = json_decode($sitemap->sitemap->attribs);
            $node->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
            $node->xmlInsertPriority = $attribs->xmlInsertPriority;

	        $node->lastmod = $parent->lastmod;
	        $node->modified = $cat->modified_time;

            $node->expandible = true;
            if ($sitemap->printNode($node) !== FALSE) {
                schuweb_sitemap_com_newsfeeds::getCategoryTree($sitemap, $parent, $params, $cat);
            }
        }
        $sitemap->changeLevel(-1);

        if ($params['include_newsfeeds']) { //view=category&catid=...
	        if (version_compare(JVERSION, '4', 'lt'))
	        {
		        $newsfeedsModel = new NewsfeedsModelCategory();
	        } else
	        {
		        $newsfeedsModel = new CategoryModel();
	        }
            $newsfeedsModel->getState(); // To force the populate state
            $newsfeedsModel->setState('list.limit', ArrayHelper::getValue($params, 'max_newsfeeds', NULL));
            $newsfeedsModel->setState('list.start', 0);
            $newsfeedsModel->setState('list.ordering', 'ordering');
            $newsfeedsModel->setState('list.direction', 'ASC');
            $newsfeedsModel->setState('category.id', $category->id);
            $newsfeeds = $newsfeedsModel->getItems();
            $sitemap->changeLevel(1);
            foreach ($newsfeeds as $newsfeed) {
                $item_params = new JRegistry;
                $item_params->loadString($newsfeed->params);

                $node = new stdclass;
                $node->id = $parent->id;
                $node->uid = $parent->uid . 'i' . $newsfeed->id;
                $node->name = $newsfeed->name;

                // Find the Itemid
	            if (version_compare(JVERSION, '4', 'lt'))
	            {
		            $Itemid = intval(preg_replace('/.*Itemid=([0-9]+).*/','$1',NewsfeedsHelperRoute::getNewsfeedRoute($newsfeed->id, $category->id)));
	            }else{
		            $Itemid = intval(preg_replace('/.*Itemid=([0-9]+).*/','$1',RouteHelper::getNewsfeedRoute($newsfeed->id, $category->id)));
	            }


                if ($item_params->get('count_clicks', $params['count_clicks']) == 1) {
                    $node->link = 'index.php?option=com_newsfeeds&task=newsfeed.go&id='. $newsfeed->id.'&Itemid='.($Itemid ? $Itemid : $parent->id);
                } else {
                    $node->link = $newsfeed->link;
                }
                $node->priority = $params['newsfeed_priority'];
                $node->changefreq = $params['newsfeed_changefreq'];

	            $node->lastmod = $parent->lastmod;
	            $node->modified = $newsfeed->modified;

	            $attribs = json_decode($sitemap->sitemap->attribs);
                $node->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
                $node->xmlInsertPriority = $attribs->xmlInsertPriority;

                $node->expandible = false;
                $sitemap->printNode($node);
            }
            $sitemap->changeLevel(-1);
        }
    }

    static public function initialize(&$params)
    {
        if (self::$_initialized) {
            return;
        }

        self::$_initialized = true;
	    if (version_compare(JVERSION, '4', 'lt'))
	    {
		    require_once JPATH_SITE . '/components/com_newsfeeds/models/category.php';
		    require_once JPATH_SITE . '/components/com_newsfeeds/helpers/route.php';
	    }
    }
}