<?php
/**
 * @package             SchuWeb Sitemap
 *
 * @version             sw.build.version
 * @author              Sven Schultschik
 * @copyright (C)       2022 Sven Schultschik. All rights reserved
 * @license             http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link                http://www.schultschik.de
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  4.0.0v1
 */
class Plgschuweb_sitemapkunenaInstallerScript extends InstallerScript
{
    /**
     * Extension script constructor.
     *
     * @since   4.0.0v1
     */
    public function __construct()
    {
        // Define the minumum versions to be supported.
        $this->minimumJoomla = '4.0';
        $this->minimumPhp = '7.4';
    }

    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param string $type - Type of PostFlight action. Possible values
     *                              are:
     *                              - * install
     *                              - * update
     *                              - * discover_install
     * @param stdClass $parent - Parent object calling object.
     *
     * @return void
     *
     * @throws Exception
     * @since  4.0
     */
    public function postflight($type, $parent)
    {
        $app = Factory::getApplication();
        $db = Factory::getDbo();

        $query = $db->getQuery(true);
        $query->select(
            $db->quoteName('enabled') . ','
            . $db->quoteName('access') . ','
            . $db->quoteName('protected') . ','
            . $db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . '=' . $db->quote('com_kunena'))
            ->where($db->quoteName('type') . '=' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . '=' . $db->quote('schuweb_sitemap'));

        $db->setQuery($query);

        $plugin = $db->loadAssoc();

        if ($plugin) {
            if ($plugin['enabled'] === 1) {
                $columns = array(
                    $db->quoteName('enabled') . '= 1',
                    $db->quoteName('access') . '=' . $plugin['access'],
                    $db->quoteName('protected') . '=' . $plugin['protected'],
                    $db->quoteName('params') . '=' . $db->quote($plugin['params'])
                );
                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__extensions'))
                    ->set($columns)
                    ->where($db->quoteName('element') . '=' . $db->quote('kunena'))
                    ->where($db->quoteName('type') . '=' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . '=' . $db->quote('schuweb_sitemap'));
                $db->setQuery($query);
                try {
                    $db->execute();
                } catch (\RuntimeException $ex) {
                    $app->enqueueMessage("Could not copy settings from old SchuWeb Sitemap plugin kunena. You need to do it manually.", $app::MSG_ERROR);
                }

                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__extensions'))
                    ->set($db->quoteName('enabled') . '=0')
                    ->where($db->quoteName('element') . '=' . $db->quote('com_kunena'))
                    ->where($db->quoteName('type') . '=' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . '=' . $db->quote('schuweb_sitemap'));
                $db->setQuery($query);
                try {
                    $db->execute();
                } catch (\RuntimeException $ex) {
                    $app->enqueueMessage("Could not disable old SchuWeb Sitemap Plugin com_kunena. Please check and disable manually.");
                }
            }

            $app->enqueueMessage("Plugin com_kunena for SchuWeb Sitemap detected. Please check if all settings are transferred correctly and uninstall the old com_kunena plugin.");
        }

    }
}