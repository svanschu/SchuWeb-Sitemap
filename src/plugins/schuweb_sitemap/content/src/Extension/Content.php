<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Plugin\SchuWeb_Sitemap\Content\Extension;

\defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Component\Content\Site\Helper\QueryHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use SchuWeb\Component\Sitemap\Site\Event\MenuItemPrepareEvent;
use SchuWeb\Component\Sitemap\Site\Event\TreePrepareEvent;

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
class Content extends CMSPlugin implements SubscriberInterface
{
    /**
     * @since 5.2.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetMenus' => 'onGetMenus',
            'onGetTree'  => 'onGetTree',
        ];
    }

    /**
     * This function is called before a menu item is printed. We use it to set the
     * proper uniqueid for the item
     *
     * @param   MenuItemPrepareEvent  Event object
     *
     * @return void
     * @since  5.2.0
     */
    public function onGetMenus(MenuItemPrepareEvent $event)
    {
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $menu_item  = $event->getMenuItem();
        $link_query = parse_url($menu_item->link);
        if (!isset($link_query['query'])) {
            return;
        }

        parse_str(html_entity_decode($link_query['query']), $link_vars);
        $view   = ArrayHelper::getValue($link_vars, 'view', '');
        $layout = ArrayHelper::getValue($link_vars, 'layout', '');
        $id     = ArrayHelper::getValue($link_vars, 'id', 0);

        //----- Set add pagebreaks param
        $add_pagebreaks = $this->params->get('add_pagebreaks', 1);

        switch ($view) {
            case 'category':
                if ($id) {
                    $menu_item->uid = 'com_contentc' . $id;
                } else {
                    $menu_item->uid = 'com_content' . $layout;
                }
                $menu_item->expandible = true;
                break;
            case 'article':
                $menu_item->uid = 'com_contenta' . $id;
                $menu_item->expandible = false;

                $query = $db->getQuery(true);

                $query->select($db->quoteName('created'))
                    ->select($db->quoteName('modified'))
                    ->from($db->quoteName('#__content'))
                    ->where($db->quoteName('id') . '=' . intval($id));

                if ($add_pagebreaks) {
                    $query->select($db->quoteName('introtext'))
                        ->select($db->quoteName('fulltext'));
                }


                $db->setQuery($query);
                $row = $db->loadObject();

                if ($row != null) {
                    $menu_item->modified = $row->modified;

                    $text = $row->introtext . $row->fulltext;

                    if ($add_pagebreaks) {
                        $menu_item->subnodes   = self::getPagebreaks($text, $menu_item->link);
                        $menu_item->expandible = (count($menu_item->subnodes) > 0); // This article has children
                    }
                }
                break;
            case 'archive':
                $menu_item->expandible = true;
                break;
            case 'featured':
                $menu_item->uid = 'com_contentfeatured';
                $menu_item->expandible = false;
        }
    }

