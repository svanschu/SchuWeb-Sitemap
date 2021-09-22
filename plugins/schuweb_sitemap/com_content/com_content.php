<?php
/**
 * @version             sw.build.version
 * @copyright (C) 2010-2021 Sven Schultschik. All rights reserved
 * @author Sven Schultschik
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.schultschik.de
 */
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4', 'lt'))
{
	require_once JPATH_SITE . '/components/com_content/helpers/route.php';
	require_once JPATH_SITE . '/components/com_content/helpers/query.php';
}

use \Joomla\Component\Content\Site\Helper\RouteHelper;
use \Joomla\Component\Content\Site\Helper\QueryHelper;
use \Joomla\Utilities\ArrayHelper;
/**
 * Handles standard Joomla's Content articles/categories
 *
 * This plugin is able to expand the categories keeping the right order of the
 * articles acording to the menu settings and the user session data (user state).
 *
 * This is a very complex plugin, if you are trying to build your own plugin
 * for other component, I suggest you to take a look to another plugis as
 * they are usually most simple. ;)
 */
class schuweb_sitemap_com_content
{
    /**
     * This function is called before a menu item is printed. We use it to set the
     * proper uniqueid for the item
     *
     * @param object  Menu item to be "prepared"
     * @param array   The extension params
     *
     * @return void
     * @since  1.2
     */
    static function prepareMenuItem($node, &$params)
    {
        $db = JFactory::getDbo();
        $link_query = parse_url($node->link);
        if (!isset($link_query['query'])) {
            return;
        }

        parse_str(html_entity_decode($link_query['query']), $link_vars);
        $view = ArrayHelper::getValue($link_vars, 'view', '');
        $layout = ArrayHelper::getValue($link_vars, 'layout', '');
        $id = ArrayHelper::getValue($link_vars, 'id', 0);

        //----- Set add_images param
        $params['add_images'] = ArrayHelper::getValue($params, 'add_images', 0);

        //----- Set add pagebreaks param
        $params['add_pagebreaks'] = ArrayHelper::getValue($params, 'add_pagebreaks', 1);

        switch ($view) {
            case 'category':
                if ($id) {
                    $node->uid = 'com_contentc' . $id;
                } else {
                    $node->uid = 'com_content' . $layout;
                }
                $node->expandible = true;
                break;
            case 'article':
                $node->uid = 'com_contenta' . $id;
                $node->expandible = false;

                $query = $db->getQuery(true);

                $query->select($db->quoteName('created'))
                      ->select($db->quoteName('modified'))
                      ->from($db->quoteName('#__content'))
                      ->where($db->quoteName('id').'='.intval($id));

                if ($params['add_pagebreaks'] || $params['add_images']){
                    $query->select($db->quoteName('introtext'))
                          ->select($db->quoteName('fulltext'));
                }


                $db->setQuery($query);
                if (($row = $db->loadObject()) != NULL) {
                    $node->modified = $row->modified;

                    $text = @$item->introtext . @$item->fulltext;
                    if ($params['add_images']) {
                        $node->images = SchuWeb_SitemapHelper::getImages($text,ArrayHelper::getValue($params, 'max_images', 1000));
                    }

                    if ($params['add_pagebreaks']) {
                        $node->subnodes = SchuWeb_SitemapHelper::getPagebreaks($text,$node->link);
                        $node->expandible = (count($node->subnodes) > 0); // This article has children
                    }
                }
                break;
            case 'archive':
                $node->expandible = true;
                break;
            case 'featured':
                $node->uid = 'com_contentfeatured';
                $node->expandible = false;
        }
    }

