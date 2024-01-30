<?php

/**
 * @version    4.1.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 01/01/23 CB set up canDo for
 * 01/01/24 CB use ContentHelper->getActions
 * 29/01/24 CB delete button
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Mail_lsts;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Mail_lists.
 *
 * @since  1.0.6
 */
class HtmlView extends BaseHtmlView {

    protected $canDo;
    protected $items;
    protected $pagination;
    protected $state;
    protected $user;
    protected $user_id;

    function defineActions($list_id, $list_type, $unsent_mailshot, $mailshot_id) {
        /*
         * invoked from the template to set up the required action buttons for the last column of the report
         * 01/01/24 CB this seems to be unused - code copied from site view?
         */

        if ($this->user_id == 0) {
            return '';
        }
        $objMailHelper = new Ra_mailmanMailhelper;
        $objHelper = new ToolsHelper;

        if ($objMailHelper->isAuthor($list_id)) {
            $target = 'index.php?option=com_ra_mailman&task=mailshot.edit';
            if ($unsent_mailshot == true) {
                $caption = 'Edit';
                $target .= '&id=' . $mailshot_id;
            } else {
                $caption = 'Create';
            }
// this will over-ride any options already set
            return $objHelper->buildLink($target . '&list_id=' . $list_id, $caption, False, "link-button button-p0159");
        }

// See if User is already subscribed
        $sql = 'SELECT id, state FROM #__ra_mail_subscriptions WHERE user_id=' . $this->user_id;
        $sql .= ' AND list_id=' . $list_id;
        $subscription = $objHelper->getItem($sql);
        if ($list_type == 'Open') {
            if (is_null($subscription)) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe';
                $caption = 'Subscribe';
                $colour = '0583';  // light green
            } else {
//                echo "View: state $subscription->state<br>";
                if ($subscription->state == 0) {
                    $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe';
                    $caption = 'Re-subscribe';
                    $colour = '0555';  // dark green
                }
            }
        }
// For unsubscribing, we don't care if the list is open or closed
        if (!is_null($subscription)) {
            if ($subscription->state > 0) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.unsubscribe&list_id=';
                $caption = 'Un-subscribe';
                $colour = '0186'; // red
                return $objHelper->buildLink($target . $list_id . '&user_id=' . $this->user_id, $caption, False, "link-button button-p" . $colour);
            }
        }
        if ($list_type == 'Open') {
            if (is_null($subscription)) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe';
                $caption = 'Subscribe';
                $colour = '0583';  // light green
            } else {
                if ($subscription->state == 0) {
                    $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe';
                    $caption = 'Re-subscribe';
                    $colour = '0555';  // dark green
                }
            }
            return $objHelper->buildLink($target . '&list_id=' . $list_id . '&user_id=' . $this->user_id, $caption, False, "link-button button-p" . $colour);
        } else {
            return '';
        }
    }

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
        $layout = Factory::getApplication()->input->getWord('layout', '');
        $this->user = Factory::getUser();
        $this->user_id = $this->user->id;
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

// Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();
        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.6
     */
    protected function addToolbar() {
// Suppress menu side panel
        Factory::getApplication()->input->set('hidemainmenu', true);
        $state = $this->get('State');
        $this->canDo = ContentHelper::getActions('com_ra_mailman');
        if ($layout == 'statistics') {
            if (!$this->canDo->get('core.delete')) {
                throw new \Exception('Access denied', 404);
            }
            ToolbarHelper::title(Text::_('Delete Mailing list'), "generic");
        } else {
            ToolbarHelper::title(Text::_('Mailing lists'), "generic");

            $toolbar = Toolbar::getInstance('toolbar');

// Check if the form exists before showing the add/edit buttons
            $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Mail_lsts';

            if (file_exists($formPath)) {
                if ($this->canDo->get('core.create')) {
                    $toolbar->addNew('mail_lst.add');
                }
            }

            if ($this->canDo->get('core.edit.state')) {
                $dropdown = $toolbar->dropdownButton('status-group')
                        ->text('JTOOLBAR_CHANGE_STATUS')
                        ->toggleSplit(false)
                        ->icon('fas fa-ellipsis-h')
                        ->buttonClass('btn btn-action')
                        ->listCheck(true);

                $childBar = $dropdown->getChildToolbar();

                //          if (isset($this->items[0]->state)) {
                //              $childBar->publish('mail_lst.publish')->listCheck(true);
                //              $childBar->unpublish('mail_lst.unpublish')->listCheck(true);
//                $childBar->edit('mail_lst.edit')->listCheck(true);
//            } elseif (isset($this->items[0])) {
// If this component does not use state then show a direct delete button as we can not trash
                $toolbar->delete('mail_lst.delete')
                        ->text('JTOOLBAR_DELETE')
                        ->message('JGLOBAL_CONFIRM_DELETE')
                        ->listCheck(true);
            }
//        }
            $toolbar->standardButton('nrecords')
                    ->icon('fa fa-info-circle')
                    ->text(number_format($this->pagination->total) . ' Records')
                    ->task('')
                    ->onclick('return false')
                    ->listCheck(false);
            ToolbarHelper::cancel('mail_lsts.cancel', 'JTOOLBAR_CANCEL');
        }
// Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_mailman&view=mail_lsts');
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields() {
        return array(
            'a.`description`' => Text::_('COM_RA_MAILMAN_MAIL_LSTS_DESCRIPTION'),
            'a.`group_code`' => Text::_('COM_RA_MAILMAN_MAIL_LSTS_GROUP_CODE'),
            'a.`name`' => Text::_('COM_RA_MAILMAN_MAIL_LSTS_NAME'),
            'a.`home_group_only`' => Text::_('COM_RA_MAILMAN_MAIL_LSTS_HOME_GROUP_ONLY'),
            'a.`chat_list`' => Text::_('COM_RA_MAILMAN_MAIL_LSTS_CHAT_LIST'),
        );
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return bool
     */
    public function getState($state) {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }

}
