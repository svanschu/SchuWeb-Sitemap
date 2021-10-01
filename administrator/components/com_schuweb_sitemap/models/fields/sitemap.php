<?php
/**
 * @version       sw.build.version
 * @copyright     Copyright (C) 2021 Sven Schultschik. All rights reserved.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * @author        Sven Schultschik (sven@schultschik.de)
 */
// no direct access
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * Sitemap field.
 *
 * @since  3.3.2
 */
class JFormFieldSitemap extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 * @since   3.3.2
	 */
	protected $type = 'sitemap';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.3.2
	 */
	protected function getInput()
	{
		// Initialise variables.
		$db = JFactory::getDBO();

		// Get the title of the linked chart
		if ($this->value)
		{
			$db->setQuery(
				'SELECT title' .
				' FROM #__schuweb_sitemap' .
				' WHERE id = ' . (int) $this->value
			);
			$title = $db->loadResult();
		}
		else
		{
			$title = '';
		}

		if (empty($title))
		{
			$title = JText::_('com_schuweb_sitemap_SELECT_A_SITEMAP');
		}

		JFactory::getDocument()->addScriptDeclaration(
			"function jSelectSitemap_" . $this->id . "(id, title, object) {
                       document.getElementById('" . $this->id . "').value = id;
                       document.getElementById('" . $this->id . "_name').value = title;
                       jQuery('#sitemapTypeModal').modal('hide')
                  }"
		);

		$link = JRoute::_('index.php?option=com_schuweb_sitemap&view=sitemaps&layout=modal&tmpl=component&function=jSelectSitemap_' . $this->id);

		if (version_compare(JVERSION, '4', 'lt'))
		{
			$class     = 'class="input-medium"';
			$classSpan = 'input-append';
			$bsModal   = 'data-target="#sitemapTypeModal" data-toggle="modal"';
		}
		else
		{
			$class     = 'class="form-control valid form-control-success"';
			$classSpan = 'input-group';
			$bsModal   = 'data-bs-target="#sitemapTypeModal" data-bs-toggle="modal"';
		}

		$html   = array();
		$html[] = '<span class="' . $classSpan . '">';
		$html[] = '<input type="text" required="required" readonly="readonly" size="40" id="' . $this->id . '_name" ' . $class . ' value="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" />';
		$html[] = '<button type="button" class="btn btn-primary" ' . $bsModal . ' title="' . JText::_('COM_SCHUWEB_SITEMAP_CHANGE_SITEMAP') . '">'
			. '<span class="icon-list icon-white" aria-hidden="true"></span> '
			. JText::_('JSELECT') . '</button>';
		$html[] = '</span>';
		$html[] = JHtml::_(
			'bootstrap.renderModal',
			'sitemapTypeModal',
			array(
				'url'        => $link,
				'title'      => JText::_('COM_SCHUWEB_SITEMAP_CHANGE_SITEMAP'),
				'width'      => '800px',
				'height'     => '300px',
				'modalWidth' => '80',
				'bodyHeight' => '70',
				'footer'     => '<button type="button" class="btn" data-dismiss="modal">'
					. JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
			)
		);
		$html[] = '<input type="hidden" id="' . $this->id . '" name="' . $this->name . '" value="' . (int) $this->value . '" />';

		return implode("\n", $html);;
	}
}