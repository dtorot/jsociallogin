<?php
/**
 * @version    CVS: 0.0.1
 * @package    Com_Jsociallogin
 * @author     David Toro Triana <dtorot@opensai.org>
 * @copyright  2024 David Toro Triana
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jsl\Component\Jsociallogin\Administrator\Field;

defined('JPATH_BASE') or die;

use \Joomla\CMS\Form\FormField;
use \Joomla\CMS\Factory;

/**
 * Supports an HTML select list of categories
 *
 * @since  0.0.1
 */
class ModifiedbyField extends \Joomla\CMS\Form\FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  0.0.1
	 */
	protected $type = 'modifiedby';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since   0.0.1
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html   = array();
		$user = Factory::getApplication()->getIdentity();
		$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $user->id . '" />';
		if (!$this->hidden)
		{
			$html[] = "<div>" . $user->name . " (" . $user->username . ")</div>";
		}

		return implode($html);
	}
}
