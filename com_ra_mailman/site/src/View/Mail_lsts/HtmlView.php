<?php

/**
 * @version    4.1.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 05/07/23 CB changed action from task=mailshot.edit to view=mailshot
 * 14/11/23 CB store and pass menu_id
 * 30/01/24 CB include list owner in search
 */

namespace Ramblers\Component\Ra_mailman\Site\View\Mail_lsts;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Ra_mailman.
 *
 * @since  1.0.6
 */
class HtmlView extends BaseHtmlView {

    protected $items;
    protected $pagination;
    protected $state;
    protected $params;
    protected $objHelper;
    protected $menu_id;
    protected $user_id;

    function defineActions($list_id, $list_type, $unsent_mailshot, $mailshot_id) {
        /*
         * invoked from the template to set up the required action buttons for the last column of the report
         * $list_type will be Open / Closed
         */

        if ($this->user_id == 0) {
            return '';
        }
        $objMailHelper = new Mailhelper;
        if ($objMailHelper->isAuthor($list_id)) {
            //$target = 'index.php?option=com_ra_mailman&task=mailshot.edit';
            $target = 'index.php?option=com_ra_mailman&view=mailshot' . '&Itemid=' . $this->menu_id;
            ;
            if ($unsent_mailshot == true) {
                $caption = 'Edit';
                $target .= '&id=' . $mailshot_id;
            } else {
                $caption = 'Create';
            }
            // this will over-ride any options already set
            return $this->objHelper->buildLink($target . '&list_id=' . $list_id, $caption, False, "link-button button-p0159");
        }

        // See if User is already subscribed
        $sql = 'SELECT id, state FROM #__ra_mail_subscriptions WHERE user_id=' . $this->user_id;
        $sql .= ' AND list_id=' . $list_id;
        $subscription = $this->objHelper->getItem($sql);
        if ($list_type == 'Open') {
            if (is_null($subscription)) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                $caption = 'Subscribe';
                $colour = '0583';  // light green
            } else {
//                echo "View: state $subscription->state<br>";
                if ($subscription->state == 0) {
                    $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                    $caption = 'Re-subscribe';
                    $colour = '0555';  // dark green
                }
            }
        }
        // For unsubscribing, we don't care if the list is open or closed
        if (!is_null($subscription)) {
            if ($subscription->state > 0) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.unsubscribe&list_id=' . '&menu_id=' . $this->menu_id;
                $caption = 'Un-subscribe';
                $colour = 'rosycheeks';
                return $this->objHelper->buildButton($target . $list_id . '&user_id=' . $this->user_id, $caption, False, $colour);
            }
        }
        if ($list_type == 'Open') {
            if (is_null($subscription)) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                $caption = 'Subscribe';
                $colour = 'sunset';
            } else {
                if ($subscription->state == 0) {
                    $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                    $caption = 'Re-subscribe';
                    $colour = 'sunrise';
                }
            }
            return $this->objHelper->buildButton($target . '&list_id=' . $list_id . '&user_id=' . $this->user_id, $caption, False, $colour);
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
        $app = Factory::getApplication();
        $this->user_id = Factory::getUser()->id;
        $this->menu_id = $app->input->getInt('Itemid', '0');
        $this->objHelper = new ToolsHelper;

        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->params = $app->getParams('com_ra_mailman');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->_prepareDocument();
        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return void
     *
     * @throws Exception
     */
    protected function _prepareDocument() {
        $app = Factory::getApplication();
        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('Mailing lists'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
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
