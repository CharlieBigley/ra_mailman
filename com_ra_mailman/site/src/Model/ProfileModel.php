<?php

/**
 * @version    4.0.10
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 13/11/23 CB set up group_code from component default
 * 14/11/23 CB Don't rely on Joomla user registration
 * 09/12/23 CB new validation for existing email / username
 * 04/01/24 CB don't create profile record (automatically done in createUserDirect)
 */

namespace Ramblers\Component\Ra_mailman\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Factory;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\MVC\Model\FormModel;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Helper\TagsHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\MailHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Ra_mailman model.
 *
 * @since  4.1.0
 */
class ProfileModel extends FormModel {

    private $item = null;

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   4.1.0
     *
     * @throws  Exception
     */
    protected function populateState() {
        $app = Factory::getApplication('com_ra_mailman');

// Load state from the request userState on edit or from the passed variable on default
        if (Factory::getApplication()->input->get('layout') == 'edit') {
            $id = Factory::getApplication()->getUserState('com_ra_mailman.edit.profile.id');
        } else {
            $id = Factory::getApplication()->input->get('id');
            Factory::getApplication()->setUserState('com_ra_mailman.edit.profile.id', $id);
        }

        $this->setState('profile.id', $id);
        $layout = Factory::getApplication()->input->get('layout');

// Load the parameters.
        $params = $app->getParams();
        $params_array = $params->toArray();
//       var_dump($params_array);
//       die('layout=' . $layout . ', Mode=' . $params_array['mode']);
        if (isset($params_array['item_id'])) {
            $this->setState('profile.id', $params_array['item_id']);
        }

        $this->setState('params', $params);
    }

    /**
     * Method to get an object.
     *
     * @param   integer $id The id of the object to get.
     *
     * @return  Object|boolean Object on success, false on failure.
     *
     * @throws  Exception
     */
    public function getItem($id = null) {
        if ($this->item === null) {
            $this->item = false;

            if (empty($id)) {
                $id = $this->getState('profile.id');
            }

// Get a level row instance.
            $table = $this->getTable();
            $properties = $table->getProperties();
            $this->item = ArrayHelper::toObject($properties, CMSObject::class);

            if ($table !== false && $table->load($id) && !empty($table->id)) {
                $user = Factory::getApplication()->getIdentity();
                $id = $table->id;


                $canEdit = $user->authorise('core.edit', 'com_ra_mailman') || $user->authorise('core.create', 'com_ra_mailman');

                if (!$canEdit && $user->authorise('core.edit.own', 'com_ra_mailman')) {
                    $canEdit = $user->id == $table->created_by;
                }

                if (!$canEdit) {
                    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
                }

// Check published state.
                if ($published = $this->getState('filter.published')) {
                    if (isset($table->state) && $table->state != $published) {
                        return $this->item;
                    }
                }

// Convert the Table to a clean CMSObject.
                $properties = $table->getProperties(1);
                $this->item = ArrayHelper::toObject($properties, CMSObject::class);
            }
        }

        return $this->item;
    }

