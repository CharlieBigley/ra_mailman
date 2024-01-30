<?php

/**
 * @version    4.0.11
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 13/11/23 CB take name of website from uri
 * 14/11/23 CB correct redirection back to edit screen when saving but errors
 * 14/11/23 CB change welcome message if self registering
 */

namespace Ramblers\Component\Ra_mailman\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
//use Joomla\CMS\Language\Multilanguage;
//use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Profile class.
 *
 * @since  4.1.0
 */
class ProfileController extends FormController {

    /**
     * Method to abort creation of profile
     */
    public function cancel($key = NULL) {
        $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mail_lsts', false));
    }

    /**
     * Method to check out an item for editing and redirect to the edit form.
     *
     * @return  void
     *
     * @since   4.1.0
     *
     * @throws  Exception
     */
    public function edit($key = NULL, $urlVar = NULL) {
        // Get the previous edit id (if any) and the current edit id.
        $previousId = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');
        $editId = $this->input->getInt('id', 0);

        // Set the user id for the user to edit in the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.id', $editId);

        // Get the model.
        $model = $this->getModel('Profile', 'Site');

        // Check out the item
        if ($editId) {
            $model->checkout($editId);
        }

        // Check in the previous user.
        if ($previousId) {
            $model->checkin($previousId);
        }

        // Redirect to the edit screen.
        $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=profile&layout=edit', false));
    }

    /**
     * Method to save data.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   4.1.0
     */
    public function save($key = NULL, $urlVar = NULL) {
        // Check for request forgeries.
        $this->checkToken();

        // Initialise variables.
        $model = $this->getModel('Profile', 'Site');

        // Get the user data.
        $data = $this->input->get('jform', array(), 'array');

        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new \Exception($model->getError(), 500);
        }

        // Send an object which can be modified through the plugin event
        $objData = (object) $data;
        $this->app->triggerEvent(
                'onContentNormaliseRequestData',
                array($this->option . '.' . $this->context, $objData, $form)
        );
        $data = (array) $objData;

        // Validate the posted data.
        $data = $model->validate($form, $data);

        // Check for errors.
        if ($data === false) {
//            die('errors');
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $this->app->enqueueMessage($errors[$i], 'warning');
                }
            }

            $jform = $this->input->get('jform', array(), 'ARRAY');

            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.data', $jform);

            // Redirect back to the edit screen.
            $menu = Factory::getApplication()->getMenu();
            $item = $menu->getActive();
            $url = $item->link . '&id=' . $item->id;
            $this->setRedirect(Route::_($url, false));

            $this->redirect();
        }

        // Attempt to create a new user and a new profile
        $new_user_id = $model->save($data);

        // Check for errors.
        if ($new_user_id === false) {
            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.data', $data);

            // Redirect back to the edit screen.
            $menu = Factory::getApplication()->getMenu();
            $item = $menu->getActive();
            $url = $item->link . '&Itemid=' . $item->id;
//            var_dump($item->id);
//            die($url);
            $this->setRedirect(Route::_($url, false));
            $this->redirect();
        }
//        die('no error from model');

        if ($new_user_id == 0) {
            $this->app->enqueueMessage('Unable to create User', 'error');
//            die('Unable to create user');
            return false;
        }

        // Clear the profile id from the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.id', null);

        // Flush the data from the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.data', null);

        // If self registering, redirect to thank you screen
        // else show available mailing lists
        $current_user = Factory::getApplication()->getIdentity()->id;
        if ($current_user == 0) {   // Self registering, redirect to welcome screen
            $url = 'index.php?option=com_ra_mailman&task=profile.showWelcome&user_id=' . $new_user_id;
        } else {                    // Administrator, redirect to allow selection of lists
            $url = 'index.php?option=com_ra_mailman&view=list_select&user_id=' . $new_user_id;
        }
//        $this->app->enqueueMessage('Redirecting to ' . $url, 'info');
        $this->setRedirect(Route::_($url, false));
        $this->redirect();
    }

    public function showWelcome() {
// Shows a welcome message after a User has self-registered
// (Would be better if displayed as a View)
        $user_id = Factory::getApplication()->input->getInt('user_id', '0');
        $params = ComponentHelper::getParams('com_ra_mailman');
        $welcome_message = $params->get('welcome_message');
        $objHelper = new ToolsHelper;
        $sql = 'SELECT u.email, u.username, u.registerDate, p.home_group ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.id=' . $user_id;
        $item = $objHelper->getItem($sql);

        echo '<h2>Welcome to MailMan</h2>';
        echo '<p>' . $params->get('welcome_message') . '</p>';

        echo '<p>An account has been created for you, please ';

        echo $objHelper->buildLink(uri::base() . 'index.php?option=com_users&view=login', "Login", False);
        echo 'using your email address as "User name" and request a password reset by clicking on "Forgotten your password".<p>';

        $sql = 'SELECT group_code, name FROM `#__ra_mail_lists` ';
        $sql .= 'WHERE group_primary="' . $item->home_group . '" ';
        $list = $objHelper->getItem($sql);

        if ($list->name > '') {
            echo '<p>You have been subscribed to the newsletter <b>' . $list->name . '</b> ';
            echo 'from your local Ramblers group <b>' . $list->group_code . '</b>';
            echo '. There may be other newsletters that interest you, you can manage  ';
            echo 'these after you have logged on</p>';
        }
        echo $objHelper->backButton('index.php');
    }

    public function submit($key = NULL, $urlVar = NULL) {
        die('controller/submit');
    }

}
