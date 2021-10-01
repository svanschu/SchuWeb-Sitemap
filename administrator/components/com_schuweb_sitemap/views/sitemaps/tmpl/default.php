<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4', 'lt'))
{
	echo $this->loadTemplate('j3');
} else {
	echo $this->loadTemplate('j4');
}
?>

