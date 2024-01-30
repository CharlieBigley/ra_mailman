<?php

/**
 * @version    4.0.9
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 10/12/23 CB check username before creating a new user
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Filter\OutputFilter;
// use \Joomla\CMS\User\User
use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Profile model.
 *
 * @since  4.0.0
 */
class ProfileModel extends AdminModel {

    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  4.0.0
     */
    protected $text_prefix = 'com_ra_mailman';

    /**
     * @var    string  Alias to manage history control
     *
     * @since  4.0.0
     */
    public $typeAlias = 'com_ra_mailman.profile';

    /**
     * @var    null  Item data
     *
     * @since  4.0.0
     */
    protected $item = null;

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interrogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   4.0.0
     */
    public function getForm($data = array(), $loadData = true) {
        // Initialise variables.
        $app = Factory::getApplication();
        $list_id = $app->input->getInt('list_id', '0');

        // Get the form.
        $form = $this->loadForm(
                'com_ra_mailman.profile',
                'profile',
                array(
                    'control' => 'jform',
                    'load_data' => $loadData
                )
        );


        if (empty($form)) {
            return false;
        }
        // set fields to read-only if not registering a new user
        $id = $form->getvalue('id');
//        echo 'id ' . $form->getvalue('id') . '<br>';
//        echo 'name ' . $form->getvalue('preferred_name') . '<br>';
        if ($id == 0) {
            $form->removeField('created');
            $form->removeField('created_by');
            $form->removeField('modified');
            $form->removeField('modified_by');
        } else {
            $form->setFieldAttribute('email', 'readonly', "true");
            // lookup details from the user record
            $objHelper = new ToolsHelper;
            $sql = 'SELECT name, email FROM `#__users` WHERE id=' . $id;
            $row = $objHelper->getItem($sql);
            if ($row) {
                $form->setFieldAttribute('preferred_name', 'default', $row->name);
                $form->setFieldAttribute('email', 'default', $row->email);
            }
        }
        return $form;
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   4.0.0
     */
    public function getTable($type = 'Profile', $prefix = 'Administrator', $config = array()) {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed    Object on success, false on failure.
     *
     * @since   4.0.0
     */
    public function getItem($pk = null) {
//        if (is_null($pk)) {
//            die('pk is null');
//        } else {
//            die('pk=' . $pk);
//        }
        if ($item = parent::getItem($pk)) {
            if (isset($item->params)) {
                $item->params = json_encode($item->params);
            }

            // Do any procesing on fields here if needed
        }

        return $item;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   4.0.0
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_ra_mailman.edit.profile.data', array());

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
            /*
              // Support for multiple or not foreign key field: privacy_level
              $array = array();

              foreach ((array) $data->privacy_level as $value) {
              if (!is_array($value)) {
              $array[] = $value;
              }
              }
              if (!empty($array)) {

              $data->privacy_level = $array;
              }
             *
             */
        }

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array $data The form data
     *
     * @return  bool
     *
     * @throws  Exception
     * @since   4.0.0
     */
    public function save($data) {
        // If id is given, it will be of the User record
        // A corresponding Profile record may or may not exist
        $app = Factory::getApplication();
        $user = Factory::getApplication()->getIdentity();
        $objUserHelper = new UserHelper;

        // change group code to upper case
        $data['home_group'] = strtoupper($data['home_group']);

        $id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('profile.id');
//        $state = (!empty($data['state'])) ? 1 : 0;
//        $id = (INT) $data['id'];
//        $app->enqueueMessage('Model invoked, id= ' . $id, 'info');
        if ($id) {
            // Check the user can edit this item
            $authorised = $user->authorise('core.edit', 'com_ra_mailman') || $authorised = $user->authorise('core.edit.own', 'com_ra_mailman');
        } else {
            // Check the user can create new items in this section
            $authorised = $user->authorise('core.create', 'com_ra_mailman');
        }

        if ($authorised !== true) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
//        var_dump($data);
        $group_code = $data['home_group'];
        $email = $data['email'];
        $preferred_name = $data['preferred_name'];
//      see if this email is already in use
        $check_email = $objUserHelper->checkEmail($email, $preferred_name, $group_code);
        if ($check_email === True) {

        } else {
            Factory::getApplication()->enqueueMessage($check_email, 'error');
            return false;
        }

        $table = $this->getTable();

        if (!empty($id)) {
            $table->load($id);

            try {
                if ($table->save($data) === true) {
//                    Factory::getApplication()->enqueueMessage('Model: save successful', 'info');
                    return $table->id;
                } else {
                    Factory::getApplication()->enqueueMessage($table->getError(), 'error');
                    return false;
                }
            } catch (\Exception $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                return false;
            }
        }
        // We are creating a new profile record
        echo 'Creating new user<br>';
        echo 'Current user<br>' . $user->id . '<br>';
        // first create a user
        $objUserHelper->group_code = $data['home_group'];
        $objUserHelper->name = $data['preferred_name'];
        $objUserHelper->email = $data['email'];
        $response = $objUserHelper->createUser();
        if ($response == false) {
            Factory::getApplication()->enqueueMessage($objUserHelper->error, 'error');
            return false;
        }
        // Get id of the user just created
        $user_id = $objUserHelper->user_id;
//        Factory::getApplication()->enqueueMessage('Model: created user ' . $user_id, 'info');
        // Then create a profile record with the same id
        $response = $objUserHelper->createProfile($user_id, $data['home_group']);
        if ($response == false) {
            Factory::getApplication()->enqueueMessage($objUserHelper->error, 'error');
            return false;
        }
        /*
          // notify the administrator that a new user has been added on the front end
          // get the id of the person to notify from global config
          $params = Factory::getApplication()->getParams();

          // See if an Administrator has been specified to receive emails about new users
          $notify_new_users = (int) $params->get('notify_new_users');
          echo 'Notify = <br>' . $notify_new_users . '<br>';
          if ($notify_new_users > 0) {
          $this->notifyUser($notify_new_users);
          }
         *
         */
        return true;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  Table Object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function prepareTable($table) {
        jimport('joomla.filter.output');
        if ($table->groups_to_follow == '') {
            $table->groups_to_follow = $table->home_group;
        }
//        $table->home_group = strtoupper($table->home_group);
    }

    //public function validate(\Joomla\CMS\Form\Form $form, array $data, string $group = null) {
    /*
      public function validate($form, array $data, string $group = null) {
      parent::validate($form, $data, $group);
      //     return false;
      // return $data;
      }
     */
}
