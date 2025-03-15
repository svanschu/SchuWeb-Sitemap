<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2024 - 2025 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class ($container->get(AdministratorApplication::class), $container->get(DatabaseInterface::class)) implements InstallerScriptInterface {
            private AdministratorApplication $app;
            private DatabaseInterface        $db;

            /**
             * The current or old version of the plugin
             * 
             * @since 5.1.1
             */
            private string $oldVersion;

            public function __construct(AdministratorApplication $app, DatabaseInterface $db)
            {
                $this->app = $app;
                $this->db  = $db;
            }

            public function install(InstallerAdapter $parent): bool
            {

                $query = $this->db->getQuery(true)
                    ->select([
                        $this->db->quoteName('id'),
                        $this->db->quoteName('title')
                    ])
                    ->from($this->db->quoteName('#__schuweb_sitemap'));

                $this->db->setQuery($query);

                $sitemaps = $this->db->loadObjectList();

                foreach ($sitemaps as $sitemap) {
                    $data['id']                               = 0;
                    $data['title']                            = $sitemap->title;
                    $data['type']                             = 'schuweb.sitemap.update';
                    $data['execution_rules']['rule-type']     = 'interval-days';
                    $data['execution_rules']['interval-days'] = 1;
                    $data['execution_rules']['exec-day']      = Factory::getDate('now', 'GMT')->__get('day');
                    $data['execution_rules']['exec-time']     = '23:00';
                    $data['cron_rules']['type']               = 'interval';
                    $data['cron_rules']['exp']                = 'P1D';
                    $data['state']                            = 1;
                    $data['params']['sitemap']                = $sitemap->id;
                    $data['params']['type']                   = ["sitemap"];

                    $extension = ComponentHelper::isEnabled('com_scheduler')
                        ? $this->app->bootComponent('com_scheduler')
                        : null;

                    if (is_null($extension)) {
                        $this->app->enqueueMessage("Scheduler extension is not installed or has been disabled.", $this->app::MSG_ERROR);
                        return false;
                    }

                    $config    = ['ignore_request' => true];
                    $taskmodel = $extension->getMVCFactory()->createModel('Task', 'Administrator', $config);

                    if (!$taskmodel->save($data)) {
                        $this->app->enqueueMessage("Failed to create task for existing sitemaps", $this->app::MSG_ERROR);
                    }
                }

                $this->app->enqueueMessage('Successful installed.');

                return true;
            }

            /**
             * Function called after the extension is updated.
             * 
             * @since 5.1.1
             */
            public function update(InstallerAdapter $parent): bool
            {
                if (version_compare($this->oldVersion, '5.1.0', '<=')) {
                    $query = $this->db->getQuery(true)
                        ->select([
                            $this->db->quoteName('id'),
                            $this->db->quoteName('type')
                        ])
                        ->from($this->db->quoteName('#__scheduler_tasks'))
                        ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('PLG_TASK_SCHUWEBSITEMAP'));

                    $this->db->setQuery($query);

                    $tasks = $this->db->loadObjectList();

                    foreach ($tasks as $task) {
                        $query = $this->db->getQuery(true)
                            ->update($this->db->quoteName('#__scheduler_tasks'))
                            ->set($this->db->quoteName('type') . '=' . $this->db->quote('schuweb.sitemap.update'))
                            ->where($this->db->quoteName('id') . '=' . $task->id);

                        $this->db->setQuery($query);

                        $result = $this->db->execute();

                        if (!$result) {
                            $this->app->enqueueMessage('Failed to update Scheduler Task ' . $task->id, Joomla\CMS\Application\CMSApplicationInterface::MSG_ERROR);
                        }
                    }
                }

                $this->app->enqueueMessage('Successful updated.');

                return true;
            }

            public function uninstall(InstallerAdapter $parent): bool
            {
                $this->app->enqueueMessage('Successful uninstalled.');

                return true;
            }

            /**
             * Function called before extension installation/update/removal procedure commences.
             * 
             * @since 5.1.1
             */
            public function preflight(string $type, InstallerAdapter $parent): bool
            {
                //workaround for bug https://github.com/joomla/joomla-cms/issues/43668
                if (!isset($parent->extension) && $parent->currentExtensionId) {
                    $parent->extension->load(['type' => $parent->type, 'element' => $parent->element, 'folder' => $parent->group]);
                }

                $cache = new Registry($parent->extension->manifest_cache);
                if (!is_null($cache->get('version')))
                    $this->oldVersion = $cache->get('version');

                return true;
            }

            public function postflight(string $type, InstallerAdapter $parent): bool
            {
                $this->deleteUnexistingFiles();

                $extension = $this->app->bootComponent('com_installer');

                $config = ['ignore_request' => true];
                $model  = $extension->getMVCFactory()->createModel('Manage', 'Administrator', $config);

                $ids = [$parent->extension->extension_id];
                $model->publish($ids, 1);

                return true;
            }

            private function deleteUnexistingFiles()
            {
                $files = [];

                if (empty($files)) {
                    return;
                }

                foreach ($files as $file) {
                    try {
                        File::delete(JPATH_ROOT . $file);
                    } catch (FilesystemException $e) {
                        echo Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file) . '<br>';
                    }
                }
            }
            }
        );
    }
};