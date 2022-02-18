<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Sven Schultschik (extensions@schultschik.de)
 */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Include dependencies
jimport('joomla.application.component.controller');

require_once(JPATH_COMPONENT.'/displayer.php');

$controller = JControllerLegacy::getInstance('SchuWeb_Sitemap');
$task = JFactory::getApplication()->input->get('task');
$controller->execute($task);
$controller->redirect();
