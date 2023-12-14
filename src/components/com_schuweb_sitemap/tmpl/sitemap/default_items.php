<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2023 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

// Create shortcut to parameters.
$params = $this->state->get('params');

function displayNode($node, $level)
{
    $level++;
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
                echo '<a href="' . $htmllink . '" title="' . $subnode->name . '" target="_blank">'
                    . $subnode->name
                    . '</a>';
                break;
            case 2: // open url in javascript popup window
                echo '<a href="' . $htmllink . '" '
                    . 'title="' . $subnode->name . '" target="_blank" '
                    . "onClick=\"window.open(this.href, 'targetWindow', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,'); return false;\">"
                    . $subnode->name
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
            displayNode($subnode, $level);
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
    <?php displayNode($node, 0) ?>
    <?php if ($this->item->params->get('columns') > 1): ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>