    /**
     * Method to get the table
     *
     * @param   string $type   Name of the Table class
     * @param   string $prefix Optional prefix for the table class name
     * @param   array  $config Optional configuration array for Table object
     *
     * @return  Table|boolean Table if found, boolean false on failure
     */
    public function getTable($type = 'Profile', $prefix = 'Administrator', $config = array()) {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get the profile form.
     *
     * The base form is loaded from XML
     *
     * @param   array   $data     An optional array of data for the form to interogate.
     * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
     *
     * @return  Form    A Form object on success, false on failure
     *
     * @since   4.1.0
     */
    public function getForm($data = array(), $loadData = true) {
// Get the form.
        $form = $this->loadForm('com_ra_mailman.profile', 'profile', array(
            'control' => 'jform',
            'load_data' => $loadData
                )
        );

        if (empty($form)) {
            return false;
        }
//     Set value of group_code from component default
        $params = ComponentHelper::getParams('com_ra_tools');
        $group_code = $params->get('default_group');
        $form->setFieldAttribute('group_code', 'default', $group_code);
        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array  The default data is an empty array.
     * @since   4.1.0
     */
    protected function loadFormData() {
        $data = Factory::getApplication()->getUserState('com_ra_mailman.edit.profile.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        if ($data) {

            //           $app = Factory::getApplication('com_ra_mailman');
            //          $params = $app->getParams();
            //          $params_array = $params->toArray();
            //          $mode = $params_array['mode'];
            //          $data->mode = $mode;
            return $data;
        }

        return array();
    }

    /**
     * Method to save the form data.
     *
     * @param   array $data The form data
     *
     * @return  bool
     *
     * @throws  Exception
     * @since   4.1.0
     */
    public function save($data) {
        $id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('profile.id');
        $state = (!empty($data['state'])) ? 1 : 0;
        $app = Factory::getApplication();
        $user = Factory::getApplication()->getIdentity();
        $objUserHelper = new UserHelper;

// change group code to upper case
        $data['home_group'] = strtoupper($data['group_code']);

        if ($id) {
// Check the user can edit this item
            $authorised = $user->authorise('core.edit', 'com_ra_mailman') || $authorised = $user->authorise('core.edit.own', 'com_ra_mailman');
            if ($authorised !== true) {
                throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
            }
//       } else {
//           // Check the user can create new items in this section
//           $authorised = $user->authorise('core.create', 'com_ra_mailman');
        }

        $group_code = $data['group_code'];
        $email = $data['email'];
        $preferred_name = $data['preferred_name'];
        // if user is logged in, and  Group / Name / Email match an existing user, allow selection of further lists
        if ($user->id > 0) {
            $user_id = $objUserHelper->checkExistingUser($email, $preferred_name, $group_code);
            //           die($user_id);
            if ($user_id > 0) {
                Factory::getApplication()->enqueueMessage('This user already exists', 'Info');
                return $user_id;
            }
        }

        //      see if this email is already in use
        $check_email = $objUserHelper->checkEmail($email, $preferred_name, $group_code);

        if ($check_email === True) {

        } else {
            Factory::getApplication()->enqueueMessage($check_email, 'error');
            return false;
        }

// first create a user
        $objUserHelper->group_code = $data['home_group'];
        $objUserHelper->name = $data['preferred_name'];
        $objUserHelper->email = $data['email'];
        // 14/11/23 Joola classes do not properly link to groups, do not send email
        // changed to manual creation
        if (0) {
            // Create a User record using Joomla classes - this will trigger confirmatory email
            // (but system must be configured properly)
            $response = $objUserHelper->createUser();
            if ($response == false) {
                Factory::getApplication()->enqueueMessage('Model: creating Joomla user gave error' . $objUserHelper->error, 'error');
                return false;
            }
            // Then create a profile record with the same id
            $response = $objUserHelper->createProfile($user_id, $data['home_group']);
            if ($response == false) {
                Factory::getApplication()->enqueueMessage($objUserHelper->error, 'error');
                return false;
            }
        } else {
            $response = $objUserHelper->createUserDirect();
            if ($response == false) {
                Factory::getApplication()->enqueueMessage('Model: creating MailMan user gave error' . $objUserHelper->error, 'error');
                return false;
            }
        }
// Get id of the user just created
        $user_id = $objUserHelper->user_id;
//        Factory::getApplication()->enqueueMessage('Model: created user ' . $user_id, 'info');
        // Save the data in the session for the Controller to use
        Factory::getApplication()->setUserState('com_ra_mailman.edit.profile.id', $user_id);


        if ($user_id == 0) {
            $this->subscribeDefault($user_id);
        }
        return $user_id;
    }

    private function subscribeDefault($user_id) {
        // Sets a subscription to the Group's "Primary" list for a new User

        $objHelper = new ToolsHelper;
        $sql = 'SELECT u.email, u.username, u.registerDate, p.home_group ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.id=' . $user_id;
        $item = $objHelper->getItem($sql);

        $sql = 'SELECT id FROM `#__ra_mail_lists` ';
        $sql .= 'WHERE group_primary="' . $item->home_group . '" ';
        $list_id = $objHelper->getValue($sql);
        if ($list_id == 0) {
            $message = 'Sorry, there is no default Newsletter for Group ';
            $message .= $objHelper->lookupGroup($item->home_group);
            Factory::getApplication()->enqueueMessage($message, 'info');
            return;
        }

        $objMailHelper = new MailHelper;
        $record_type = 1;  // subscriber
        $method = 1;       // Self registered
        $objMailHelper->subscribe($list_id, $user_id, $record_type, $method);
    }

}
