<?php
/**
 * @version          sw.build.version
 * @copyright        Copyright (C) 2021 Sven Schultschik. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 * @author           Sven Schultschik (sven@schultschik.de)
 */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_schuweb_sitemap&layout=edit&id=' . $this->item->id); ?>"
      method="post" name="adminForm" id="sitemap-form"
      aria-label="<?php echo Text::_('SCHUWEB_SITEMAP_PAGE_' . ((int) $this->item->id === 0 ? 'ADD_SITEMAP' : 'EDIT_SITEMAP'), true); ?>" class="form-validate">

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('SCHUWEB_SITEMAP_SITEMAP_DETAILS_FIELDSET')); ?>
        <div class="row">
            <div class="col-lg-9">
                <div class="form-vertical">
					<?php echo $this->form->getInput('introtext'); ?>
                </div>
            </div>
            <div class="col-lg-3">
				<?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'attrib-menus', Text::_('SCHUWEB_SITEMAP_FIELDSET_MENUS')); ?>
		<?php echo $this->loadTemplate('menus_j4'); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php
		$fieldSets = $this->form->getFieldsets('attribs');
		foreach ($fieldSets as $name => $fieldSet) :
			?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'attrib-' . $name, Text::_($fieldSet->label)); ?>
            <div class="row">
                <div class="col-md-12">
                    <fieldset id="fieldset-<?php echo $name; ?>" class="options-form">
						<?php
						if (isset($fieldSet->description) && trim($fieldSet->description)) :
							echo '<legend>' . $this->escape(JText::_($fieldSet->description)) . '</legend>';
						endif; ?>
                        <div>
							<?php foreach ($this->form->getFieldset($name) as $field) : ?>
								<?php echo $field->renderField(); ?>
							<?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>
            </div>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endforeach; ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value=""/>
	<?php echo $this->form->getInput('is_default'); ?>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