    /**
     * Expands a com_content menu item
     *
     * @return void
     * @since  1.0
     */
    static function getTree($xmap, $parent, &$params)
    {
        $db = JFactory::getDBO();
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $result = null;

        $link_query = parse_url($parent->link);
        if (!isset($link_query['query'])) {
            return;
        }

        parse_str(html_entity_decode($link_query['query']), $link_vars);
        $view = ArrayHelper::getValue($link_vars, 'view', '');
        $id = intval(ArrayHelper::getValue($link_vars, 'id', ''));

        /*         * *
         * Parameters Initialitation
         * */
        //----- Set expand_categories param
        $expand_categories = ArrayHelper::getValue($params, 'expand_categories', 1);
        $expand_categories = ( $expand_categories == 1
            || ( $expand_categories == 2 && $xmap->view == 'xml')
            || ( $expand_categories == 3 && $xmap->view == 'html')
            || $xmap->view == 'navigator');
        $params['expand_categories'] = $expand_categories;

        //----- Set expand_featured param
        $expand_featured = ArrayHelper::getValue($params, 'expand_featured', 1);
        $expand_featured = ( $expand_featured == 1
            || ( $expand_featured == 2 && $xmap->view == 'xml')
            || ( $expand_featured == 3 && $xmap->view == 'html')
            || $xmap->view == 'navigator');
        $params['expand_featured'] = $expand_featured;

        //----- Set expand_featured param
        $include_archived = ArrayHelper::getValue($params, 'include_archived', 2);
        $include_archived = ( $include_archived == 1
            || ( $include_archived == 2 && $xmap->view == 'xml')
            || ( $include_archived == 3 && $xmap->view == 'html')
            || $xmap->view == 'navigator');
        $params['include_archived'] = $include_archived;

        //----- Set show_unauth param
        $show_unauth = ArrayHelper::getValue($params, 'show_unauth', 1);
        $show_unauth = ( $show_unauth == 1
            || ( $show_unauth == 2 && $xmap->view == 'xml')
            || ( $show_unauth == 3 && $xmap->view == 'html'));
        $params['show_unauth'] = $show_unauth;

        //----- Set add_images param
        $add_images = ArrayHelper::getValue($params, 'add_images', 0) && $xmap->isImages;
        $add_images = ( $add_images && $xmap->view == 'xml');
        $params['add_images'] = $add_images;
        $params['max_images'] = ArrayHelper::getValue($params, 'max_images', 1000);

        //----- Set add pagebreaks param
        $add_pagebreaks = ArrayHelper::getValue($params, 'add_pagebreaks', 1);
        $add_pagebreaks = ( $add_pagebreaks == 1
            || ( $add_pagebreaks == 2 && $xmap->view == 'xml')
            || ( $add_pagebreaks == 3 && $xmap->view == 'html')
            || $xmap->view == 'navigator');
        $params['add_pagebreaks'] = $add_pagebreaks;

        if ($params['add_pagebreaks'] && !defined('_SCHUWEBSITEMAP_COM_CONTENT_LOADED')) {
            define('_SCHUWEBSITEMAP_COM_CONTENT_LOADED',1);  // Load it just once
            $lang = JFactory::getLanguage();
            $lang->load('plg_content_pagebreak');
        }

        //----- Set cat_priority and cat_changefreq params
        $priority = ArrayHelper::getValue($params, 'cat_priority', $parent->priority);
        $changefreq = ArrayHelper::getValue($params, 'cat_changefreq', $parent->changefreq);
        if ($priority == '-1')
            $priority = $parent->priority;
        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $params['cat_priority'] = $priority;
        $params['cat_changefreq'] = $changefreq;

        //----- Set art_priority and art_changefreq params
        $priority = ArrayHelper::getValue($params, 'art_priority', $parent->priority);
        $changefreq = ArrayHelper::getValue($params, 'art_changefreq', $parent->changefreq);
        if ($priority == '-1')
            $priority = $parent->priority;
        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $params['art_priority'] = $priority;
        $params['art_changefreq'] = $changefreq;

        $params['max_art'] = intval(ArrayHelper::getValue($params, 'max_art', 0));
        $params['max_art_age'] = intval(ArrayHelper::getValue($params, 'max_art_age', 0));

        $params['nullDate'] = $db->Quote($db->getNullDate());

        $params['nowDate'] = $db->Quote(JFactory::getDate()->toSql());
        $params['groups'] = implode(',', $user->getAuthorisedViewLevels());

        // Define the language filter condition for the query
        $params['language_filter'] = $app->getLanguageFilter();

        switch ($view) {
            case 'category':
                if (!$id) {
                    $id = intval(ArrayHelper::getValue($params, 'id', 0));
                }
                if ($params['expand_categories'] && $id) {
                    $result = self::expandCategory($xmap, $parent, $id, $params, $parent->id);
                }
                break;
            case 'featured':
                if ($params['expand_featured']) {
                    $result = self::includeCategoryContent($xmap, $parent, 'featured', $params,$parent->id);
                }
                break;
            case 'categories':
                if ($params['expand_categories']) {
                    $result = self::expandCategory($xmap, $parent, ($id ? $id : 1), $params, $parent->id);
                }
                break;
            case 'archive':
                if ($params['expand_featured']) {
                    $result = self::includeCategoryContent($xmap, $parent, 'archived', $params,$parent->id);
                }
                break;
            case 'article':
                // if it's an article menu item, we have to check if we have to expand the
                // article's page breaks
                if ($params['add_pagebreaks']){
                    $query = $db->getQuery(true);

                    $query->select($db->quoteName('introtext'))
                          ->select($db->quoteName('fulltext'))
                          ->select($db->quoteName('alias'))
                          ->select($db->quoteName('catid'))
                          ->from($db->quoteName('#__content'))
                          ->where($db->quoteName('id').'='.intval($id));
                    $db->setQuery($query);

                    $row = $db->loadObject();

                    $parent->slug = $row->alias ? ($id . ':' . $row->alias) : $id;
                    $parent->link = ContentHelperRoute::getArticleRoute($parent->slug, $row->catid);

                    $subnodes = SchuWeb_SitemapHelper::getPagebreaks($row->introtext.$row->fulltext,$parent->link);
                    self::printNodes($xmap, $parent, $params, $subnodes);
                }

        }
        return $result;
    }

