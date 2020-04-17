<?php
/**
 * @author Guillermo Vargas, http://www.jooxmap.com
 * @author Sven Schultschik, http://extensions.schultschik.com
 * @version $Id$
 * @package SchuWeb_Sitemap
 * @license             GNU General Public License version 2 or later
 * @copyright           Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 */

use Joomla\Utilities\ArrayHelper;

defined( '_JEXEC' ) or die;

/** Adds support for Virtuemart categories to Xmap */
class schuweb_sitemap_com_virtuemart
{
	protected static $categoryModel;
	protected static $productModel;
	protected static $initialized = false;

	static $urlBase;
	/*
	 * This function is called before a menu item is printed. We use it to set the
	 * proper uniqueid for the item and indicate whether the node is expandible or not
	 */

	static function prepareMenuItem($node, &$params)
	{
		$app = JFactory::getApplication();

		$link_query = parse_url($node->link);

		parse_str(html_entity_decode($link_query['query']), $link_vars);

		$catid  = ArrayHelper::getValue($link_vars, 'virtuemart_category_id', 0);
		$prodid = ArrayHelper::getValue($link_vars, 'virtuemart_product_id', 0);

		if (!$catid)
		{
			$menu       = $app->getMenu();
			$menuParams = $menu->getParams($node->id);
			$catid      = $menuParams->get('virtuemart_category_id', 0);
		}

		if (!$prodid)
		{
			$menu       = $app->getMenu();
			$menuParams = $menu->getParams($node->id);
			$prodid     = $menuParams->get('virtuemart_product_id', 0);
		}

		if ($prodid && $catid)
		{
			$node->uid        = 'com_virtuemartc' . $catid . 'p' . $prodid;
			$node->expandible = false;
		}
		elseif($catid)
		{
			$node->uid        = 'com_virtuemartc' . $catid;
			$node->expandible = true;
		}
	}

    /**
     * Get the content tree for this kind of content
     * @param $sitemap
     * @param $parent
     * @param $params
     * @return bool
     *
     * @since 1.0
     */
	static function getTree($sitemap, $parent, &$params)
	{
		self::initialize();

		$link_query = parse_url($parent->link);

		parse_str(html_entity_decode($link_query['query']), $link_vars);

		$catid            = intval(ArrayHelper::getValue($link_vars, 'virtuemart_category_id', 0));
		$params['Itemid'] = intval(ArrayHelper::getValue($link_vars, 'Itemid', $parent->id));

		$view = ArrayHelper::getValue($link_vars, 'view', '');

		// we currently support only categories
		if (!in_array($view, array('categories','category')))
		{
			return true;
		}

		$include_products = ArrayHelper::getValue($params, 'include_products', 1);
		$include_products = ( $include_products == 1
			|| ( $include_products == 2 && $sitemap->view == 'xml')
			|| ( $include_products == 3 && $sitemap->view == 'html'));

		$params['include_products']          = $include_products;
		$params['include_product_images']    = (ArrayHelper::getValue($params, 'include_product_images', 1) && $sitemap->view == 'xml');
		$params['product_image_license_url'] = trim(ArrayHelper::getValue($params, 'product_image_license_url', ''));

		$priority   = ArrayHelper::getValue($params, 'cat_priority', $parent->priority);
		$changefreq = ArrayHelper::getValue($params, 'cat_changefreq', $parent->changefreq);

		if ($priority == '-1')
		{
			$priority = $parent->priority;
		}

		if ($changefreq == '-1')
		{
			$changefreq = $parent->changefreq;
		}

		$params['cat_priority']   = $priority;
		$params['cat_changefreq'] = $changefreq;

		$priority   = ArrayHelper::getValue($params, 'prod_priority', $parent->priority);
		$changefreq = ArrayHelper::getValue($params, 'prod_changefreq', $parent->changefreq);

		if ($priority == '-1')
		{
			$priority = $parent->priority;
		}

		if ($changefreq == '-1')
		{
			$changefreq = $parent->changefreq;
		}

		$params['prod_priority']   = $priority;
		$params['prod_changefreq'] = $changefreq;

		self::getCategoryTree($sitemap, $parent, $params, $catid);

		return true;
	}

