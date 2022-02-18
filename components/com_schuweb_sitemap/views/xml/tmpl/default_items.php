<?php
/**
 * @version             sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Sven Schultschik (extensions@schultschik.de)
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Create shortcut to parameters.
$params = $this->state->get('params');

// Use the class defined in default_class.php to print the sitemap
$this->displayer->printSitemap();