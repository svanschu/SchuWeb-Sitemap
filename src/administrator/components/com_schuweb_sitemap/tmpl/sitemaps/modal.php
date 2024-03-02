<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2024 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$app = Factory::getApplication();

if ($app->isClient('site')) {
    Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
}

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('core')
    ->useScript('multiselect')
    ->useScript('com_schuweb_sitemap.admin-sitemaps-modal');

$function = $app->getInput()->getCmd('function', 'jSelectSitemap');
$editor = $app->getInput()->getCmd('editor', '');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$onclick = $this->escape($function);

if (!empty($editor)) {
    // This view is used also in com_menus. Load the xtd script only if the editor is set!
    $this->document->addScriptOptions('xtd-articles', ['editor' => $editor]);
    $onclick = "jSelectSitemap";
}

?>
<div class="container-popup">

    <form
        action="<?php echo Route::_('index.php?option=com_schuweb_sitemap&view=sitemaps&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1&editor=' . $editor); ?>"
        method="post" name="adminForm" id="adminForm">

        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)): ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden">
                    <?php echo Text::_('INFO'); ?>
                </span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else: ?>
            <table class="table table-sm">
                <caption class="visually-hidden">
                    <?php echo Text::_('SCHUWEB_SITEMAP_TABLE_CAPTION'); ?>,
                    <span id="orderedBy">
                        <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
                    </span>,
                    <span id="filteredBy">
                        <?php echo Text::_('JGLOBAL_FILTERED_BY'); ?>
                    </span>
                </caption>
                <thead>
                    <tr>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="title">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-1 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $iconStates = [
                        -2 => 'icon-trash',
                        0 => 'icon-times',
                        1 => 'icon-check',
                    ];
                    ?>
                    <?php
                    foreach ($this->items as $i => $item): ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <span class="tbody-icon">
                                    <span class="<?php echo $iconStates[$this->escape($item->state)]; ?>"
                                        aria-hidden="true"></span>
                                </span>
                            </td>
                            <th scope="row">
                                <?php $attribs = 'data-function="' . $this->escape($onclick) . '"'
                                    . ' data-id="' . $item->id . '"'
                                    . ' data-title="' . $this->escape($item->title) . '"'
                                    . ' data-uri="' . $this->escape('index.php?option=com_schuweb_sitemap&view=sitemap&id=' . $item->id) . '"';
                                ?>
                                <a class="select-link" href="javascript:void(0)" <?php echo $attribs; ?>>
                                    <?php echo $this->escape($item->title); ?>
                                </a>
                            </th>
                            <td class="small d-none d-md-table-cell">
                                <?php echo $this->escape($item->access_level); ?>
                            </td>
                            <td class="small d-none d-md-table-cell">
                                <?php echo (int) $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php // load the pagination. ?>
            <?php echo $this->pagination->getListFooter(); ?>

        <?php endif; ?>

        <input type="hidden" name="tmpl" value="component" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="forcedLanguage"
            value="<?php echo $app->getInput()->get('forcedLanguage', '', 'CMD'); ?>">
        <?php echo HTMLHelper::_('form.token'); ?>

    </form>
</div>