    /**
     * Get all content items within a content category.
     * Returns an array of all contained content items.
     *
     * @param object  $sitemap
     * @param object  $parent   the menu item
     * @param int     $catid    the id of the category to be expanded
     * @param array   $params   an assoc array with the params for this plugin on Xmap
     * @param int     $itemid   the itemid to use for this category's children
     */
    static function expandCategory($sitemap, $parent, $catid, &$params, $itemid)
    {
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);

        $where = array('a.parent_id = ' . $catid . ' AND a.published = 1 AND a.extension=\'com_content\'');

        if ($params['language_filter'] ) {
            $where[] = 'a.language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')';
        }

        if (!$params['show_unauth']) {
            $where[] = 'a.access IN (' . $params['groups'] . ') ';
        }

	    $columns = array(
		    $db->quoteName('a.id'),
		    $db->quoteName('a.title'),
		    $db->quoteName('a.alias'),
		    $db->quoteName('a.access'),
		    $db->quoteName('a.path') . 'AS route',
		    $db->quoteName('a.created_time') . 'AS created',
		    $db->quoteName('a.modified_time') . 'AS modified'
	    );
	    $query->select($columns)
		    ->from($db->quoteName('#__categories') . 'AS a')
		    ->where($where);
	    if ($sitemap->view != 'xml')
		    $query->order('a.lft');

        $db->setQuery($query);
        $items = $db->loadObjectList();

        if (count($items) > 0) {
            $sitemap->changeLevel(1);
            foreach ($items as $item) {
                $node = new stdclass();
                $node->id = $parent->id;
                $node->uid = $parent->uid . 'c' . $item->id;
                $node->browserNav = $parent->browserNav;
                $node->priority = $params['cat_priority'];
                $node->changefreq = $params['cat_changefreq'];

                $attribs = json_decode($sitemap->sitemap->attribs);
                $node->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
                $node->xmlInsertPriority = $attribs->xmlInsertPriority;

                $node->name = $item->title;
                $node->expandible = true;
                $node->secure = $parent->secure;
                $node->lastmod = $parent->lastmod;
                // TODO: Should we include category name or metakey here?
                // $node->keywords = $item->metakey;
                $node->newsItem = 0;

                // For the google news we should use te publication date instead
                // the last modification date. See
                if ($sitemap->isNews || !$item->modified)
                    $item->modified = $item->created;

                $node->slug = $item->route ? ($item->id . ':' . $item->route) : $item->id;
                $node->link = ContentHelperRoute::getCategoryRoute($node->slug);
                if (strpos($node->link,'Itemid=')===false) {
                    $node->itemid = $itemid;
                    $node->link .= '&Itemid='.$itemid;
                } else {
                    $node->itemid = preg_replace('/.*Itemid=([0-9]+).*/','$1',$node->link);
                }
                if ($sitemap->printNode($node)) {
                    self::expandCategory($sitemap, $parent, $item->id, $params, $node->itemid);
                }
            }
            $sitemap->changeLevel(-1);
        }

        // Include Category's content
        self::includeCategoryContent($sitemap, $parent, $catid, $params, $itemid);
        return true;
    }

	/**
	 * Get all content items within a content category.
	 * Returns an array of all contained content items.
	 *
	 * @throws Exception
	 * @since 2.0
	 */
    static function includeCategoryContent($sitemap, $parent, $catid, &$params, $Itemid)
    {
        $db = JFactory::getDBO();

		$query = $db->getQuery(true);

		$columns = array(
		    $db->quoteName('a.id'),
		    $db->quoteName('a.title'),
		    $db->quoteName('a.alias'),
		    $db->quoteName('a.catid'),
		    $db->quoteName('a.created') . ' AS created',
		    $db->quoteName('a.modified') . ' AS modified',
		    $db->quoteName('a.language')
	    );

	    if ($params['add_images'] || $params['add_pagebreaks'])
	    {
		    $columns[] = $db->quoteName('a.introtext');
		    $columns[] = $db->quoteName('a.fulltext');
	    }

	    $query->select($columns)
	        ->from($db->quoteName('#__content') . ' AS a');

	    if ($catid == 'featured')
		    $query->leftJoin($db->quoteName('#__content_frontpage') . ' AS fp ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('fp.content_id'));

	    if ($catid != 'archived')
		    if ($params['include_archived'])
		    {
			    $query->where('(' . $db->qn('a.state') . ' = 1 or ' . $db->qn('a.state') . '= 2 )');
		    }
		    else
		    {
			    $query->where($db->quoteName('a.state') . ' = 1');
		    }

	    if ($catid=='featured') {
			$query->where($db->qn('a.featured').'=1');
	    } elseif ($catid=='archived') {
		    $query->where($db->qn('a.state').'=2');
	    } elseif(is_numeric($catid)) {
		    $query->where($db->qn('a.catid').'='.(int) $catid);
	    }

	    if ($params['max_art_age'] || $sitemap->isNews)
	    {
		    $days = (($sitemap->isNews && ($params['max_art_age'] > 3 || !$params['max_art_age'])) ? 3 : $params['max_art_age']);
		    $query->where($db->qn('a.created') . '>=' . $db->q(date('Y-m-d H:i:s', time() - $days * 86400)));
	    }

	    if ($params['language_filter'] ) {
		    $query->where($db->qn('a.language').' in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
	    }

	    if (!$params['show_unauth'] ){
		    $query->where($db->qn('a.access').'IN (' . $params['groups'] . ')');
	    }

	    if (version_compare(JVERSION, '4', 'lt'))
	    {
		    $query->andWhere(array($db->quoteName('a.publish_up') . '=' . $params['nullDate'], $db->quoteName('a.publish_up') . '<=' . $params['nowDate']))
			    ->andWhere(array($db->quoteName('a.publish_down') . '=' . $params['nullDate'], $db->quoteName('a.publish_down') . '>=' . $params['nowDate']));
	    } else {
		    $query->andWhere(array($db->quoteName('a.publish_up') . 'IS NULL'  , $db->quoteName('a.publish_up') . '<=' . $params['nowDate']))
			    ->andWhere(array($db->quoteName('a.publish_down') . 'IS NULL' , $db->quoteName('a.publish_down') . '>=' . $params['nowDate']));
	    }

		if ($sitemap->view != 'xml')
			$query->order(self::buildContentOrderBy($parent->params,$parent->id,$Itemid));

		if ($params['max_art'])
			$query->setLimit($params['max_art']);

        $db->setQuery($query);

        $items = $db->loadObjectList();

        if (count($items) > 0) {
            $sitemap->changeLevel(1);
            foreach ($items as $item) {
                $node = new stdclass();
                $node->id = $parent->id;
                $node->uid = $parent->uid . 'a' . $item->id;
                $node->browserNav = $parent->browserNav;
                $node->priority = $params['art_priority'];
                $node->changefreq = $params['art_changefreq'];

                $attribs = json_decode($sitemap->sitemap->attribs);
                $node->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
                $node->xmlInsertPriority = $attribs->xmlInsertPriority;

                $node->name = $item->title;
                $node->modified = $item->modified;
                $node->expandible = false;
                $node->secure = $parent->secure;
                // TODO: Should we include category name or metakey here?
                // $node->keywords = $item->metakey;
                $node->newsItem = 1;
                $node->language = $item->language;
                $node->lastmod = $parent->lastmod;

                // For the google news we should use te publication date instead
                // the last modification date. See
                if ($sitemap->isNews || !$node->modified)
                    $node->modified = $item->created;

                $node->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
                //$node->catslug = $item->category_route ? ($catid . ':' . $item->category_route) : $catid;
                $node->catslug = $item->catid;
                $node->link = ContentHelperRoute::getArticleRoute($node->slug, $node->catslug);

                // Add images to the article
                $text = @$item->introtext . @$item->fulltext;
                if ($params['add_images']) {
                    $node->images = SchuWeb_SitemapHelper::getImages($text,$params['max_images']);
                }

                if ($params['add_pagebreaks']) {
                    $subnodes = SchuWeb_SitemapHelper::getPagebreaks($text,$node->link);
                    $node->expandible = (count($subnodes) > 0); // This article has children
                }

                if ($sitemap->printNode($node) && $node->expandible) {
                    self::printNodes($sitemap, $parent, $params, $subnodes);
                }
            }
            $sitemap->changeLevel(-1);
        }
        return true;
    }

    static private function printNodes($xmap, $parent, &$params, &$subnodes)
    {
        $xmap->changeLevel(1);
        $i=0;
        foreach ($subnodes as $subnode) {
            $i++;
            $subnode->id = $parent->id;
            $subnode->uid = $parent->uid.'p'.$i;
            $subnode->browserNav = $parent->browserNav;
            $subnode->priority = $params['art_priority'];
            $subnode->changefreq = $params['art_changefreq'];

            $attribs = json_decode($xmap->sitemap->attribs);
            $subnode->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
            $subnode->xmlInsertPriority = $attribs->xmlInsertPriority;

            $subnode->secure = $parent->secure;
            $subnode->lastmod = $parent->lastmod;
            $xmap->printNode($subnode);
        }
        $xmap->changeLevel(-1);
    }

	/**
	 * Generates the order by part of the query according to the
	 * menu/component/user settings. It checks if the current user
	 * has already changed the article's ordering column in the frontend
	 *
	 * @param   JRegistry  $params
	 * @param   int        $parentId
	 * @param   int        $itemid
	 *
	 * @return string
	 * @throws Exception
	 */
    static function buildContentOrderBy(&$params,$parentId,$itemid)
    {
        $app    = JFactory::getApplication('site');

        // Case when the child gets a different menu itemid than it's parent
        if ($parentId != $itemid) {
            $menu = $app->getMenu();
            $item = $menu->getItem($itemid);
            $menuParams = clone($params);
            $itemParams = new JRegistry($item->params);
            $menuParams->merge($itemParams);
        } else {
            $menuParams =& $params;
        }

        $filter_order = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order', 'filter_order', '', 'string');
        $filter_order_Dir = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
        $orderby = ' ';

        if ($filter_order && $filter_order_Dir) {
            $orderby .= $filter_order . ' ' . $filter_order_Dir . ', ';
        }

        $articleOrderby     = $menuParams->get('orderby_sec', 'rdate');
        $articleOrderDate   = $menuParams->get('order_date');
        //$categoryOrderby  = $menuParams->def('orderby_pri', '');
	    if (version_compare(JVERSION, '4', 'lt'))
	    {
		    $secondary = ContentHelperQuery::orderbySecondary($articleOrderby, $articleOrderDate) . ', ';
		    //$primary      = ContentHelperQuery::orderbyPrimary($categoryOrderby);
	    } else {
		    $secondary = QueryHelper::orderbySecondary($articleOrderby, $articleOrderDate) . ', ';
		    //$primary      = QueryHelper::orderbyPrimary($categoryOrderby);
	    }

        //$orderby .= $primary . ' ' . $secondary . ' a.created ';
        $orderby .=  $secondary . ' a.created ';

        return $orderby;
    }
}