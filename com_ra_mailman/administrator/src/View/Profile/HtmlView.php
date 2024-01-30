<?php

/**
 * @version    4.0.10
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 01/01/24 CB use ContentHelper->getActions
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Profile;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a single Profile.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView {

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
    public function display($tpl = null) {
        $app = Factory::getApplication();
        $id = $app->input->getInt('id', '0');
        //Factory::getApplication()->enqueueMessage('id=' . $id, 'info');
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = ($this->item->id == 0);

        if (isset($this->item->checked_out)) {
            $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }

        $canDo = ContentHelper::getActions('com_ra_mailman');

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create')))) {
//            ToolbarHelper::apply('profile.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('profile.save', 'JTOOLBAR_SAVE');
        }

        if (empty($this->item->id)) {
            ToolbarHelper::title(Text::_('New user'), "generic");
            ToolbarHelper::cancel('profile.cancel', 'JTOOLBAR_CANCEL');
        } else {
            ToolbarHelper::title(Text::_('Edit Profile'), "generic");
            ToolbarHelper::cancel('profile.cancel', 'JTOOLBAR_CLOSE');
        }
    }

}
