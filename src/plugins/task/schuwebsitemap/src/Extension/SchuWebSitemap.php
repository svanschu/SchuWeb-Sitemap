<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 - 2025 Sven Schultschik. All rights reserved
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
 * @since 5.1.0
 */
class SchuWebSitemap extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /**
     * @var string[]
     * @since 5.1.0
     */
    protected const TASKS_MAP = array(
        'schuweb.sitemap.update' => array(
            'langConstPrefix' => 'PLG_TASK_SCHUWEBSITEMAP',
            'method'          => 'updateXml',
            'form'            => 'sitemapupdate'
        )
    );

    /**
     * @var boolean
     * @since 5.1.0
     */
    protected $autoloadLanguage = true;

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 5.1.0
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
     * @since 5.1.0
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
            $this->logTask('SchuWeb Sitemap extension is not installed or has been disabled.', 'error');
            throw new RuntimeException('SchuWeb Sitemap extension is not installed or has been disabled.');
        }

        $params = $event->getArgument('params');

        $config = ['ignore_request' => true, 'pk' => (int)$params->sitemap];
        $model  = $extension->getMVCFactory()->createModel('SitemapXml', 'Site', $config);

        $this->logTask('Sitemap: ' . $model->getName());

        foreach ($params->type as $type) {
            switch ($type) {
                case 'sitemap':
                    $model->createxml();
                    $this->logTask('Sitemap XML created.');
                    break;
                case 'images':
                    $model->createxmlimages();
                    $this->logTask('Image map XML created.');
                    break;
                case 'news':
                    $model->createxmlnews();
                    $this->logTask('News map XML created.');
                    break;
            }
        }

        $this->logTask('Update XML files end');

        return Status::OK;
    }
}