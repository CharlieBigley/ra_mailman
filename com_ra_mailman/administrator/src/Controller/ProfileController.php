<?php

/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Profile controller class.
 *
 * @since  4.0.0
 */
class ProfileController extends FormController {

    protected $view_list = 'profiles';

    public function create() {
        // Invoked when a User record exists but not a Profile record
        // Creates a record, allows it to be edited
        $id = Factory::getApplication()->input->getInt('id', '');
        echo 'Controller / id=' . $id . '<br>';
        $objHelper = new ToolsHelper;
        $sql = 'SELECT name, email FROM `#__users` ';
        $sql .= 'WHERE id=' . $id;

        $item = $objHelper->getItem($sql);
        if ($item) {

            $objUserHelper = new UserHelper;
            $objUserHelper->name = $item->name;
            $objUserHelper->email = $item->email;
            $objUserHelper->createProfile($id, '');
            $this->setRedirect(Route::_('/administrator/index.php?option=com_ra_mailman&view=profile&layout=edit&id=' . $id, false));
        } else {
            throw new Exception('Can\'t find User record', 404);
        }
    }

    public function purgeProfile() {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
        $objHelper = new ToolsHelper;
        $id = Factory::getApplication()->input->getInt('id', '0');
        if ($objHelper->isSuperuser()) {
            if ($id > 0) {
                $sql = 'DELETE FROM #__ra_profiles WHERE id=' . $id;
                echo $sql . '<br>';
                $objHelper->executeCommand($sql);
                // delete details of any emails sent
                $sql = 'DELETE FROM #__ra_mail_recipients WHERE user_id=' . $id;
                echo $sql . '<br>';
                $objHelper->executeCommand($sql);

                // Delete any subscriptions
//        $sql = 'DELETE FROM #__ra_mail_subscriptions_audit WHERE object_id>' . $start_subs;
//        echo $sql . '<br>';
//        $objHelper->executeCommand($sql);
                $sql = 'DELETE FROM #__ra_mail_subscriptions WHERE user_id=' . $id;
                echo $sql . '<br>';
                $objHelper->executeCommand($sql);

                // delete profile audit records
                $sql = 'DELETE FROM #__ra_profiles_audit WHERE object_id=' . $id;
                echo $sql . '<br>';
                $objHelper->executeCommand($sql);
            }
        }

        $back = 'administrator/index.php?option=com_ra_mailman&task=reports.duffUsers';
        echo $objHelper->backButton($back);
    }

    /*
     * Function handing the save for adding a new User and Profile record
     */

    public function save($key = null, $urlVar = null) {
        // Check for request forgeries.
        $this->checkToken();

        // find which layout is in use
        $app = Factory::getApplication();
        $layout = $app->input->getCmd('layout', 'new');

        // Initialise variables.
        $model = $this->getModel('Profile', 'Administrator');

        // Get the user data.
        $data = $this->input->get('jform', array(), 'array');
//        var_dump($data);
        if ($data['id'] == 0) {
            $message = 'Profile created';
        } else {
            $message = 'Profile updated';
        }
        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new \Exception($model->getError(), 500);
        }

        // Check for errors.
        if ($data === false) {
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
            $id = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');
            $this->setRedirect(Route::_('administrator/index.php?option=com_ra_mailman&view=profile&layout=' . $layout . '&id=' . $id, false));

            $this->redirect();
        }

        // Attempt to save the data.
        $return = $model->save($data);

        // Check for errors.
        if ($return === false) {
            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.data', $data);
            // Redirect back to the edit screen.
            $id = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');
            $this->setMessage('Save failed, layout=' . $layout . ',id= ' . $id, $model->getError(), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=profile&layout=' . $layout . '&id=' . $id, false));
            $this->redirect();
        }
        $this->setMessage(Text::sprintf('Saved OK', $model->getError()), 'info');
        // Check in the profile.
        if ($return) {
            $model->checkin($return);
        }

        // Clear the profile id from the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.id', null);

        // Redirect to the list screen.
        if (!empty($return)) {
            $this->setMessage($message);
        }

        $menu = Factory::getApplication()->getMenu();
        $item = $menu->getActive();
        $url = (empty($item->link) ? 'index.php?option=com_ra_mailman&view=profiles' : $item->link);
        $this->setRedirect(Route::_($url, false));

        // Flush the data from the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.data', null);
    }

}
