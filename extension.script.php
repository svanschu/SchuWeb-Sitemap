<?php
/**
 * @package          Joomla
 * @subpackage       SchuWeb_Sitemap
 *
 * @author           Sven Schultschik <extensions@schultschik.com>
 * @copyright    (c) 2020 extensions.schultschik.com - All rights reserved
 * @license          GNU General Public License version 3 or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class com_schuweb_sitemapInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param string $type - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param \stdClass $parent - Parent object calling object.
     *
     * @return void
     * @since 3.2.0
     */
    public function preflight($type, $parent)
    {
        if (strcmp($type, "update") !== 0) return;

        $oldRelease = $this->getParam('version');
        if (version_compare($oldRelease, "3.2.0", "lt")) {
            //delete old obsolete files
            $this->deleteFolders = array("/components/com_schuweb_sitemap/assets/css");
            $this->removeFiles();
        }
    }
}