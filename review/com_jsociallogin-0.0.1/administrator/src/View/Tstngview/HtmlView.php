<?php
/**
 * @version    CVS: 0.0.1
 * @package    Com_Jsociallogin
 * @author     David Toro Triana <dtorot@opensai.org>
 * @copyright  2024 David Toro Triana
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jsl\Component\Jsociallogin\Administrator\View\Tstngview;
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Jsl\Component\Jsociallogin\Administrator\Helper\JsocialloginHelper;
use \Joomla\CMS\Language\Text;

/**
 * View class for a single Tstngview.
 *
 * @since  0.0.1
 */
class HtmlView extends BaseHtmlView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		
		
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getApplication()->getIdentity();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JsocialloginHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JSOCIALLOGIN_TITLE_TSTNGVIEW'), "jcc fa-user");

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('tstngview.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('tstngview.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			ToolbarHelper::custom('tstngview.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::custom('tstngview.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('tstngview.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('tstngview.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
