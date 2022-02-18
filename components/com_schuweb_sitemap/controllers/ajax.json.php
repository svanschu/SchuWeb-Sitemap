<?php

/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2005 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * SchuWeb_Sitemap Ajax Controller
 *
 * @package      SchuWeb_Sitemap
 * @subpackage   com_schuweb_sitemap
 * @since        2.0
 */
class SchuWeb_SitemapControllerAjax extends JControllerLegacy
{

    public function editElement()
    {
        JSession::checkToken('get') or jexit(JText::_('JINVALID_TOKEN'));

        jimport('joomla.utilities.date');
        jimport('joomla.user.helper');
        $user = JFactory::getApplication()->getIdentity();
        $groups = array_keys(JUserHelper::getUserGroups($user->get('id')));
        $result = new JRegistry('_default');
        $jinput = JFactory::$application->input;
        $sitemapId = $jinput->getInt('id');

        if (!$user->authorise('core.edit', 'com_schuweb_sitemap.sitemap.' . $sitemapId)) {
            $result->setValue('result', 'KO');
            $result->setValue('message', 'You are not authorized to perform this action!');
        } else {
            $model = $this->getModel('sitemap');
            $state = false;
            if ($model->getItem()) {
                $action = $jinput->getCmd('action', '');
                $uid = $jinput->getCmd('uid', '');
                $itemid = $jinput->getInt('itemid', '');
                switch ($action) {
                    case 'toggleElement':
                        if ($uid && $itemid) {
                            $state = $model->toggleItem($uid, $itemid);
                        }
                        break;
                    case 'changeProperty':
                        $uid = $jinput->getCmd('uid', '');
                        $property = $jinput->getCmd('property', '');
                        $value = $jinput->getCmd('value', '');
                        if ($uid && $itemid && $uid && $property) {
                            $state = $model->chageItemPropery($uid, $itemid, 'xml', $property, $value);
                        }
                        break;
                }
            }
            $result->set('result', 'OK');
            $result->set('state', $state);
            $result->set('message', '');
        }

        echo $result->toString();
    }
}