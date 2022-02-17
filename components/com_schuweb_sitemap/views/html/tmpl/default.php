<?php
/**
 * @version         sw.build.version
 * @copyright       Copyright (C) 2005 - 2009 Joomla! Vargas. All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 * @author          Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

// Create shortcut to parameters.
$params = $this->item->params;

?>
<div id="SchuWeb_Sitemap">
<?php if ($params->get('show_page_heading', 1) && $params->get('page_heading') != '') : ?>
    <h1>
        <?php echo $this->escape($params->get('page_heading')); ?>
    </h1>
<?php endif; ?>

<?php if ($params->get('showintro', 1) )  : ?>
    <?php echo $this->item->introtext; ?>
<?php endif; ?>

    <?php echo $this->loadTemplate('items'); ?>

<?php if ($params->get('include_link', 1) )  : ?>
    <div class="muted" style="font-size:10px;width:100%;clear:both;text-align:center;">Powered by <a target="_blank" href="http://extensions.schultschik.com/">SchuWeb Sitemap</a></div>
<?php endif; ?>

    <span class="article_separator">&nbsp;</span>
</div>