    /**
     * Expands a com_content menu item
     *
     * @param   TreePrepareEvent  Event object
     *
     * @return void
     * @since  5.2.0
     */
    public function onGetTree(TreePrepareEvent $event)
    {
        $sitemap = $event->getSitemap();
        $parent  = $event->getNode();

        if ($parent->option != "com_content")
            return null;

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $app    = Factory::getApplication();
        $user   = $app->getIdentity();
        $result = null;

        if (is_null($user))
            $groups = [0 => 1];
        else
            $groups = $user->getAuthorisedViewLevels();

        $link_query = parse_url($parent->link);
        if (!isset($link_query['query'])) {
            return;
        }

        parse_str(html_entity_decode($link_query['query']), $link_vars);
        $view = ArrayHelper::getValue($link_vars, 'view', '');
        $id   = intval(ArrayHelper::getValue($link_vars, 'id', ''));

        /*         * *
         * Parameters Initialitation
         * */
        //----- Set expand_categories param
        $expand_categories = $this->params->get('expand_categories', 1);
        $expand_categories = ($expand_categories == 1
            || ($expand_categories == 2 && $sitemap->isXmlsitemap())
            || ($expand_categories == 3 && !$sitemap->isXmlsitemap()));
        $this->params->set('expand_categories', $expand_categories);

        //----- Set expand_featured param
        $expand_featured = $this->params->get('expand_featured', 1);
        $expand_featured = ($expand_featured == 1
            || ($expand_featured == 2 && $sitemap->isXmlsitemap())
            || ($expand_featured == 3 && !$sitemap->isXmlsitemap()));
        $this->params->set('expand_featured', $expand_featured);

        //----- Set expand_featured param
        $include_archived = $this->params->get('include_archived', 2);
        $include_archived = ($include_archived == 1
            || ($include_archived == 2 && $sitemap->isXmlsitemap())
            || ($include_archived == 3 && !$sitemap->isXmlsitemap()));
        $this->params->set('include_archived', $include_archived);

        //----- Set show_unauth param
        $show_unauth = $this->params->get('show_unauth', 1);
        $show_unauth = ($show_unauth == 1
            || ($show_unauth == 2 && $sitemap->isXmlsitemap())
            || ($show_unauth == 3 && !$sitemap->isXmlsitemap()));
        $this->params->set('show_unauth', $show_unauth);

        //----- Set add pagebreaks param
        $add_pagebreaks = $this->params->get('add_pagebreaks', 1);
        $add_pagebreaks = ($add_pagebreaks == 1
            || ($add_pagebreaks == 2 && $sitemap->isXmlsitemap())
            || ($add_pagebreaks == 3 && !$sitemap->isXmlsitemap()));
        $this->params->set('add_pagebreaks', $add_pagebreaks);

        if ($this->params->get('add_pagebreaks') && !defined('_SCHUWEBSITEMAP_COM_CONTENT_LOADED')) {
            define('_SCHUWEBSITEMAP_COM_CONTENT_LOADED', 1); // Load it just once
            $lang = $app->getLanguage();
            $lang->load('plg_content_pagebreak');
        }

        //----- Set cat_priority and cat_changefreq params
        $priority   = $this->params->get('cat_priority', $parent->priority);
        $changefreq = $this->params->get('cat_changefreq', $parent->changefreq);
        if ($priority == '-1')
            $priority = $parent->priority;
        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $this->params->set('cat_priority', $priority);
        $this->params->set('cat_changefreq', $changefreq);

        //----- Set art_priority and art_changefreq params
        $priority   = $this->params->get('art_priority', $parent->priority);
        $changefreq = $this->params->get('art_changefreq', $parent->changefreq);
        if ($priority == '-1')
            $priority = $parent->priority;
        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $this->params->set('art_priority', $priority);
        $this->params->set('art_changefreq', $changefreq);

        $this->params->set('max_art', intval($this->params->get('max_art', 0)));
        $this->params->set('max_art_age', intval($this->params->get('max_art_age', 0)));

        $this->params->set('nullDate', $db->Quote($db->getNullDate()));

        $this->params->set('nowDate', $db->Quote(Factory::getDate()->toSql()));
        $this->params->set('groups', implode(',', (array) $groups));

        // Define the language filter condition for the query
        $this->params->set('language_filter', $sitemap->isLanguageFilter());

        switch ($view) {
            case 'category':
                if (!$id) {
                    $id = intval($this->params->get('id', 0));
                }
                if ($this->params->get('expand_categories') && $id) {
                    $result = self::expandCategory($sitemap, $parent, $id, $params, $parent->id, 0);
                }
                break;
            case 'featured':
                if ($this->params->get('expand_featured')) {
                    $result = self::includeCategoryContent($sitemap, $parent, 'featured', $params, $parent->id);
                }
                break;
            case 'categories':
                if ($this->params->get('expand_categories')) {
                    $result = self::expandCategory($sitemap, $parent, ($id ?: 1), $params, $parent->id, 0);
                }
                break;
            case 'archive':
                if ($this->params->get('expand_featured')) {
                    $result = self::includeCategoryContent($sitemap, $parent, 'archived', $params, $parent->id);
                }
                break;
            case 'article':
                // if it's an article menu item, we have to check if we have to expand the
                // article's page breaks
                if ($this->params->get('add_pagebreaks')) {
                    $query = $db->getQuery(true);

                    $query->select($db->quoteName('introtext'))
                        ->select($db->quoteName('fulltext'))
                        ->select($db->quoteName('alias'))
                        ->select($db->quoteName('catid'))
                        ->select($db->qn('images'))
                        ->select($db->qn('created'))
                        ->select($db->qn('language'))
                        ->from($db->quoteName('#__content'))
                        ->where($db->quoteName('id') . '=' . intval($id));
                    $db->setQuery($query);

                    $row = $db->loadObject();

                    $parent->slug = $row->alias ? ($id . ':' . $row->alias) : $id;
                    $parent->link = RouteHelper::getArticleRoute($parent->slug, $row->catid, $row->language);

                    $text = $row->introtext . $row->fulltext;
                    if ($sitemap->isImagesitemap()) {
                        $parent->images = $this->getImages($text, $row->images, $parent->secure);
                    }

                    if ($sitemap->isNewssitemap())
                        $parent->modified = $row->created;

                    $subnodes = $this->getPagebreaks($row->introtext . $row->fulltext, $parent->link);
                    $this->printNodes($sitemap, $parent, $subnodes);
                }

        }

        return $result;
    }

