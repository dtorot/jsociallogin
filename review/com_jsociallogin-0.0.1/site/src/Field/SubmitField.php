<?php

/**
 * @version    CVS: 0.0.1
 * @package    Com_Jsociallogin
 * @author     David Toro Triana <dtorot@opensai.org>
 * @copyright  2024 David Toro Triana
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jsl\Component\Jsociallogin\Site\Field;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\FormField;

/**
 * Class SubmitField
 *
 * @since  0.0.1
 */
class SubmitField extends FormField
{
	protected $type = 'submit';

	protected $value;

	protected $for;

	/**
	 * Get a form field markup for the input
	 *
	 * @return string
	 */
	public function getInput()
	{
		$this->value = $this->getAttribute('value');

		return '<button id="' . $this->id . '"'
		. ' name="submit_' . $this->for . '"'
		. ' value="' . $this->value . '"'
		. ' title="' . Text::_('JSEARCH_FILTER_SUBMIT') . '"'
		. ' class="btn" style="margin-top: -10px;">'
		. Text::_('JSEARCH_FILTER_SUBMIT')
		. ' </button>';
	}
}
