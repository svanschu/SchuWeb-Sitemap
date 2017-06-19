<?php
/**
 * @version          $Id$
 * @copyright        Copyright (C) 2007 - 2009 Joomla! Vargas. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 * @author           Guillermo Vargas (guille@vargas.co.cr)
 */
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
if (version_compare(JVERSION, '3.0.0', 'ge')) {
    JHtml::_('formbehavior.chosen', 'select');
}
?>
<script type="text/javascript">
    Joomla.submitbutton = function (task) {
        if (task == 'sitemap.cancel' || document.formvalidator.isValid(document.id('sitemap-form'))) {
            <?php echo $this->form->getField('introtext')->save(); ?>
            Joomla.submitform(task, document.getElementById('sitemap-form'));
        }
    }
</script>
<form action="<?php echo JRoute::_('index.php?option=com_schuweb_sitemap&layout=edit&id=' . $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
    <!-- Begin Content -->
    <div class="form-horizontal">

        <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('SCHUWEB_SITEMAP_SITEMAP_DETAILS_FIELDSET')); ?>

        <div class="row-fluid">
            <div class="span9">
                <fieldset class="adminform">
                    <?php echo $this->form->getInput('introtext'); ?>
                </fieldset>
            </div>
            <div class="span3">
                <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>

        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'attrib-menus', JText::_('SCHUWEB_SITEMAP_FIELDSET_MENUS')); ?>
        <?php echo $this->loadTemplate('menues'); ?>
        <?php echo JHtml::_('bootstrap.endTab'); ?>

        <?php
        $fieldSets = $this->form->getFieldsets('attribs');
        foreach ($fieldSets as $name => $fieldSet) :
            ?>
            <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'attrib-' . $name, JText::_($fieldSet->label)); ?>
            <?php
            if (isset($fieldSet->description) && trim($fieldSet->description)) :
                echo '<p class="tip">' . $this->escape(JText::_($fieldSet->description)) . '</p>';
            endif;

            foreach ($this->form->getFieldset($name) as $field) :
                ?>
                <div class="control-group">
                    <?php echo $field->label; ?>
                    <div class="controls">
                        <?php echo $field->input; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php echo JHtml::_('bootstrap.endTab'); ?>
        <?php endforeach;?>
    </div>
    <?php echo JHtml::_('bootstrap.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo $this->form->getInput('is_default'); ?>
    <?php echo JHtml::_('form.token'); ?>
</form>
<div class="clr"></div>
