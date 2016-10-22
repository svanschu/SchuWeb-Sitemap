<?php

/**
 * @author Guillermo Vargas, http://www.jooxmap.com
 * @author Sven Schultschik, http://extensions.schultschik.de
 * @email extensions@schultschik.de
 * @version $Id$
 * @package SchuWeb Sitemap
 * @license GNU/GPL
 * @description SchuWeb Sitemap plugin for Kunena Forum Component.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/** Handles Kunena forum structure */
class schuweb_sitemap_com_kunena
{
    /*
     * This function is called before a menu item is printed. We use it to set the
     * proper uniqueid for the item
     */

    static $profile;
    static $config;

    static function getTree($xmap, $parent, &$params)
    {
        if ($xmap->isNews) // This component does not provide news content. don't waste time/resources
            return false;

        // Make sure that we can load the kunena api
        if (!schuweb_sitemap_com_kunena::loadKunenaApi()) {
            return false;
        }

        if (!self::$profile) {
            self::$config = KunenaFactory::getConfig();;
            self::$profile = KunenaFactory::getUser();
        }

        $user = JFactory::getUser();
        $catid = 0;

        $link_query = parse_url($parent->link);
        if (!isset($link_query['query'])) {
            return;
        }

        parse_str(html_entity_decode($link_query['query']), $link_vars);
        $view = ArrayHelper::getValue($link_vars, 'view', '');

        switch ($view) {
            case 'showcat':
            case 'category':
                $link_query = parse_url($parent->link);
                parse_str(html_entity_decode($link_query['query']), $link_vars);
                $catid = ArrayHelper::getValue($link_vars, 'catid', 0);
                break;
            case 'listcat':
            case 'entrypage':
                $catid = 0;
                break;
            default:
                return true;   // Do not expand links to posts
        }

        $include_topics = ArrayHelper::getValue($params, 'include_topics', 1);
        $include_topics = ($include_topics == 1
            || ($include_topics == 2 && $xmap->view == 'xml')
            || ($include_topics == 3 && $xmap->view == 'html')
            || $xmap->view == 'navigator');
        $params['include_topics'] = $include_topics;

        $priority = ArrayHelper::getValue($params, 'cat_priority', $parent->priority);
        $changefreq = ArrayHelper::getValue($params, 'cat_changefreq', $parent->changefreq);
        if ($priority == '-1')
            $priority = $parent->priority;
        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $params['cat_priority'] = $priority;
        $params['cat_changefreq'] = $changefreq;
        $params['groups'] = implode(',', $user->getAuthorisedViewLevels());

        $priority = ArrayHelper::getValue($params, 'topic_priority', $parent->priority);
        $changefreq = ArrayHelper::getValue($params, 'topic_changefreq', $parent->changefreq);
        if ($priority == '-1')
            $priority = $parent->priority;

        if ($changefreq == '-1')
            $changefreq = $parent->changefreq;

        $params['topic_priority'] = $priority;
        $params['topic_changefreq'] = $changefreq;

        if ($include_topics) {
            $ordering = ArrayHelper::getValue($params, 'topics_order', 'ordering');
            if (!in_array($ordering, array('id', 'ordering', 'time', 'subject', 'hits')))
                $ordering = 'ordering';
            $params['topics_order'] = 't.`' . $ordering . '`';
            $params['include_pagination'] = ($xmap->view == 'xml');

            $params['limit'] = '';
            $params['days'] = '';
            $limit = ArrayHelper::getValue($params, 'max_topics', '');
            if (intval($limit))
                $params['limit'] = $limit;

            $days = ArrayHelper::getValue($params, 'max_age', '');
            $params['days'] = false;
            if (intval($days))
                $params['days'] = ($xmap->now - (intval($days) * 86400));
        }

        $params['table_prefix'] = '#__kunena';

        schuweb_sitemap_com_kunena::getCategoryTree($xmap, $parent, $params, $catid);
    }

