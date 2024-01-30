<?php

/**
 * @version    4.0.10
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 19/09/23 CB use MailHelper from Site, not Administrator
 * 01/01/24 CB use ContentHelper->getActions
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Subscriptions;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use \Ramblers\Component\Ra_mailman\Site\Helpers\MailHelper;

/**
 * View class for a list of Subscriptions.
 *
 * @since  1.0.4
 */
class HtmlView extends BaseHtmlView {

    protected $items;
    protected $pagination;
    protected $state;

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
     * @since   1.0.4
     */
    protected function addToolbar() {
        // Suppress menu side panel
        Factory::getApplication()->input->set('hidemainmenu', true);
        $state = $this->get('State');
        $canDo = ContentHelper::getActions('com_ra_mailman');

        ToolbarHelper::title(Text::_('Subscriptions'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');
        /*
          if ($canDo->get('core.edit.state')) {
          $dropdown = $toolbar->dropdownButton('status-group')
          ->text('JTOOLBAR_CHANGE_STATUS')
          ->toggleSplit(false)
          ->icon('fas fa-ellipsis-h')
          ->buttonClass('btn btn-action')
          ->listCheck(true);

          $childBar = $dropdown->getChildToolbar();

          if (isset($this->items[0]->state)) {
          $childBar->publish('subscriptions.publish')->listCheck(true);
          $childBar->unpublish('subscriptions.unpublish')->listCheck(true);
          }
          }
         *
         */
        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text(number_format($this->pagination->total) . ' Records')
                ->task('')
                ->onclick('return false')
                ->listCheck(false);
        ToolbarHelper::cancel('subscriptions.cancel', 'JTOOLBAR_CANCEL');

        // Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_mailman&view=subscriptions');
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
