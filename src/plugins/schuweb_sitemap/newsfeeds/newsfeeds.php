<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2023 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\Component\Newsfeeds\Site\Helper\RouteHelper;

class schuweb_sitemap_newsfeeds
{
	/*
	 * This function is called before a menu item is printed. We use it to set the
	 * proper uniqueid for the item and indicate whether the node is expandible or not
	 */

	static function prepareMenuItem(&$node, &$params)
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

	static function getTree(&$sitemap, &$parent, &$params)
	{
        //Image sitemap does not make sense for newsfeeds
        if ($sitemap->isImagesitemap())
            return false;

		$newsfeed_query = parse_url($parent->link);
		parse_str(html_entity_decode($newsfeed_query['query']), $newsfeed_vars);
		$view = ArrayHelper::getValue($newsfeed_vars, 'view', 0);

		if ($view == 'category') {
			$catid = intval(ArrayHelper::getValue($newsfeed_vars, 'id', 0));
		} elseif ($view == 'categories') {
			$catid = 0;
		} else { // Only expand category menu items
			return;
		}

		$include_newsfeeds = ArrayHelper::getValue($params, 'include_newsfeeds', 1);
		$include_newsfeeds = ($include_newsfeeds == 1
			|| ($include_newsfeeds == 2 && $sitemap->isXmlsitemap())
			|| ($include_newsfeeds == 3 && !$sitemap->isXmlsitemap()));
		$params['include_newsfeeds'] = $include_newsfeeds;

		$priority = ArrayHelper::getValue($params, 'cat_priority', $parent->priority);
		$changefreq = ArrayHelper::getValue($params, 'cat_changefreq', $parent->changefreq);
		if ($priority == '-1')
			$priority = $parent->priority;
		if ($changefreq == '-1')
			$changefreq = $parent->changefreq;

		$params['cat_priority'] = $priority;
		$params['cat_changefreq'] = $changefreq;

		$priority = ArrayHelper::getValue($params, 'newsfeed_priority', $parent->priority);
		$changefreq = ArrayHelper::getValue($params, 'newsfeed_changefreq', $parent->changefreq);
		if ($priority == '-1')
			$priority = $parent->priority;

		if ($changefreq == '-1')
			$changefreq = $parent->changefreq;

		$params['newsfeed_priority'] = $priority;
		$params['newsfeed_changefreq'] = $changefreq;

		$options = array();
		$options['countItems'] = false;
		$options['catid'] = rand();
		$categories = Categories::getInstance('Newsfeeds', $options);
		$category = $categories->get($catid ?: 'root', true);

		self::getCategoryTree($sitemap, $parent, $params, $category);
	}

	static function getCategoryTree(&$sitemap, &$parent, &$params, &$category)
	{
		$children = $category->getChildren();

		foreach ($children as $cat) {
			$node = new stdclass;
			$node->id = $parent->id;
			$id = $node->uid = $parent->uid . 'c' . $cat->id;
			$node->name = $cat->title;
			$node->link = RouteHelper::getCategoryRoute($cat);
			$node->priority = $params['cat_priority'];
			$node->changefreq = $params['cat_changefreq'];
			$node->browserNav = $parent->browserNav;
			$node->xmlInsertChangeFreq = $parent->xmlInsertChangeFreq;
			$node->xmlInsertPriority = $parent->xmlInsertPriority;

			$node->lastmod = $parent->lastmod;
			$node->modified = $cat->modified_time;

			$node->expandible = true;

			if (!isset($parent->subnodes))
				$parent->subnodes = new \stdClass();

			$node->params = &$parent->params;

			$parent->subnodes->$id = $node;


			self::getCategoryTree($sitemap, $parent->subnodes->$id, $params, $cat);

		}

		if ($params['include_newsfeeds']) {
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select(
				array(
					$db->qn('id'),
					$db->qn('name'),
					$db->qn('link'),
					$db->qn('params'),
					$db->qn('modified')
				)
			)
				->from($db->qn('#__newsfeeds'))
				->setLimit(ArrayHelper::getValue($params, 'max_newsfeeds', null, 'INT'))
				->order($db->escape('ordering') . ' ' . $db->escape('ASC'))
				->where($db->qn('catid') . ' = ' . $db->q($category->id));

			$db->setQuery($query);

			$newsfeeds = $db->loadObjectList();

			foreach ($newsfeeds as $newsfeed) {
				$item_params = new Registry;
				$item_params->loadString($newsfeed->params);

				$node = new stdclass;
				$node->id = $parent->id;
				$id = $node->uid = $parent->uid . 'i' . $newsfeed->id;
				$node->name = $newsfeed->name;

				$node->browserNav = $parent->browserNav;

				$node->link = $newsfeed->link;
				$node->priority = $params['newsfeed_priority'];
				$node->changefreq = $params['newsfeed_changefreq'];

				$node->lastmod = $parent->lastmod;
				$node->modified = $newsfeed->modified;

				$node->xmlInsertChangeFreq = $parent->xmlInsertChangeFreq;
				$node->xmlInsertPriority = $parent->xmlInsertPriority;

				$node->expandible = false;

				if (!isset($parent->subnodes))
					$parent->subnodes = new \stdClass();

				$parent->subnodes->$id = $node;
			}
		}
	}
}