    /** Virtuemart support
     * @param $sitemap
     * @param $parent
     * @param $params
     * @param int $catid
     *
     * @since 1.0
     */
	static function getCategoryTree($sitemap, $parent, &$params, $catid=0)
	{
		if (!isset($urlBase))
		{
			$urlBase = JURI::base();
		}

		$vendorId = 1;
		$cache    = JFactory::getCache('com_virtuemart','callback');
		$children = $cache->call( array( 'VirtueMartModelCategory', 'getChildCategoryList' ),$vendorId, $catid );

		$sitemap->changeLevel(1);

		foreach ($children as $row)
		{
			$node = new stdclass;

			$node->id         = $parent->id;
			$node->uid        = $parent->uid . 'c' . $row->virtuemart_category_id;
			$node->browserNav = $parent->browserNav;
			$node->name       = stripslashes($row->category_name);
			$node->priority   = $params['cat_priority'];
			$node->changefreq = $params['cat_changefreq'];
			$node->expandible = true;
			$node->link       = 'index.php?option=com_virtuemart&amp;view=category&amp;virtuemart_category_id=' . $row->virtuemart_category_id . '&amp;Itemid='.$parent->id;

			if ($sitemap->printNode($node) !== FALSE)
			{
				self::getCategoryTree($sitemap, $parent, $params, $row->virtuemart_category_id);
			}
		}

		$sitemap->changeLevel(-1);

		if ($params['include_products'] && $catid != 0)
		{
			$products = self::$productModel->getProductsInCategory($catid);

			if ($params['include_product_images'])
			{
				self::$categoryModel->addImages($products,1);
			}

			$sitemap->changeLevel(1);

			foreach ($products as $row)
			{
				$node = new stdclass;

				$node->id         = $parent->id;
				$node->uid        = $parent->uid . 'c' . $row->virtuemart_category_id . 'p' . $row->virtuemart_product_id;
				$node->browserNav = $parent->browserNav;
				$node->priority   = $params['prod_priority'];
				$node->changefreq = $params['prod_changefreq'];
				$node->name       = $row->product_name;
				$node->modified   = strtotime($row->modified_on);
				$node->expandible = false;
				$node->link       = 'index.php?option=com_virtuemart&amp;view=productdetails&amp;virtuemart_product_id=' . $row->virtuemart_product_id . '&amp;virtuemart_category_id=' . $row->virtuemart_category_id . '&amp;Itemid=' . $parent->id;

				if ($params['include_product_images'])
				{
					foreach ($row->images as $image)
					{
						if (isset($image->file_url))
						{
							$imagenode = new stdClass;

							$imagenode->src     = $urlBase . $image->file_url_thumb;
							$imagenode->title   = $row->product_name;
							$imagenode->license = $params['product_image_license_url'];

							$node->images[] = $imagenode;
						}
					}
				}

				$sitemap->printNode($node);
			}

			$sitemap->changeLevel(-1);
		}
	}

	static protected function initialize()
	{
		if (self::$initialized) return;

		$app = JFactory::getApplication ();

		if (!class_exists( 'VmConfig' ))
		{
			require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
			VmConfig::loadConfig();
		}

		JTable::addIncludePath(JPATH_VM_ADMINISTRATOR . '/tables');

		VmConfig::set ('llimit_init_FE', 9000);

		$app->setUserState('com_virtuemart.htmlc-1.limit',9000);
		$app->setUserState('com_virtuemart.htmlc0.limit',9000);
		$app->setUserState('com_virtuemart.xmlc0.limit' ,9000);

		if (!class_exists('VirtueMartModelCategory')) require(JPATH_VM_ADMINISTRATOR . '/models/category.php');
		self::$categoryModel = new VirtueMartModelCategory();

		if (!class_exists('VirtueMartModelProduct')) require(JPATH_VM_ADMINISTRATOR  . '/models/product.php');
		self::$productModel = new VirtueMartModelProduct();
	}
}
