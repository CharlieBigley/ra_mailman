<?php

/**
 * @version    4.0.10
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Dataload;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\Language\Text;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a single Mail_lst.
 *
 * @since  1.0.6
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

        $this->state = $this->get('State');
//        die('HtmlView');
        $this->item = $this->get('Item');
//        var_dump($this->item);
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
        // Suppress menu side panel
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = ($this->item->id == 0);

        $canDo = ContentHelper::getActions('com_ra_mailman');

        ToolbarHelper::title(Text::_('Load data'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

        // If not checked out, can save the item.
        if ($canDo->get('core.edit') || ($canDo->get('core.create'))) {
//            $toolbar->apply('mail_lst.apply', 'JTOOLBAR_APPLY');  // save
//            ToolbarHelper::apply('mail_lst.apply', 'JTOOLBAR_APPLY');  // save
//            ToolbarHelper::save('mail_lst.save', 'JTOOLBAR_SAVE');   // Save and close

            $toolbar->standardButton('process')
                    ->icon('fa fa-info-circle')
                    ->text('Process file')
                    ->task('dataload.process')
                    ->onclick('return true')
                    ->listCheck(false);
        }

        ToolbarHelper::cancel('dataload.cancel', 'JTOOLBAR_CANCEL');
    }

}