    /**
     * Get all content items within a content category.
     *
     * @param   object  $sitemap
     * @param   object  $parent  the menu item
     * @param   int     $catid   the id of the category to be expanded
     * @param   array   $params  an assoc array with the params for this plugin on Xmap
     * @param   int     $itemid  the itemid to use for this category's children
     */
    private function expandCategory(&$sitemap, &$parent, $catid, &$params, $itemid, $level)
    {
        $maxlevel = $parent->params->get('maxLevel');
        if ($maxlevel == -1 || $level < $maxlevel) {

            /** @var DatabaseDriver $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $app   = Factory::getApplication();
            $query = $db->getQuery(true);

            $where = array('a.parent_id = ' . $catid . ' AND a.published = 1 AND a.extension=\'com_content\'');

            if ($this->params->get('language_filter')) {
                $where[] = 'a.language in (' . $db->quote($app->getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
            }

            if (!$this->params->get('show_unauth')) {
                $where[] = 'a.access IN (' . $this->params->get('groups') . ') ';
            }

            $columns = array(
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.access'),
                $db->quoteName('a.path') . 'AS route',
                $db->quoteName('a.created_time') . 'AS created',
                $db->quoteName('a.modified_time') . 'AS modified',
                $db->quoteName('language')
            );
            $query->select($columns)
                ->from($db->quoteName('#__categories') . 'AS a')
                ->where($where);
            if (!$sitemap->isXmlsitemap())
                $query->order('a.lft');

            $db->setQuery($query);
            $items = $db->loadObjectList();

            if (count($items) > 0) {
                foreach ($items as $item) {
                    $node             = new \stdclass();
                    $node->id         = $parent->id;
                    $id               = $node->uid = $parent->uid . 'c' . $item->id;
                    $node->browserNav = $parent->browserNav;
                    $node->priority   = $this->params->get('cat_priority');
                    $node->changefreq = $this->params->get('cat_changefreq');

                    $node->name       = $item->title;
                    $node->expandible = true;
                    $node->secure     = $parent->secure;
                    // TODO: Should we include category name or metakey here?
                    // $node->keywords = $item->metakey;
                    $node->newsItem = 0;
                    $node->language = $item->language;

                    // For the google news we should use te publication date instead
                    // the last modification date. See
                    if ($sitemap->isNewssitemap())
                        $item->modified = $item->created;

                    $node->slug = $item->route ? ($item->id . ':' . $item->route) : $item->id;
                    $node->link = RouteHelper::getCategoryRoute($node->slug, $node->language);
                    if (strpos($node->link, 'Itemid=') === false) {
                        $node->itemid = $itemid;
                        $node->link .= '&Itemid=' . $itemid;
                    } else {
                        $node->itemid = preg_replace('/.*Itemid=([0-9]+).*/', '$1', $node->link);
                    }

                    if (!isset($parent->subnodes))
                        $parent->subnodes = new \stdClass();

                    $node->params = &$parent->params;

                    $parent->subnodes->$id = $node;


                    self::expandCategory($sitemap, $parent->subnodes->$id, $item->id, $params, $node->itemid, ++$level);
                }
            }
        }

        // Include Category's content
        $this->includeCategoryContent($sitemap, $parent, $catid, $params, $itemid);

        return true;
    }

    /**
     * Get all content items within a content category.
     * Returns an array of all contained content items.
     *
     * @throws \Exception
     * @since 2.0
     */
    private function includeCategoryContent(&$sitemap, &$parent, $catid, &$params, $Itemid)
    {
        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);

        $columns = array(
            $db->quoteName('a.id'),
            $db->quoteName('a.title'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.catid'),
            $db->quoteName('a.created') . ' AS created',
            $db->quoteName('a.modified') . ' AS modified',
            $db->quoteName('a.language'),
            $db->qn('a.images')
        );

        if ($sitemap->isImagesitemap() || $this->params->get('add_pagebreaks')) {
            $columns[] = $db->quoteName('a.introtext');
            $columns[] = $db->quoteName('a.fulltext');
        }

        $query->select($columns)
            ->from($db->quoteName('#__content') . ' AS a');

        $categories    = Factory::getApplication()->bootComponent("content")->getCategory(["access" => false, "published" => 0]);
        $categoryNodes = $categories->get($catid);

        # if categorie is archived and include archive is false just return
        if ($categoryNodes->published == 2 && !$this->params->get('include_archived'))
            return true;

        if ($catid != 'archived')
            if ($this->params->get('include_archived')) {
                $query->where('(' . $db->qn('a.state') . ' = 1 or ' . $db->qn('a.state') . '= 2 )');
            } else {
                $query->where($db->quoteName('a.state') . ' = 1');
            }

        if ($catid == 'featured') {
            $query->where($db->qn('a.featured') . '=1');
        } elseif ($catid == 'archived') {
            $query->where($db->qn('a.state') . '=2');
        } elseif (is_numeric($catid)) {
            $query->where($db->qn('a.catid') . '=' . (int) $catid);
        }

        if ($this->params->get('max_art_age') || $sitemap->isNewssitemap()) {
            $days = (
                (
                    $sitemap->isNewssitemap()
                    && ($this->params->get('max_art_age') > 3 || !$this->params->get('max_art_age'))
                ) ? 3 : $this->params->get('max_art_age')
            );
            $query->where($db->qn('a.created') . '>=' . $db->q(date('Y-m-d H:i:s', time() - $days * 86400)));
        }

        if ($this->params->get('language_filter')) {
            $query->where(
                $db->qn('a.language')
                . ' in (' .
                $db->quote(Factory::getApplication()->getLanguage()->getTag())
                . ',' . $db->quote('*') . ')'
            );
        }

        if (!$this->params->get('show_unauth')) {
            $query->where(
                $db->qn('a.access') . 'IN (' . $this->params->get('groups') . ')'
            );
        }

        $query->andWhere(
            array(
                $db->quoteName('a.publish_up') . 'IS NULL',
                $db->quoteName('a.publish_up') . '<=' . $this->params->get('nowDate')
            )
        )
            ->andWhere(
                array(
                    $db->quoteName('a.publish_down') . 'IS NULL',
                    $db->quoteName('a.publish_down') . '>=' . $this->params->get('nowDate')
                )
            );

        $isFp = false;
        if (!$sitemap->isXmlsitemap()) {
            $order = self::buildContentOrderBy($parent->params, $parent->id, $Itemid);
            $query->order($order);
            $isFp = str_contains($order, "fp.ordering");
        }

        if ($catid == 'featured' || $isFp)
            $query->leftJoin($db->quoteName('#__content_frontpage') . ' AS fp ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('fp.content_id'));

        if ($this->params->get('max_art'))
            $query->setLimit($this->params->get('max_art'));

        $db->setQuery($query);

        $items = $db->loadObjectList();

        if (count($items) > 0) {
            foreach ($items as $item) {
                $node             = new \stdclass();
                $node->id         = $parent->id;
                $id               = $node->uid = $parent->uid . 'a' . $item->id;
                $node->browserNav = $parent->browserNav;
                $node->priority   = $this->params->get('art_priority');
                $node->changefreq = $this->params->get('art_changefreq');

                $node->name       = $item->title;
                $node->modified   = $item->modified;
                $node->expandible = false;
                $node->secure     = $parent->secure;
                $node->newsItem   = 1;
                $node->language   = $item->language;

                // For the google news we should use te publication date instead
                // the last modification date. See
                if ($sitemap->isNewssitemap())
                    $node->modified = $item->created;

                $node->slug    = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
                $node->catslug = $item->catid;
                $node->link    = RouteHelper::getArticleRoute($node->slug, $node->catslug, $node->language);

                // Add images to the article
                $text = @$item->introtext . @$item->fulltext;
                if ($sitemap->isImagesitemap()) {
                    $node->images = $this->getImages($text, $item->images, $node->secure);
                }

                if ($this->params->get('add_pagebreaks')) {
                    $subnodes         = $this->getPagebreaks($text, $node->link);
                    $node->expandible = (count($subnodes) > 0); // This article has children
                }

                if (!isset($parent->subnodes))
                    $parent->subnodes = new \stdClass();

                $parent->subnodes->$id = $node;

                if ($node->expandible) {
                    $this->printNodes($sitemap, $parent->subnodes->$id, $subnodes);
                }
            }
        }

        return true;
    }

    private function printNodes(&$sitemap, &$parent, &$subnodes)
    {
        $i = 0;
        foreach ($subnodes as $subnode) {
            $i++;
            $subnode->id         = $parent->id;
            $id                  = $subnode->uid = $parent->uid . 'p' . $i;
            $subnode->browserNav = $parent->browserNav;
            $subnode->priority   = $this->params->get('art_priority');
            $subnode->changefreq = $this->params->get('art_changefreq');

            $subnode->secure = $parent->secure;
            if (!isset($parent->subnodes))
                $parent->subnodes = new \stdClass();

            $parent->subnodes->$id = $subnode;
        }
    }

    /**
     * Generates the order by part of the query according to the
     * menu/component/user settings. It checks if the current user
     * has already changed the article's ordering column in the frontend
     *
     * @param   Registry  $params
     * @param   int        $parentId
     * @param   int        $itemid
     *
     * @return string
     * @throws \Exception
     */
    private static function buildContentOrderBy(&$params, $parentId, $itemid)
    {
        $app = Factory::getApplication();

        // Case when the child gets a different menu itemid than it's parent
        if ($parentId != $itemid) {
            $menu       = $app->getMenu();
            $item       = $menu->getItem($itemid);
            $menuParams = clone $params;
            $itemParams = $item->getParams();
            $menuParams->merge($itemParams);
        } else {
            $menuParams =& $params;
        }

        $filter_order     = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order', 'filter_order', '', 'string');
        $filter_order_Dir = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
        $orderby          = ' ';

        if ($filter_order && $filter_order_Dir) {
            $orderby .= $filter_order . ' ' . $filter_order_Dir . ', ';
        }

        $articleOrderby   = $menuParams->get('orderby_sec', 'rdate');
        $articleOrderDate = $menuParams->get('order_date');
        $secondary        = QueryHelper::orderbySecondary($articleOrderby, $articleOrderDate) . ', ';

        $orderby .= $secondary . ' a.created ';

        return $orderby;
    }

    private static function getImages($text, $meta_images, $secure, $max = 1000)
    {
        if (!isset($urlBase)) {
            $urlBase = URI::root();
        }

        $urlBaseLen = strlen($urlBase);

        $images   = null;
        $matches1 = $matches2 = array();
        // Look <img> tags
        preg_match_all('/<img[^>]*?(?:(?:[^>]*src="(?P<src>[^"]+)")|(?:[^>]*alt="(?P<alt>[^"]+)")|(?:[^>]*title="(?P<title>[^"]+)"))+[^>]*>/i', $text, $matches1, PREG_SET_ORDER);
        // Loog for <a> tags with href to images
        preg_match_all('/<a[^>]*?(?:(?:[^>]*href="(?P<src>[^"]+\.(gif|png|jpg|jpeg))")|(?:[^>]*alt="(?P<alt>[^"]+)")|(?:[^>]*title="(?P<title>[^"]+)"))+[^>]*>/i', $text, $matches2, PREG_SET_ORDER);

        //Filter out thumbnails from popup image modals
        foreach ($matches2 as $big_image) {
            foreach ($matches1 as $key => $thumb) {
                $big_img_name_array = explode('/', $big_image['src']);
                $big_img_name       = end($big_img_name_array);

                if (str_ends_with($thumb['src'], $big_img_name)) {
                    unset($matches1[$key]);
                }
            }
        }

        $matches = array_merge($matches1, $matches2);
        if (count($matches)) {
            $images = array();

            $count = count($matches);
            $j     = 0;
            for ($i = 0; $i < $count && $j < $max; $i++) {
                if (trim($matches[$i]['src']) && (substr($matches[$i]['src'], 0, 1) == '/' || !preg_match('/^https?:\/\//i', $matches[$i]['src']) || substr($matches[$i]['src'], 0, $urlBaseLen) == $urlBase)) {
                    $src = $matches[$i]['src'];
                    if (substr($src, 0, 1) == '/') {
                        $src = substr($src, 1);
                    }
                    if (!preg_match('/^https?:\//i', $src)) {
                        $src = $urlBase . $src;
                    }
                    $image        = new \stdClass;
                    $image->src   = $src;
                    $image->title = (isset($matches[$i]['title']) ? $matches[$i]['title'] : @$matches[$i]['alt']);
                    $images[]     = $image;
                    $j++;
                }
            }
        }

        $mimages = new Registry($meta_images);
        foreach ($mimages as $k => $mimage) {
            if ($mimage != "" && ($k == "image_intro" || $k == "image_fulltext")) {
                $src = explode('#', $mimage)[0];
                if (!preg_match('/^https?:\//i', $src)) {
                    $src = $urlBase . $src;
                }
                if (!self::issetImage($src, $images)) {
                    $image      = new \stdClass;
                    $image->src = $src;
                    $images[]   = $image;
                }
            }
        }

        return $images;
    }

    /** Determine if image is already in the array
     * 
     * @param string $src The src of the cirrent wanted to be added imagee
     * @param array $images Array aof allay collected imahes
     * 
     * @return bool true if image already exists
     * @since 5.1.1
     */
    private static function issetImage($src, &$images): bool
    {
        foreach ($images as $image) {
            if (strcmp($src, $image->src) == 0)
                return true;
        }

        return false;
    }

    private static function getPagebreaks($text, $baseLink)
    {
        $matches = $subnodes = array();
        if (
            preg_match_all(
                '/<hr\s*[^>]*?(?:(?:\s*alt="(?P<alt>[^"]+)")|(?:\s*title="(?P<title>[^"]+)"))+[^>]*>/i',
                $text,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            $i = 2;
            foreach ($matches as $match) {
                if (strpos($match[0], 'class="system-pagebreak"') !== FALSE) {
                    $link = $baseLink . '&limitstart=' . ($i - 1);

                    if (@$match['alt']) {
                        $title = stripslashes($match['alt']);
                    } elseif (@$match['title']) {
                        $title = stripslashes($match['title']);
                    } else {
                        $title = Text::sprintf('SCHUWEB_SITEMAP_PAGE_NUMBER', $i);
                    }
                    $subnode             = new \stdclass();
                    $subnode->name       = $title;
                    $subnode->expandible = false;
                    $subnode->link       = $link;
                    $subnodes[]          = $subnode;
                    $i++;
                }
            }

        }
        return $subnodes;
    }
}