<?php
/**
 * @author
 * @link
 * @copyright
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('sortablelist.sortable', 'menueList', 'adminForm', 'asc', false);
?>
<table class="adminlist table table-striped">
    <thead>
    <tr>
        <th width="1%">
        </th>
        <th width="1%">
            <?php echo JHtml::_('grid.checkall'); ?>
        </th>
        <th class="title">
            <?php echo JText::_('JGLOBAL_TITLE'); ?>
        </th>
        <th>
            <?php echo JText::_('SCHUWEB_SITEMAP_PRIORITY'); ?>
        </th>
        <th>
            <?php echo JText::_('SCHUWEB_SITEMAP_CHANGE_FREQUENCY'); ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->item->selections as $i => $menu): ?>
        <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
            <td class="center hidden-phone">
                <span class="sortable-handler">
                    <i class="icon-menu"></i>
                </span>
            </td>
            <td width="1%" class="center">
                <input type="checkbox" id="cb<?php echo $i; ?>"
                       name="jform[selections][<?php echo $menu['menutype']; ?>][enabled]"
                       value="1" <?php if (isset($menu['enabled'])) {echo $menu['enabled'] ? 'checked="checked"' : '';} ?> />
            </td>
            <td class="nowrap has-context">
                <label for="cb<?php echo $i; ?>"><?php echo $this->escape($menu['title']); ?></label>
            </td>
            <td class="nowrap hidden-phone">
                <?php echo JHtml::_('schuweb_sitemap.priorities', 'jform[selections][' . $menu['menutype'] . '][priority]', $menu['priority'], $i); ?>
            </td>
            <td class="nowrap hidden-phone">
                <?php echo JHTML::_('schuweb_sitemap.changefrequency', 'jform[selections][' . $menu['menutype'] . '][changefreq]', $menu['changefreq'], $i); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>