<?php
/**
 * @package             SchuWeb Sitemap
 *
 * @version             sw.build.version
 * @author              Sven Schultschik
 * @copyright (C)       2010 - 2022 Sven Schultschik. All rights reserved
 * @license             http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link                http://www.schultschik.de
 **/

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\Component\Weblinks\Site\Helper\RouteHelper;
use Joomla\Component\Weblinks\Site\Model\CategoryModel;

class schuweb_sitemap_com_weblinks
{

	static private $_initialized = false;

	/*
	 * This function is called before a menu item is printed. We use it to set the
	 * proper uniqueid for the item and indicate whether the node is expandible or not
	 */

	static function prepareMenuItem($node, &$params)
	{
		$link_query = parse_url($node->link);
		parse_str(html_entity_decode($link_query['query']), $link_vars);
		$view = ArrayHelper::getValue($link_vars, 'view', '');
		if ($view == 'weblink')
		{
			$id = intval(ArrayHelper::getValue($link_vars, 'id', 0));
			if ($id)
			{
				$node->uid        = 'com_weblinksi' . $id;
				$node->expandible = false;
			}
		}
		elseif ($view == 'categories')
		{
			$node->uid        = 'com_weblinkscategories';
			$node->expandible = true;
		}
		elseif ($view == 'category')
		{
			$catid            = intval(ArrayHelper::getValue($link_vars, 'id', 0));
			$node->uid        = 'com_weblinksc' . $catid;
			$node->expandible = true;
		}
	}

	static function getTree($sitemap, $parent, &$params)
	{
		self::initialize($params);

		$app             = JFactory::getApplication();
		$weblinks_params = $app->getParams('schuweb_sitemap_weblinks');

		$link_query = parse_url($parent->link);
		parse_str(html_entity_decode($link_query['query']), $link_vars);
		$view = ArrayHelper::getValue($link_vars, 'view', 0);

		if ($view == 'category')
		{
			$catid = intval(ArrayHelper::getValue($link_vars, 'id', 0));
		}
		elseif ($view == 'categories')
		{
			$catid = 0;
		}
		else
		{ // Only expand category menu items
			return;
		}

		$include_links           = ArrayHelper::getValue($params, 'include_links', 1);
		$include_links           = ($include_links == 1
			|| ($include_links == 2 && $sitemap->view == 'xml')
			|| ($include_links == 3 && $sitemap->view == 'html'));
		$params['include_links'] = $include_links;

		$priority   = ArrayHelper::getValue($params, 'cat_priority', $parent->priority);
		$changefreq = ArrayHelper::getValue($params, 'cat_changefreq', $parent->changefreq);
		if ($priority == '-1')
			$priority = $parent->priority;
		if ($changefreq == '-1')
			$changefreq = $parent->changefreq;

		$params['cat_priority']   = $priority;
		$params['cat_changefreq'] = $changefreq;

		$priority   = ArrayHelper::getValue($params, 'link_priority', $parent->priority);
		$changefreq = ArrayHelper::getValue($params, 'link_changefreq', $parent->changefreq);
		if ($priority == '-1')
			$priority = $parent->priority;

		if ($changefreq == '-1')
			$changefreq = $parent->changefreq;

		$params['link_priority']   = $priority;
		$params['link_changefreq'] = $changefreq;

		$options               = array();
		$options['countItems'] = false;
		$options['catid']      = rand();
		$categories            = JCategories::getInstance('Weblinks', $options);
		$category              = $categories->get($catid ?: 'root', true);

		$params['count_clicks'] = $weblinks_params->get('count_clicks');

		schuweb_sitemap_com_weblinks::getCategoryTree($sitemap, $parent, $params, $category);
	}

	static function getCategoryTree($sitemap, $parent, &$params, $category)
	{
		$children = $category->getChildren();
		$sitemap->changeLevel(1);
		foreach ($children as $cat)
		{
			$node       = new stdclass;
			$node->id   = $parent->id;
			$node->uid  = $parent->uid . 'c' . $cat->id;
			$node->name = $cat->title;
            $node->link = RouteHelper::getCategoryRoute($cat);
			$node->priority   = $params['cat_priority'];
			$node->changefreq = $params['cat_changefreq'];

			$node->lastmod  = $parent->lastmod;
			$node->modified = $cat->modified_time;

			$attribs                   = json_decode($sitemap->sitemap->attribs);
			$node->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
			$node->xmlInsertPriority   = $attribs->xmlInsertPriority;

			$node->expandible = true;
			if ($sitemap->printNode($node) !== false)
			{
				schuweb_sitemap_com_weblinks::getCategoryTree($sitemap, $parent, $params, $cat);
			}
		}
		$sitemap->changeLevel(-1);

		if ($params['include_links'])
		{ //view=category&catid=...
			$linksModel = new CategoryModel();
			$linksModel->getState(); // To force the populate state
			$linksModel->setState('list.limit', ArrayHelper::getValue($params, 'max_links'));
			$linksModel->setState('list.start', 0);
			$linksModel->setState('list.ordering', 'ordering');
			$linksModel->setState('list.direction', 'ASC');
			$linksModel->setState('category.id', $category->id);
			$links = $linksModel->getItems();
			$sitemap->changeLevel(1);
			foreach ($links as $link)
			{
				$item_params = new JRegistry;
				$item_params->loadString($link->params);

				$node       = new stdclass;
				$node->id   = $parent->id;
				$node->uid  = $parent->uid . 'i' . $link->id;
				$node->name = $link->title;

				// Find the Itemid
                $Itemid = intval(preg_replace('/.*Itemid=([0-9]+).*/', '$1', RouteHelper::getWeblinkRoute($link->id, $category->id)));

				if ($item_params->get('count_clicks', $params['count_clicks']) == 1)
				{
					$node->link = 'index.php?option=schuweb_sitemap_weblinks&task=weblink.go&id=' . $link->id . '&Itemid=' . ($Itemid ?: $parent->id);
				}
				else
				{
					$node->link = $link->url;
				}
				$node->priority   = $params['link_priority'];
				$node->changefreq = $params['link_changefreq'];

				$attribs                   = json_decode($sitemap->sitemap->attribs);
				$node->xmlInsertChangeFreq = $attribs->xmlInsertChangeFreq;
				$node->xmlInsertPriority   = $attribs->xmlInsertPriority;

				$node->lastmod  = $parent->lastmod;
				$node->modified = $link->modified;

				$node->expandible = false;
				$sitemap->printNode($node);
			}
			$sitemap->changeLevel(-1);
		}
	}

	static public function initialize(&$params)
	{
		if (self::$_initialized)
		{
			return;
		}

		self::$_initialized = true;
	}
}