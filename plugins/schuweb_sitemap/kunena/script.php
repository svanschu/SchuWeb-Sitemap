<?php
/**
 * @package    Joomla.Language
 *
 * @copyright  (C) 2022 J!German <https://www.jgerman.de>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  4.0.0v1
 */
class Plg_kunenaInstallerScript extends InstallerScript
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
}