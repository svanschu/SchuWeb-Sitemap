<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

$prio_options = array();
for ($i=0.1; $i<=1;$i+=0.1) {
    $prio_options[] = HTMLHelper::_('select.option',$i,$i);;
}

$changefreq_options[] = HTMLHelper::_('select.option','hourly','hourly');
$changefreq_options[] = HTMLHelper::_('select.option','daily','daily');
$changefreq_options[] = HTMLHelper::_('select.option','weekly','weekly');
$changefreq_options[] = HTMLHelper::_('select.option','monthly','monthly');
$changefreq_options[] = HTMLHelper::_('select.option','yearly','yearly');
$changefreq_options[] = HTMLHelper::_('select.option','never','never');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
Factory::getApplication()->getDocument()->getWebAssetManager()
    ->useScript('com_schuweb_sitemap.admin-sitemap-menusort');

?>
<table class="table" id="menuList">
    <thead>
    <tr>
        <th class="w-1 text-center">
            <?php echo HTMLHelper::_('grid.checkall'); ?>
        </th>
        <th scope="col" class="w-1 text-center">
            <?php echo Text::_('SCHUWEB_SITEMAP_ORDERING'); ?>
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
            <td class="text-center">
                <span class="icon-ellipsis-v" aria-hidden="true"></span>
                <input class="sortID" type="hidden" 
                    name="jform[selections][<?php echo $menu['menutype']; ?>][order]" 
                    value="<?php echo $menu['order'] ?>" />
            </td>
            <td>
                <label for="cb<?php echo $i; ?>"><?php echo $this->escape($menu['title']); ?></label>
            </td>
            <td>
                <?php 
                $name = 'jform[selections][' . $menu['menutype'] . '][priority]';
                $value = $menu['priority'];
                echo HTMLHelper::_('select.genericlist', $prio_options, $name, null, 'value', 'text', $value, $name.$i);?>
            </td>
            <td>
                <?php
                $name = 'jform[selections][' . $menu['menutype'] . '][changefreq]';
                $value = $menu['changefreq'];
                echo HTMLHelper::_('select.genericlist', $changefreq_options, $name, null, 'value', 'text', $value, $name.$i);?>
            </td>
        </tr>
    <?php $i++;
    endforeach; ?>
    </tbody>
</table>