<?php
/**
 * @version          sw.build.version
 * @copyright        Copyright (C) 2021 Sven Schultschik. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 * @author           Sven Schultschik (sven@schultschik.de)
 */
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4', 'lt'))
{
	echo $this->loadTemplate('j3');
} else {
	echo $this->loadTemplate('j4');
}