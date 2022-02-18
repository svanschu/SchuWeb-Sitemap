<?php
/**
 * @version              sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license              GNU General Public License version 2 or later; see LICENSE.txt
 * @author               Sven Schultschik (extensions@schultschik.de)
 */

// no direct access
defined('_JEXEC') or die;

$function = JFactory::$application->input->getVar('function', 'jSelectSitemap');

?>

<table class="table table-striped">
    <thead>
    <tr>
        <th>
			<?php echo JText::_('SCHUWEB_SITEMAP_Heading_Sitemap') ?>
        </th>
        <th>
			<?php echo JText::_('SCHUWEB_SITEMAP_Heading_Published') ?>
        </th>
        <th>
			<?php echo JText::_('JGrid_Heading_Access') ?>
        </th>
    </tr>
    </thead>
	<?php
	foreach ($this->items as $i => $item) :
		?>
        <tr>
            <td>
                <a style="cursor: pointer;"
                   onclick="window.parent.<?php echo $function;?>('<?php echo $item->id; ?>', '<?php echo $this->escape($item->title); ?>');">
					<?php echo $this->escape($item->title); ?></a>
            </td>
            <td>
				<?php echo JHtml::_('jgrid.published', $item->state, $i, 'sitemaps.'); ?>
            </td>
            <td>
				<?php echo $this->escape($item->access_level); ?>
            </td>
            <td>
				<?php echo (int) $item->id; ?>
            </td>
        </tr>
	<?php endforeach; ?>
</table>
<input type="hidden" name="tmpl" value="component"/>
<input type="hidden" name="task" value=""/>
<input type="hidden" name="boxchecked" value="0"/>

