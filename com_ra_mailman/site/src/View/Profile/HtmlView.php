<?php

/**
 * @version    CVS: 4.1.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_mailman\Site\View\Profile;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * View class for Profile.
 *
 * @since  4.1.0
 */
class HtmlView extends BaseHtmlView {

    protected $state;
    protected $item;
    protected $form;
    protected $params;
    protected $title;
    protected $user;

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
        $this->user = $app->getIdentity();

        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->params = $app->getParams('com_ra_mailman');
        $this->canSave = $this->get('CanSave');
        $this->form = $this->get('Form');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
//        var_dump($this->params);
        // Mode is defined when creating the menu entry: 0=Self register, 1=Admin register
        $this->mode = $this->params->get('mode', '0');
        if ($this->mode == '0') {
            if ($this->user->id > 0) {
                //return Error::raiseWarning(404, "Please login to gain access to this function");
//            throw new \Exception('Please login to gain access to this function', 404);
                echo '<h4>You are already Registered</h4>';
                return false;
            }
        } else {  // Creating a new user
            if ($this->user->id == 0) {
                //return Error::raiseWarning(404, "Please login to gain access to this function");
//            throw new \Exception('Please login to gain access to this function', 404);
                echo '<h4>Please login to gain access to this function</h4>';
                return false;
            }
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
            $this->params->def('page_heading', Text::_('COM_RA_MAILMAN_DEFAULT_PAGE_TITLE'));
        }

        $this->title = $this->params->get('page_title', '');

        if (empty($this->title)) {
            $this->title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $this->title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $this->title = Text::sprintf('JPAGETITLE', $this->title, $app->get('sitename'));
        }

        $this->document->setTitle($this->title);

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

}
