<?php
/**
 * @version     sw.build.version
 * @copyright   Copyright (C) 2019 - 2023 Sven Schultschik. All rights reserved
 * @license     GPL-3.0-or-later
 * @author      Sven Schultschik (extensions@schultschik.de)
 * @link        extensions.schultschik.de
 */

namespace SchuWeb\Component\Sitemap\Administrator\Field\Modal;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\ParameterType;


defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;

/**
 * Supports a modal sitemap picker
 *
 * @since  3.3.2
 */
class SitemapField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 * @since   3.3.2
	 */
	protected $type = 'Modal_Sitemap';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.3.2
	 */
	protected function getInput()
	{
		$allowClear = ((string) $this->element['clear'] != 'false');
		$allowSelect = ((string) $this->element['select'] != 'false');

		// The active sitemap id field.
		$value = (int) $this->value ?: '';

		// Create the modal id.
		$modalId = 'Sitemap_' . $this->id;

		/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

		// Add the modal field script to the document head.
		$wa->useScript('field.modal-fields');

		static $scriptSelect = null;

		if (is_null($scriptSelect)) {
			$scriptSelect = [];
		}

		if (!isset($scriptSelect[$this->id])) {
			$wa->addInlineScript(
				"
				window.jSelectSitemap_" . $this->id . " = function (id, title, catid, object, url, language) {
					window.processModalSelect('Sitemap', '" . $this->id . "', id, title, catid, object, url, language);
				}",
				[],
				['type' => 'module']
			);

			Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

			$scriptSelect[$this->id] = true;
		}

		$linkSitemaps = 'index.php?option=com_schuweb_sitemap&view=sitemaps&layout=modal&tmpl=component&amp;' . Session::getFormToken() . '=1';

		$modalTitle = Text::_('com_schuweb_sitemap_SELECT_A_SITEMAP');

		$urlSelect = $linkSitemaps . '&amp;function=jSelectSitemap_' . $this->id;

		// Get the title of the linked chart
		if ($value) {
			$db = $this->getDatabase();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('title'))
				->from($db->quoteName('#__schuweb_sitemap'))
				->where($db->quoteName('id') . ' = :value')
				->bind(':value', $value, ParameterType::INTEGER);
			$db->setQuery($query);

			try {
				$title = $db->loadResult();
			} catch (\RuntimeException $e) {
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			}
			if (empty($title)) {
				$value = '';
			}
		}

		$title = empty($title) ? Text::_('com_schuweb_sitemap_SELECT_A_SITEMAP') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current sitemap display field.
		$html = '';

		if ($allowSelect || $allowClear) {
			$html .= '<span class="input-group">';
		}

		$html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

		// Select sitemap button
		if ($allowSelect) {
			$html .= '<button'
				. ' class="btn btn-primary' . ($value ? ' hidden' : '') . '"'
				. ' id="' . $this->id . '_select"'
				. ' data-bs-toggle="modal"'
				. ' type="button"'
				. ' data-bs-target="#ModalSelect' . $modalId . '">'
				. '<span class="icon-file" aria-hidden="true"></span> ' . Text::_('JSELECT')
				. '</button>';
		}

		// Clear sitemap button
		if ($allowClear) {
			$html .= '<button'
				. ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
				. ' id="' . $this->id . '_clear"'
				. ' type="button"'
				. ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
				. '<span class="icon-times" aria-hidden="true"></span> ' . Text::_('JCLEAR')
				. '</button>';
		}

		if ($allowSelect || $allowClear) {
			$html .= '</span>';
		}

		// Select sitemap modal
		if ($allowSelect) {
			$html .= HTMLHelper::_(
				'bootstrap.renderModal',
				'ModalSelect' . $modalId,
				[
					'title' => $modalTitle,
					'url' => $urlSelect,
					'height' => '400px',
					'width' => '800px',
					'bodyHeight' => 70,
					'modalWidth' => 80,
					'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
						. Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
				]
			);
		}

		// Note: class='required' for client side validation.
		$class = $this->required ? ' class="required modal-value"' : '';

		$html .= '<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int) $this->required . '" name="' . $this->name
			. '" data-text="' . htmlspecialchars(Text::_('COM_SCHUWEB_SITEMAP_SELECT_A_SITEMAP'), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '">';

		return $html;
	}
}