    /*
     * Builds the Kunena's tree
     */
    static function getCategoryTree($xmap, $parent, &$params, $parentCat)
    {
        // Load categories

        // kimport('kunena.forum.category.helper');
        $categories = KunenaForumCategoryHelper::getChildren($parentCat);

        /* get list of categories */
        $xmap->changeLevel(1);
        foreach ($categories as $cat) {
            $node = new stdclass;
            $node->id = $parent->id;
            $node->browserNav = $parent->browserNav;
            $node->uid = 'com_kunenac' . $cat->id;
            $node->name = $cat->name;
            $node->priority = $params['cat_priority'];
            $node->changefreq = $params['cat_changefreq'];
            $node->link = KunenaRoute::normalize('index.php?option=com_kunena&view=category&catid=' . $cat->id);
            $node->expandible = true;
            $node->secure = $parent->secure;
            if ($xmap->printNode($node) !== FALSE) {
                schuweb_sitemap_com_kunena::getCategoryTree($xmap, $parent, $params, $cat->id);
            }
        }

        if ($params['include_topics']) {
            // Kunena 2.0+
            // kimport('kunena.forum.topic.helper');
            // TODO: orderby parameter is missing:
            $topics = KunenaForumTopicHelper::getLatestTopics($parentCat, 0, $params['limit'], array('starttime', $params['days']));
            if (count($topics) == 2 && is_numeric($topics[0])) {
                $topics = $topics[1];
            }

            //get list of topics
            foreach ($topics as $topic) {
                $node = new stdclass;
                $node->id = $parent->id;
                $node->browserNav = $parent->browserNav;
                $node->uid = 'com_kunenat' . $topic->id;
                $node->name = $topic->subject;
                $node->priority = $params['topic_priority'];
                $node->changefreq = $params['topic_changefreq'];
                $node->modified = intval(@$topic->last_post_time ? $topic->last_post_time : $topic->time);
                $node->link = KunenaRoute::normalize('index.php?option=com_kunena&view=topic&catid=' . $topic->category_id . '&id=' . $topic->id);
                $node->expandible = false;
                $node->secure = $parent->secure;
                if ($xmap->printNode($node) !== FALSE) {
                    // Pagination will not work with K2.0, revisit this when that version is out and stable
                    if ($params['include_pagination'] && isset($topic->msgcount) && $topic->msgcount > self::$config->messages_per_page) {
                        $msgPerPage = self::$config->messages_per_page;
                        $threadPages = ceil($topic->msgcount / $msgPerPage);
                        for ($i = 2; $i <= $threadPages; $i++) {
                            $subnode = new stdclass;
                            $subnode->id = $node->id;
                            $subnode->uid = $node->uid . 'p' . $i;
                            $subnode->name = "[$i]";
                            $subnode->seq = $i;
                            $subnode->link = $node->link . '&limit=' . $msgPerPage . '&limitstart=' . (($i - 1) * $msgPerPage);
                            $subnode->browserNav = $node->browserNav;
                            $subnode->priority = $node->priority;
                            $subnode->changefreq = $node->changefreq;
                            $subnode->modified = $node->modified;
                            $subnode->secure = $node->secure;
                            $xmap->printNode($subnode);
                        }
                    }
                }
            }
        }
        $xmap->changeLevel(-1);
    }

    private static function loadKunenaApi()
    {
        if (!defined('KUNENA_LOADED')) {
            jimport('joomla.application.component.helper');
            // Check if Kunena component is installed/enabled
            if (!JComponentHelper::isEnabled('com_kunena', true)) {
                return false;
            }

            // Check if Kunena API exists
            $kunena_api = JPATH_ADMINISTRATOR . '/components/com_kunena/api.php';
            if (!is_file($kunena_api))
                return false;

            // Load Kunena API
            require_once($kunena_api);
        }
        return true;
    }
}
