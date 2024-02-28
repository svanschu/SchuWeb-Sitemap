<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Plugin\Task\SchuWebSitemap\Extension;

defined('_JEXEC') or die;

use RuntimeException;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Application\ConsoleApplication;

/**
 * A task plugin. Offers 1 task routines Update XML SItemap
 * {@see ExecuteTaskEvent}.
 *
 * @since __BUMP_VERSION__
 */
class SchuWebSitemap extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /**
     * @var string[]
     * @since __BUMP_VERSION__
     */
    protected const TASKS_MAP = array(
        'PLG_TASK_SCHUWEBSITEMAP' => array(
            'langConstPrefix' => 'PLG_TASK_SCHUWEBSITEMAP',
            'method'          => 'updateXml',
            'form'            => 'sitemapupdate'
        )
    );

    /**
     * @var boolean
     * @since __BUMP_VERSION__
     */
    protected $autoloadLanguage = true;

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since __BUMP_VERSION__
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * update the XML files of a sitemap
     * 
     * @since __BUMP_VERSION__
     */
    private function updateXml(ExecuteTaskEvent $event): int
    {
        $app = $this->getApplication();
        if ($app instanceof ConsoleApplication){
            $this->logTask("Due to bugs in Joomla! Route::link() this task currently can't be run on the CLI only as Webtask", 'error');
            return Status::NO_RUN;
        }

        $extension = ComponentHelper::isEnabled('com_schuweb_sitemap')
            ? $this->getApplication()->bootComponent('com_schuweb_sitemap')
            : null;

        if (!($extension instanceof MVCFactoryServiceInterface)) {
            throw new RuntimeException('SchuWeb Sitemap extension is not installed or has been disabled.');
        }

        $params = $event->getArgument('params');

        $config = ['ignore_request' => true, 'pk' => (int)$params->sitemap];
        $model  = $extension->getMVCFactory()->createModel('SitemapXml', 'Site', $config);

        foreach ($params->type as $type) {
            switch ($type) {
                case 'sitemap':
                    $model->createxml();
                    break;
                case 'images':
                    $model->createxmlimages();
                    break;
                case 'news':
                    $model->createxmlnews();
                    break;
            }
        }

        return Status::OK;
    }
}