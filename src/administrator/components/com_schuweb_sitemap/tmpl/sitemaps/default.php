<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_schuweb_sitemap
 * 
 * @version     sw.build.version
 * @copyright   Copyright (C) 2023 Sven Schultschik. All rights reserved
 * @license     GNU General Public License version 3; see LICENSE
 * @author      Sven Schultschik (extensions@schultschik.de)
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use SchuWeb\Libraries\Button\DefaultButton;

$baseUrl = JUri::root();

$user = Factory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';
$saveOrderCheck = $saveOrder && !empty($this->items);

?>
<form action="<?php echo Route::_('index.php?option=com_schuweb_sitemap&view=sitemaps'); ?>" method="post"
    name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php
                echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
                ?>
                <?php if (empty($this->items)): ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden">
                            <?php echo Text::_('INFO'); ?>
                        </span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else: ?>
                    <table class="table itemList" id="sitemapList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_SCHUWEB_SITEMAP_TABLE_CAPTION'); ?>,
                            <span id="orderedBy">
                                <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
                            </span>,
                            <span id="filteredBy">
                                <?php echo Text::_('JGLOBAL_FILTERED_BY'); ?>
                            </span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'SCHUWEB_SITEMAP_HEADING_DEFAULT', 'a.featured', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="title">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'SCHUWEB_SITEMAP_Heading_Sitemap', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody <?php if ($saveOrderCheck): ?> data-url="<?php echo $saveOrderingUrl; ?>"
                                data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true" <?php endif; ?>>
                            <?php foreach ($this->items as $i => $item):
                                $ordering = ($listOrder == 'a.ordering');
                                $canCreate = $user->authorise('core.create', 'com_schuweb_sitemap.sitemap.' . $item->id);
                                $canEdit = $user->authorise('core.edit', 'com_schuweb_sitemap.sitemap.' . $item->id);
                                $canChange = $user->authorise('core.edit.state', 'com_schuweb_sitemap.sitemap.' . $item->id);
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'sitemaps.', $canChange, 'cb'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $options = [
                                            'task_prefix' => 'sitemaps.',
                                            'disabled' => false,
                                            'id' => 'default-' . $item->id
                                        ];

                                        echo (new DefaultButton())
                                            ->render((int) $item->is_default, $i, $options);
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($canEdit): ?>
                                            <a
                                                href="<?php echo Route::_('index.php?option=com_schuweb_sitemap&task=sitemap.edit&id=' . $item->id); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif; ?>
                                        <?php if ($item->state):
                                            if (isset($this->xml_links[$item->id]['sitemap'])): ?>
                                                <small>[<a href="<?php echo $this->xml_links[$item->id]['sitemap']; ?>" target="_blank"
                                                        title="<?php echo Text::_('SCHUWEB_SITEMAP_XML_LINK_TOOLTIP', true); ?>">
                                                        <?php echo Text::_('SCHUWEB_SITEMAP_XML_LINK'); ?>
                                                    </a>]</small>
                                            <?php else: ?>
                                                <small>[
                                                    <?php echo Text::_('SCHUWEB_SITEMAP_NO_SITEMAP_XML', true); ?>]
                                                </small>
                                            <?php endif;
                                            if (isset($this->xml_links[$item->id]['news'])): ?>
                                                <small>[<a href="<?php echo $this->xml_links[$item->id]['news']; ?>" target="_blank"
                                                        title="<?php echo Text::_('SCHUWEB_SITEMAP_NEWS_LINK_TOOLTIP', true); ?>">
                                                        <?php echo Text::_('SCHUWEB_SITEMAP_NEWS_LINK'); ?>
                                                    </a>]</small>
                                            <?php else: ?>
                                                <small>[
                                                    <?php echo Text::_('SCHUWEB_SITEMAP_NO_NEWS_XML', true); ?>]
                                                </small>
                                            <?php endif;
                                            if (isset($this->xml_links[$item->id]['images'])): ?>
                                                <small>[<a href="<?php echo $this->xml_links[$item->id]['images']; ?>" target="_blank"
                                                        title="<?php echo Text::_('SCHUWEB_SITEMAP_IMAGES_LINK_TOOLTIP', true); ?>">
                                                        <?php echo Text::_('SCHUWEB_SITEMAP_IMAGES_LINK'); ?>
                                                    </a>]</small>
                                            <?php else: ?>
                                                <small>[
                                                    <?php echo Text::_('SCHUWEB_SITEMAP_NO_IMAGES_XML', true); ?>]
                                                </small>
                                            <?php endif;
                                        endif; ?>
                                        <br />
                                        <div class="small">
                                            <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                        </div>
                                    </td>

                                    <td class="small d-none d-md-table-cell">
                                        <?php echo $this->escape($item->access_level); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
                <input type="hidden" name="task" value="" />
                <input type="hidden" name="boxchecked" value="0" />
                <?php echo JHtml::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>