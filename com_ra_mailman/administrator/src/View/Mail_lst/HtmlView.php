<?php

/**
 * @version    4.0.10
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 19/09/23 CB included Mailhelper as file (could not otherwise be found)
 * 01/01/24 CB use ContentHelper->getActions
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Mail_lst;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\Language\Text;
use \Ramblers\Component\Ra_mailman\Site\Helpers\MailHelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Edit class for a single Mail list.
 *
 * @since  1.0.6
 */
class HtmlView extends BaseHtmlView {

    protected $count;
    protected $state;
    protected $list_id;
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
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
// If deleting a record, list_id will be passed as a parameter
        $this->list_id = Factory::getApplication()->input->getInt('list_id', '0');

        if ($this->item->id == 0) {
            $this->count = 0;
        } else {
            $objHelper = new ToolsHelper;
            require_once JPATH_SITE . '/components/com_ra_mailman/src/Helpers/Mailhelper.php';
            $objMailHelper = new MailHelper;
            $this->count = $objMailHelper->countSubscribers($this->item->id);
        }
        $this->addToolbar();
        parent::display($tpl);
        $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.export&id=' . $this->item->id;
        if ($this->count > 0) {
            echo $objHelper->buildLink($target, 'Export');
        }
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
        /*
          ToolbarHelper::title(Text::_('Mailing list'), "generic");

          // If not checked out, can save the item.
          if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create')))) {
          ToolbarHelper::apply('mail_lst.apply', 'JTOOLBAR_APPLY');
          ToolbarHelper::save('mail_lst.save', 'JTOOLBAR_SAVE');
          }

          if (!$checkedOut && ($canDo->get('core.create'))) {
          ToolbarHelper::custom('mail_lst.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
          }

          // If an existing item, can save to a copy.
          if (!$isNew && $canDo->get('core.create')) {
          ToolbarHelper::custom('mail_lst.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
          }

          if ($this->item->group_primary == '') {
          ToolbarHelper::publish('mail_lst.prime', 'Make primary', false);
          } else {
          ToolbarHelper::unpublish('mail_lst.prime', 'Cancel primary', false);
          }

          if (empty($this->item->id)) {
          ToolbarHelper::cancel('mail_lst.cancel', 'JTOOLBAR_CANCEL');
          } else {
          ToolbarHelper::cancel('mail_lst.cancel', 'JTOOLBAR_CLOSE');
          }
         */
        ToolbarHelper::title(Text::_('Mailing list'), "generic");
        $toolbar = Toolbar::getInstance('toolbar');

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create')))) {
            $toolbar->apply('mail_lst.apply', 'JTOOLBAR_APPLY');
            $toolbar->save('mail_lst.save', 'JTOOLBAR_SAVE');
        }

        if (!$checkedOut && ($canDo->get('core.create'))) {
            ToolbarHelper::custom('mail_lst.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        }

        // If an existing item, can save to a copy.
        if (!$isNew && $canDo->get('core.create')) {
            ToolbarHelper::custom('mail_lst.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
        }

        if ($this->item->group_primary == '') {
            $toolbar->publish('mail_lst.prime', 'Make primary', false);
        } else {
            $toolbar->unpublish('mail_lst.prime', 'Cancel primary', false);
        }

        if (empty($this->item->id)) {
            $toolbar->cancel('mail_lst.cancel', 'JTOOLBAR_CANCEL');
        } else {
            $toolbar->cancel('mail_lst.cancel', 'JTOOLBAR_CLOSE');
        }

//        if ($this->count > 0) {
        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text('Export ' . $this->count . ' Subscribers')
                ->task('mail_lst.export')
                ->onclick('return false')
                ->listCheck(false);
//        }
    }

}
