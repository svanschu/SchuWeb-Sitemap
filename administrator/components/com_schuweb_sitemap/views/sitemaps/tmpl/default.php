<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');


$n = count($this->items);

$baseUrl = JUri::root();

$user = JFactory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder && !empty($this->items))
{
    //TODO!!!!!!!!!!!!!!!!!!!!
	$saveOrderingUrl = 'index.php?option=com_schuweb_sitemap&task=sitemaps.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_schuweb_sitemap&view=sitemaps'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span
                                class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
				<?php else : ?>
                    <table class="table" id="sitemapList">
                        <caption class="visually-hidden">
							<?php echo Text::_('COM_SCHUWEB_SITEMAP_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <th class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
                            </th>
                            <th scope="col" class="w-5 text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="title">
								<?php echo HTMLHelper::_('searchtools.sort', 'SCHUWEB_SITEMAP_Heading_Sitemap', 'a.title', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo JText::_('SCHUWEB_SITEMAP_Heading_Html_Stats'); ?><br/>
                                (<?php echo JText::_('SCHUWEB_SITEMAP_Heading_Num_Links') . ' / ' . JText::_('SCHUWEB_SITEMAP_Heading_Num_Hits') . ' / ' . JText::_('SCHUWEB_SITEMAP_Heading_Last_Visit'); ?>
                                )
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo JText::_('SCHUWEB_SITEMAP_Heading_Xml_Stats'); ?><br/>
								<?php echo JText::_('SCHUWEB_SITEMAP_Heading_Num_Links') . '/' . JText::_('SCHUWEB_SITEMAP_Heading_Num_Hits') . '/' . JText::_('SCHUWEB_SITEMAP_Heading_Last_Visit'); ?>
                            </th>
                            <th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody <?php if ($saveOrder) :?> data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
						<?php foreach ($this->items as $i => $item) :
							$ordering   = ($listOrder == 'a.ordering');
							$canCreate  = $user->authorise('core.create',     'com_schuweb_sitemap.sitemap.' . $item->id);
							$canEdit    = $user->authorise('core.edit',       'com_schuweb_sitemap.sitemap.' . $item->id);
							$canChange  = $user->authorise('core.edit.state', 'com_schuweb_sitemap.sitemap.' . $item->id);

							$now = JFactory::getDate()->toUnix();
							if (!$item->lastvisit_html)
							{
								$htmlDate = JText::_('Date_Never');
							}
                            elseif ($item->lastvisit_html > ($now - 3600))
							{ // Less than one hour
								$htmlDate = JText::sprintf('Date_Minutes_Ago', intval(($now - $item->lastvisit_html) / 60));
							}
                            elseif ($item->lastvisit_html > ($now - 86400))
							{ // Less than one day
								$hours    = intval(($now - $item->lastvisit_html) / 3600);
								$htmlDate = JText::sprintf('Date_Hours_Minutes_Ago', $hours, ($now - ($hours * 3600) - $item->lastvisit_html) / 60);
							}
                            elseif ($item->lastvisit_html > ($now - 259200))
							{ // Less than three days
								$days     = intval(($now - $item->lastvisit_html) / 86400);
								$htmlDate = JText::sprintf('Date_Days_Hours_Ago', $days, intval(($now - ($days * 86400) - $item->lastvisit_html) / 3600));
							}
							else
							{
								$date     = new JDate($item->lastvisit_html);
								$htmlDate = $date->format('Y-m-d H:i');
							}

							if (!$item->lastvisit_xml)
							{
								$xmlDate = JText::_('Date_Never');
							}
                            elseif ($item->lastvisit_xml > ($now - 3600))
							{ // Less than one hour
								$xmlDate = JText::sprintf('Date_Minutes_Ago', intval(($now - $item->lastvisit_xml) / 60));
							}
                            elseif ($item->lastvisit_xml > ($now - 86400))
							{ // Less than one day
								$hours   = intval(($now - $item->lastvisit_xml) / 3600);
								$xmlDate = JText::sprintf('Date_Hours_Minutes_Ago', $hours, ($now - ($hours * 3600) - $item->lastvisit_xml) / 60);
							}
                            elseif ($item->lastvisit_xml > ($now - 259200))
							{ // Less than three days
								$days    = intval(($now - $item->lastvisit_xml) / 86400);
								$xmlDate = JText::sprintf('Date_Days_Hours_Ago', $days, intval(($now - ($days * 86400) - $item->lastvisit_xml) / 3600));
							}
							else
							{
								$date    = new JDate($item->lastvisit_xml);
								$xmlDate = $date->format('Y-m-d H:i');
							}

							?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                </td>
                                <td class="text-center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'sitemaps.', $canChange, 'cb'); ?>
                                </td>
                                <td>
                                    <?php if ($canEdit) : ?>
                                    <a href="<?php echo JRoute::_('index.php?option=com_schuweb_sitemap&task=sitemap.edit&id=' . $item->id); ?>">
										<?php echo $this->escape($item->title); ?></a>
                                    <?php else : ?>
	                                    <?php echo $this->escape($item->title); ?>
                                    <?php endif; ?>
									<?php if ($item->is_default == 1) : ?>
                                        <span class="icon-star" aria-hidden="true"></span>
									<?php endif; ?>
									<?php if ($item->state): ?>
                                        <small>[<a href="<?php echo $baseUrl . 'index.php?option=com_schuweb_sitemap&amp;view=xml&tmpl=component&id=' . $item->id; ?>"
                                                   target="_blank"
                                                   title="<?php echo JText::_('SCHUWEB_SITEMAP_XML_LINK_TOOLTIP', true); ?>"><?php echo JText::_('SCHUWEB_SITEMAP_XML_LINK'); ?></a>]</small>
                                        <small>[<a href="<?php echo $baseUrl . 'index.php?option=com_schuweb_sitemap&amp;view=xml&tmpl=component&news=1&id=' . $item->id; ?>"
                                                   target="_blank"
                                                   title="<?php echo JText::_('SCHUWEB_SITEMAP_NEWS_LINK_TOOLTIP', true); ?>"><?php echo JText::_('SCHUWEB_SITEMAP_NEWS_LINK'); ?></a>]</small>
                                        <small>[<a href="<?php echo $baseUrl . 'index.php?option=com_schuweb_sitemap&amp;view=xml&tmpl=component&images=1&id=' . $item->id; ?>"
                                                   target="_blank"
                                                   title="<?php echo JText::_('SCHUWEB_SITEMAP_IMAGES_LINK_TOOLTIP', true); ?>"><?php echo JText::_('SCHUWEB_SITEMAP_IMAGES_LINK'); ?></a>]</small>
									<?php endif; ?>
                                    <br/>
                                    <div class="small">
		                                <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                    </div>
                                </td>

                                <td class="small d-none d-md-table-cell">
									<?php echo $this->escape($item->access_level); ?>
                                </td>
                                <td class="d-none d-md-table-cell">
									<?php echo $item->count_html . ' / ' . $item->views_html . ' / ' . $htmlDate; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
									<?php echo $item->count_xml . ' / ' . $item->views_xml . ' / ' . $xmlDate; ?>
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

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
				<?php echo JHtml::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
