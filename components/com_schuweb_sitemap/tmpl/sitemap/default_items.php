<?php
/**
 * @version             sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Sven Schultschik (extensions@schultschik.de)
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

// Create shortcut to parameters.
$params = $this->state->get('params');

$extlinks = $this->item->params->get('exlinks');

function displayNode($node, $level, $extlinks)
{
    $level++;
    $live_site = substr_replace(Uri::root(), "", -1, 1);
    echo '<ul class="level_' . $level . '">';
    foreach ($node->subnodes as $subnode) {
        echo '<li>';
        $htmllink = '';
        if (isset($subnode->htmllink)) {
            $htmllink = $subnode->htmllink;
        } else {
            $htmllink = Route::link('site', $subnode->link, true, @$subnode->secure);
        }
        switch ($subnode->browserNav) {
            case 1: // open url in new window
                $extim = '';
                if ($extlinks) {
                    $extim = '<img src="' . $live_site . '/components/com_schuweb_sitemap/assets/images/'
                        . $extlinks . '"      alt="'
                        . Text::_('COM_SCHUWEB_SITEMAP_SHOW_AS_EXTERN_ALT')
                        . '" title="'
                        . Text::_('COM_SCHUWEB_SITEMAP_SHOW_AS_EXTERN_ALT')
                        . '" border="0" />';
                }
                echo '<a href="' . $htmllink . '" title="' . $subnode->name . '" target="_blank">'
                    . $subnode->name . $extim
                    . '</a>';
                break;
            case 2: // open url in javascript popup window
                echo '<a href="' . $htmllink . '" '
                    . 'title="' . $subnode->name . '" target="_blank" '
                    . "onClick=\"window.open(this.href, 'targetWindow', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,'); return false;\">"
                    . $subnode->name
                    . '<img src="' . $live_site . '/components/com_schuweb_sitemap/assets/images/'
                    . $extlinks . '" '
                    . 'alt="' . Text::_('COM_SCHUWEB_SITEMAP_SHOW_AS_EXTERN_ALT') . '" '
                    . 'title="' . Text::_('COM_SCHUWEB_SITEMAP_SHOW_AS_EXTERN_ALT') . '" border="0" />'
                    . '</a>';
                break;
            case 3: // no link
                echo '<span>'
                    . $subnode->name
                    . '</span>';
                break;
            default: // open url in parent window
                echo '<a href="' . $htmllink . '" title="' . $subnode->name . '">'
                    . $subnode->name
                    . '</a>';
                break;
        }
        if (isset($subnode->subnodes)) {
            displayNode($subnode, $level, $extlinks);
        }
        ;
        echo '</li>';
    }
    echo '</ul>';
}

?>

<?php foreach ($this->nodes as $node): ?>
    <?php if ($this->item->params->get('columns') > 1): ?>
        <div style="float:left;width:<?php echo $this->getWidth() ?>%;">
        <?php endif; ?>
    <?php if ($this->item->params->get('show_menutitle')): ?>
        <h2 class="menutitle">
            <?php echo $node->name ?>
        </h2>
    <?php endif; ?>
    <?php displayNode($node, 0, $extlinks) ?>
    <?php if ($this->item->params->get('columns') > 1): ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>