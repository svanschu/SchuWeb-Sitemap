<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

// Create shortcut to parameters.
$params = $this->params;

?>
<div id="SchuWeb_Sitemap">
<?php if ($params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1>
            <?php if ($this->escape($params->get('page_heading'))) : ?>
                <?php echo $this->escape($params->get('page_heading')); ?>
            <?php else : ?>
                <?php echo $this->escape($params->get('page_title')); ?>
            <?php endif; ?>
        </h1>
    </div>
<?php endif; ?>

<?php if ($params->get('showintro', 1) )  : ?>
    <?php echo $this->item->introtext; ?>
<?php endif; ?>

    <?php echo $this->loadTemplate('items'); ?>

<?php if ($params->get('include_link', 1) )  : ?>
    <div class="muted" style="font-size:10px;width:100%;clear:both;text-align:center;">Powered by <a target="_blank" href="https://extensions.schultschik.com/">SchuWeb Sitemap</a></div>
<?php endif; ?>

    <span class="article_separator">&nbsp;</span>
</div>