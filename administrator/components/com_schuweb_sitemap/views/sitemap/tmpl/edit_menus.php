<?php
/**
 * @version       sw.build.version
 * @copyright     Copyright (C) 2019-2021 Sven Schultschik. All rights reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @author        Sven Schultschik (https://extensions.schultschik.com)
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
?>
<table class="table" id="menuList">
    <thead>
    <tr>
        <th class="w-1 text-center">
            <?php echo HTMLHelper::_('grid.checkall'); ?>
        </th>
        <th scope="col">
            <?php echo Text::_('JGLOBAL_TITLE'); ?>
        </th>
        <th scope="col" class="w-10 d-none d-md-table-cell">
            <?php echo Text::_('SCHUWEB_SITEMAP_PRIORITY'); ?>
        </th>
        <th scope="col" class="w-10 d-none d-md-table-cell">
            <?php echo Text::_('SCHUWEB_SITEMAP_CHANGE_FREQUENCY'); ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    foreach ($this->item->selections as $key => $menu): ?>
        <tr class="row<?php echo $i % 2; ?>">
            <td class="text-center">
                <input type="checkbox" id="cb<?php echo $i; ?>"
                       name="jform[selections][<?php echo $menu['menutype']; ?>][enabled]"
                       value="1" <?php if (isset($menu['enabled'])) {echo $menu['enabled'] ? 'checked="checked"' : '';} ?> />
            </td>
            <td>
                <label for="cb<?php echo $i; ?>"><?php echo $this->escape($menu['title']); ?></label>
            </td>
            <td>
                <?php echo HTMLHelper::_('schuweb_sitemap.priorities', 'jform[selections][' . $menu['menutype'] . '][priority]', $menu['priority'], $i); ?>
            </td>
            <td>
                <?php echo HTMLHelper::_('schuweb_sitemap.changefrequency', 'jform[selections][' . $menu['menutype'] . '][changefreq]', $menu['changefreq'], $i); ?>
            </td>
        </tr>
    <?php $i++;
    endforeach; ?>
    </tbody>
</table>