<?php

/**
 * @version     4.0.11
 * @package     com_ra_mailman
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie Bigley <webmaster@bigley.me.uk> - https://www.developer-url.com
 * Invoked from controllers/dataload to import Users, will be passed 4 parameters:
 *  method_id, list_id, processing and filename
 * Data Type: 3 Download from Insight Hub
 *            4 Export from MailChimp
 *            5 Simple csv file
 * Processing: 0 = report only
 *             1 = Update database
 *
 * 06/12/22 CB Created from com ramblers as LoadUsers
 * 06/12/22 CB use Ra_toolsProfile not RamblerProfile
 * 02/02/23 CB moved from site, include classes Profile and Profilebase (Don't know why this is necessary)
 * 16/01/23 CB changed messaging from processFile
 * 17/01/23 CB revert to writing direct to user table
 * 20/04/23 CB implement load from Insight Hub
 * 17/06/23 CB rebuilt to Joomla 4
 * 23/06/23 CB Move to Site, when creating user force reset of password
 * 23/06/23 CB attempted to clear user from session after it has been created
 * 23/10/23 add field sendEmail, pass array of groups rather than call linkUser
 * 13/11/23 CB link to group Registered as well as Public
 * 09/12/23 CB change validation of email, also check unique username (validEmail instead of checkEmail)
 */

namespace Ramblers\Component\Ra_mailman\Site\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use \Joomla\CMS\User\User;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Ra_mailman helper class
 */
class UserHelper {

    // These five variable are defined by the calling program
    public $method_id;
    public $group_code;
    public $list_id;
    public $processing;
    public $filename;
    // These are available after processing
    public $error;
    // These variables are used internally
    public $email;
    public $name;
    public $user_id;
    protected $open;
    protected $objHelper;
    protected $objMailHelper;
    protected $error_count;
    protected $record_count;
    protected $record_type;
    protected $users_created;
    protected $users_required;

    public function __construct() {
        $this->record_count = 0;
        $this->users_created = 0;

// When subscribing, always subscribe as User (rather than an Author)
        $this->record_type = 1;
        $this->objMailHelper = new Mailhelper;
        $this->objHelper = new ToolsHelper;
    }

    public function checkEmail($email, $username, $group_code) {
        // Returns True or an error message
        $objHelper = new ToolsHelper;
        $sql = 'SELECT u.id, u.name, u.registerDate, p.home_group ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.email="' . $email . '"';
        $item = $objHelper->getItem($sql);
        if (!is_null($item)) {
            if ($item->id > 0) {
                return 'This email is already in use for ' . $item->name . '/' . $item->home_group . ' registered ' . $item->registerDate;
            }
        }

        $sql = 'SELECT u.id, u.name, u.registerDate, p.home_group ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.name="' . $username . '" ';
        $sql .= 'AND p.home_group="' . $group_code . '" ';
//        echo $sql . '<br>';
//        die($sql);
        $item = $objHelper->getItem($sql);
        if (!is_null($item)) {
            if ($item->id > 0) {
                return 'This Name is already in use for ' . $item->email . '/' . $item->home_group . ' registered ' . $item->registerDate;
            }
        }
        return True;
    }

    public function checkExistingUser($email, $username, $group_code) {
        // Invoked from the front end if administrator is trying to register a new user
        // Returns ID of existing user, if one found
        $objHelper = new ToolsHelper;
        $sql = 'SELECT u.id ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.email="' . $email . '"';
        $sql .= 'AND u.name = "' . $username . '"';
        $sql .= 'AND p.home_group = "' . $group_code . '"';
        return $objHelper->getValue($sql);
    }

    public function createProfile() {
        //    Create a record in ra_profiles
        $user = Factory::getApplication()->getIdentity();
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $date = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        $query->insert($db->quoteName('#__ra_profiles'))
                ->set('id =' . $db->quote($this->user_id))
                ->set('home_group =' . $db->quote($this->group_code))
                ->set('groups_to_follow  =' . $db->quote($this->group_code))
                ->set('preferred_name =' . $db->quote($this->name))
                ->set('created =' . $db->quote($date))
                ->set('created_by =' . $db->quote($user->id))
        ;
//        echo 'query=' . (string) $query . '<br>';
        $db->setQuery($query);
        return $db->execute();
    }

    public function createProfile_2($user_id, $group_code) {
        // Fails to find Instance of table
        $data = array(
            'id' => $user_id,
            'home_group' => $db->quote($group_code),
            'groups_to_follow' => $db->quote($group_code),
            'preferred_name' => $db->quote($this->name),
        );
        $table = Table::getInstance('Profile', 'Table');
        if (!$table->bind($data)) {
            echo 'could not bind<br>';
            return false;
        }
        if (!$table->check()) {
            echo 'could not validate<br>';
            return false;
        }
        if (!$table->store(true)) {
            echo 'could not store<br>';
            return false;
        }
    }

    public function createProfile_1($user_id, $group_code) {
//    Create a record in ra_profiles
        $user = Factory::getApplication()->getIdentity();
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Prepare the insert query.
        $query->set('id =' . $db->quote($user_id))
                ->set('home_group =' . $db->quote($group_code))
                ->set('groups_to_follow  =' . $db->quote($group_code))
                ->set('preferred_name =' . $db->quote($this->name))
        ;
//      Check that record not already present
//      should not be an existing records, but if there is, update it anyway
        $sql = 'SELECT id FROM #__ra_profiles WHERE id=' . $user_id;
        echo $sql;
        $record_exists = $this->objHelper->getValue($sql);
        $date = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        if ($record_exists > 0) {
            echo 'Yes<br>';
            echo $query->toSql();
            $query->set('modified =' . $db->quote($date))
                    ->set('modified_by =' . $db->quote($user->id))
                    ->update($db->quoteName('#__ra_profiles'));
        } else {
            echo 'No<br>';
            echo 'query=' . (string) $query . '<br>';
            $query->set('created =' . $db->quote($date))
                    ->set('created_by =' . $db->quote($user->id))
                    ->insert($db->quoteName('#__ra_profiles'));
        }
        // $this->error = 'Unable to create User record for ' . $this->group_code . ' ' . $this->name;
    }

    public function createUser() {
        /*
         * This uses Joomla objects to create a User record (and send them a message about the new password)
         * It is used from the front-end (controllers/profile) and from the backend (mailman User / New
         * However, if used from the back end it seems only to work the first time it is invoked
         * 23/10/23 add field sendEmail, pass array of groups rather than call linkUser
         */

        if ($this->name == 'Email Address') {
            // this is the first line of a MailChimp export
            return;
        }
        $this->user_id = 0;

        $password = '$2y$10$PCUXW4xpLTsLGmdJJ4NqUuuNSnpq7fBkZxB4XiqUNFq8tP1Ha3FHa'; // unspecifiedpassword
        // This code only seems to work for the first user
        $user = new User();   // Write to database
        $data = array(
            "name" => $this->name,
            "username" => $this->email,
            "password" => $password,
            "password2" => $password,
            "sendEmail" => '1',
            "group" => array('1', '2'), // Public & Registered
            //          "require_reset" =>1,
            "email" => $this->email
        );
        if (!$user->bind($data)) {
            $this->error = 'Could not validate data - Error: ' . $user->getError();
            return false;
        }

        if (!$user->save()) {
            // throw new Exception("Could not save user. Error: " . $user->getError());
            $this->error = 'Could not create user - Error: ' . $user->getError();
            return false;
        }
        $this->user_id = $user->id;
//        $this->linkUser();
        Factory::getSession()->clear('user', "default");
        return true;
    }

    public function createUserDirect() {
        // writes a record to the users table
        if ($this->name == 'Email Address') {
// this is the first line of a MailChimp export
            return;
        }
        $this->user_id = 0;

        $date = Factory::getDate();
        $params = '{"admin_style":"","admin_language":"","language":"","editor":"","timezone":""}';
        $password = '$2y$10$PCUXW4xpLTsLGmdJJ4NqUuuNSnpq7fBkZxB4XiqUNFq8tP1Ha3FHa'; // unspecifiedpassword
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Prepare the insert query.
        $query
                ->insert($db->quoteName('#__users'))
                ->set('name =' . $db->quote($this->name))
                ->set('username =' . $db->quote($this->email))
                ->set('email =' . $db->quote($this->email))
                ->set('password =' . $db->quote($password))
                ->set('registerDate =' . $db->quote($date->toSQL()))
                ->set("activation =''")
                ->set('params =' . $db->quote($params))
                ->set("otpKey =''")
                ->set("otep =''")
        //                ->set('requireReset=' . $db->quote($requireReset))
        ;
//        echo $query . '<br>';
        //      Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
        // $db_insertid can be flakey
//        $this->user_id = $db->insertid();
// Factory::getApplication()->enqueueMessage('Unable to create User record for ' . $this->group_code . ' ' . $this->name, 'Error');
        if ($this->lookupUser()) {
            Factory::getApplication()->enqueueMessage('Created MailMan user record for ' . $this->group_code . ' ' . $this->name, 'Info');

            $this->linkUser(1);  // Public
            $this->linkUser(2);  // Registered
            $this->sendEmail();
            $this->createProfile();
            return true;
        }
        $this->error = 'Unable to create User record for ' . $this->group_code . ' ' . $this->name;
        return false;
    }

    protected function addJoomlaUser() {
        $password = self::randomkey(8);
        $data = array(
            "name" => $this->name,
            "username" => $this->email,
            "password" => $password,
            "password2" => $password,
            "email" => $this->email,
            "reset" => 1
        );

        // $user = clone(Factory::getUser());
        $user = new User();
        //Write to database
        if (!$user->bind($data)) {
            throw new Exception("Could not bind data. Error: " . $user->getError());
        }
        if (!$user->save()) {
            throw new Exception("Could not save user. Error: " . $user->getError());
        }

        return $user->id;
    }

    protected function linkUser($group_id) {
        //  Links User to given group
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query
                ->insert($db->quoteName('#__user_usergroup_map'))
                ->set('user_id =' . $db->quote($this->user_id))
                ->set('group_id=' . $db->quote($group_id));
        $db->setQuery($query);
//        echo $query . '<br>';
        $return = $db->execute();

        if ($return == false) {
            $this->error = 'Unable to link ' . $this->user_id . ' to ' . $group_id;
            Factory::getApplication()->enqueueMessage('Unable to link MailMan user ' . $group_id, 'Warning');
        }
        return $return;
    }

    protected function lookupUser() {
        $this->user_id = 0;
        $sql = 'SELECT id FROM #__users WHERE email="' . $this->email . '"';
//        echo $sql . '<br>';
        $this->user_id = (int) $this->objHelper->getValue($sql);
        return $this->user_id;
//        $db = JFactory::getDbo();
//        $query = $db->getQuery(true);
//        $query->select('a.id');
//        $query->from('`#__users` AS a');
//        $query->where($db->qn('a.email') . ' = ' . $db->q($email));
//        $db->setQuery($query);
//        return $db->loadResult();
    }

    protected function parseLine($data) {
        /*
         * Sets up the intername field name, email etc
         * The format of the line depends on the type of data being loaded
         */
        switch ($this->method_id) {
            case 3:     // Download from Insight Hub
                // First record is just column headings
                if ($this->record_count == 1) {
                    echo $this->record_count . ': Ignoring header row<br>';
                    return 0;
                } else {
                    $this->email = $data[6];
                    $this->name = $data[4] . ' ' . $data[5];
                    if ($this->group_code != substr($data[0], 0, 4)) {
                        $this->error_count++;
                        echo '<b>' . $this->email . ' is in ' . substr($data[0], 0, 4) . ', not in Group ' . $this->group_code . "</b><br>";
                        return 0;
                    }
                }
                if ($this->name == ' ') {
                    $this->error_count++;
                    echo '<b>' . $this->email . ' has no name' . "</b><br>";
                    return 0;
                } else {
                    return 1;
                }
            case 4:  // Mailchimp
                // First record is just column headings
                if ($this->record_count == 1) {
                    echo $this->record_count . ': Ignoring header row<br>';
                    return 0;
                } else {
                    $this->email = $data[0];
                    $this->name = $data[1] . ' ';
                    if ($data[2] == '') {
                        $this->name .= $data[4];
                    } else {
                        $this->name .= $data[2];
                    }
                }
                if ($this->name == ' ') {
                    $this->error_count++;
                    echo '<b>' . $this->email . ' has no name' . "</b><br>";
                    return 0;
                } else {
                    return 1;
                }
            case 5:    // simple csv file
                if ($this->record_count == 1) {
                    echo 'Ignoring header row<br>';
                    return 0;
                } else {
                    $return = 1;
                    $this->group_code = $data[0];
                    $this->name = $data[1];
                    $this->email = $data[2];
                    if ($this->group_code == '') {
                        $this->error_count++;
                        echo '<b>First column (Group code) is blank' . "</b><br>";
                        $return = 0;
                    }

                    if ($this->name == '') {
                        $this->error_count++;
                        $message = '<b>Second column (name) is blank</b>';
                        $message .= ', email=' . $this->email;
                        echo $message . "<br>";
                        $return = 0;
                    }

                    if ($this->email == '') {
                        $this->error_count++;
                        echo '<b>Third column (email) is blank' . "</b><br>";
                        $return = 0;
                    }
                    return $return;
                }
        }
    }

    public function processFile() {
//        $diagnostic = "method_id=" . $this->method_id . ', processing=' . $this->processing . ', filename=' . $this->filename;
//        Factory::getApplication()->enqueueMessage("Helper: " . $diagnostic, 'Message');

        if (!file_exists($this->filename)) {
            echo $this->filename . " not found";
            return 0;
        }
        if (substr(JPATH_ROOT, 14, 6) == 'joomla') {
            echo '<h4>deleting test data</h4> ';
            $this->purgeTestData();
        }

        $sql = "Select group_code, name, record_type from `#__ra_mail_lists` "
                . "WHERE id='" . $this->list_id . "'";
        $item = $this->objHelper->getItem($sql);
        $this->group_code = $item->group_code;
        $title = $item->group_code . ' ' . $item->name;
        if ($item->record_type == 'O') {
            $this->open = true;
        } else {
            $this->open = false;
            $title .= ' (Closed list)';
        }

        if ($this->processing == 1) {
            echo '<h2>Processing ';
        } else {
            echo '<h2>Validating ';
        }
        if ($this->method_id == 3) {
            echo 'Members from corporate feed';
        } elseif ($this->method_id == 4) {
            echo 'MailChimp export';
        } elseif ($this->method_id == 5) {
            echo 'CSV';
        } else {
            echo 'Type=' . $this->method_id . 'Not recognised';
        }

        echo '<h4>List=' . $title . '<br>';
        echo 'File=' . $this->filename . '</h4>';
        $this->processRecords();
        echo '<br>' . $this->record_count . ' records read<br>';
        if ($this->error_count > 0) {
            echo "<b>$this->error_count errors</b><br>";
        }
        echo $this->users_required . ' Users required<br>';
        echo $this->users_created . ' Users created<br>';
        if ($this->method_id == 3) {

            $this->processLapsers();
        }
    }

    protected function processLapsers() {
        // Find any members on previous files, but not this one
        // Set up the date to which current members have been renewed
        $today = date('Y-m-d');
        $bounce_date = date('Y-m-d', strtotime($today . ' + 1 year'));


//        echo '+y ' . $bounce_date . '<br>';
        // Find subscriptions with renewal date before this
        $sql = "SELECT s.id AS subscription_id, s.expiry_date, l.id AS list_id, ";
        $sql .= "u.id as user_id, u.name AS 'User', u.email AS 'email' ";
        $sql .= 'FROM `#__ra_mail_lists` AS l ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s ON s.list_id = l.id ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = s.user_id ';
        $sql .= 'WHERE l.id=' . $this->list_id . ' ';
        $sql .= 'AND (datediff(' . $bounce_date . ',expiry_date) > 0)  ';
        $sql .= ' AND s.state=1';
        $sql .= ' ORDER BY u.username';
//        echo $sql;
//        $this->objHelper->showQuery($sql);
    }

    protected function processRecords() {
        $this->record_count = 0;
        $this->users_required = 0;
        $handle = fopen($this->filename, "r");
        if ($handle == 0) {
            echo 'Unable to open ' . $this->filename . '<br>';
            return 0;
        }
//        die('File ' . $this->filename . ' opened OK');
        $sql_lookup = 'SELECT id FROM #__users WHERE email="';
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $this->record_count++;
            echo $this->record_count . ': ';
            if ($this->record_count == 1) {
                echo 'Ignoring header row<br>';
            } elseif (substr($data[0], 0, 1) == '#') {
                echo 'Ignoring comment ' . $data[0] . ',' . $data[1], '<br>';
            } else {
                /*
                 * After $this->parseLine, the following variables will have been set up:
                 *     $this->group_code
                 *     $this->name
                 *     $this->email
                 */
                if (($this->parseLine($data)) AND ($this->validEmailFormat())) {
                    if (JDEBUG) {
                        echo 'group=' . $this->group_code . ', name=' . $this->name . ', email=' . $this->email . "<br>";
                    }
                    $subscription_required = false;
                    $message = '';
                    $user_id = (int) $this->lookupUser();
//                    echo $sql_lookup . $this->email . '"<br>';
//                    $user_id = (int) $this->objHelper->getValue($sql_lookup . $this->email . '"');
                    //                    echo "User for " . $this->email . ' = ' . $user_id;
                    if ($user_id == 0) {
                        $this->users_required++;
                        $message .= 'User ' . $this->name . ' <b>not present</b> (' . $this->email . ')';
                        if ($this->processing == 1) {
                            $response = $this->createUserDirect();
                            if ($response) {
                                $user_id = $this->user_id;
                                $message .= ', User created';
                                if (JDEBUG) {
                                    $message .= ', id=' . $user_id;
                                }
                                $this->users_created++;
                                $subscription_required = true;
                            } else {
                                $subscription_required = false;
                                $message .= ', Error creating User ' . $this->name . '/' . $this->email;
                            }
                        }
                    } else {
                        $message .= 'User ' . $this->name . ' exists';
                        $method = $this->objMailHelper->isSubscriber($this->list_id, $user_id);
                        if ($method == '') {
                            $message .= ', Subscription <b>not present</b>';
                            $subscription_required = true;
                        } else {
                            $message .= ', subscription exists, method=<b>' . $method . '</b>';
                            $subscription_required = false;
                        }
                    }

                    if (($subscription_required) AND ($this->processing == 1)) {
                        $this->objMailHelper->subscribe($this->list_id, $user_id, $this->record_type, $this->method_id);
//                        echo $this->record_count . ": Subscription created OK" . '<br>';
                        $message .= ', Subscription created';
                    }
                    echo $message . '<br>';
                }
            }
//            if (($this->record_count == 30) AND (substr(JPATH_ROOT, 14, 6) == 'joomla')) {            // Development
//                return;
//            }
        }
        fclose($handle);
    }

    public function purgeTestData() {
        // First check user is a Super-User
        if (!$this->objHelper->isSuperuser()) {
            Factory::getApplication()->enqueueMessage('Invalid access', 'error');
            $target = 'index.php?option=com_ramblers&view=mail_lsts';
            $this->setRedirect(Route::_($target, false));
        }
        /*
          //update field created in ra_profiles
          $sql = 'SELECT id,created,modified from #__ra_profiles';
          $rows = $this->objHelper->getRows($sql);
          foreach ($rows as $row) {
          echo $row->created . '<br>';
          if (($row->created == '0000-00-00') OR ($row->created == '0000-00-00 00:00:00')) {
          $this->objHelper->executeCommand('DELETE FROM #__ra_profiles WHERE id=' . $row->id);
          } else {
          if (strlen($row->created) == 10) {
          $new = $row->created . ' 00:00:00';
          $update = 'UPDATE #__ra_profiles SET created="' . $new . '" WHERE id=' . $row->id;
          echo "$update<br>";
          $this->objHelper->executeCommand($update);
          }
          }
          }
         */
        // For test
        //$start_user = 1026;  // After Andrea Parton
        //$start_subs = 54;
        // For dev
        $start_user = 980;  // After Barry Collis
        $start_subs = 12;

        // delete details of any emails sent
        $sql = 'DELETE FROM #__ra_mail_recipients WHERE user_id>' . $start_user;
        echo $sql . '<br>';
        $this->objHelper->executeCommand($sql);

        // Delete any subscriptions
        $sql = 'DELETE FROM #__ra_mail_subscriptions_audit WHERE object_id>' . $start_subs;
        echo $sql . '<br>';
        $rows = $this->objHelper->executeCommand($sql);
        $sql = 'DELETE FROM #__ra_mail_subscriptions WHERE user_id>' . $start_user;
        echo $sql . '<br>';
        $this->objHelper->executeCommand($sql);

        // delete profile audit records
        $sql = 'DELETE FROM #__ra_profiles_audit WHERE object_id>' . $start_user;
        echo $sql . '<br>';
        $this->objHelper->executeCommand($sql);

        // delete the profile record itself
        $sql = 'DELETE FROM #__ra_profiles WHERE id>' . $start_user;
        echo $sql . '<br>';
        $this->objHelper->executeCommand($sql);

        // Delete the users
        $sql = 'DELETE FROM #__user_usergroup_map WHERE user_id>' . $start_user;
        echo $sql . '<br>';
        $this->objHelper->executeCommand($sql);
        $sql = 'DELETE FROM #__users WHERE id>' . $start_user;
        echo $sql . '<br>';
        $this->objHelper->executeCommand($sql);

        echo 'Test data deleted<br>';
    }

    public function sendEmail() {
        // send email to the administrator
        $params = ComponentHelper::getParams('com_ra_mailman');
        $notify_id = $params->get('email_new_user', '0');

        if ($notify_id > 0) {
            $sql = 'SELECT email FROM #__users WHERE id=' . $notify_id;
            $to = $this->objHelper->getValue($sql);
            if ($to == '') {
                Factory::getApplication()->enqueueMessage('Unable to find email address for user ' . $notify_id, 'Warning');
            }
            $title = 'A new user has been registered to MailMan';
            $body = 'New user registration:' . '<br>';
            $body .= 'Name <b>' . $this->name . '</b><br>';
            $body .= 'Group <b>' . $this->group_code . '</b><br>';
            $body .= 'Email <b>' . $this->email . '</b><br>';
            $response = $this->objMailHelper->sendEmail($to, $to, $title, $body);
            if ($response) {
                Factory::getApplication()->enqueueMessage('Notification sent to ' . $to, 'Info');
            }
        }
    }

    private function validEmailFormat() {
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
//            echo "Email address '$this->email' is considered valid.\n";
            return true;
        }
        $this->error_count++;
        echo "User $this->name: email address '$this->email' is considered invalid<br>";
        return false;
    }

    /**
     *   Random Key
     *
     *   @returns a string
     * */
    public static function randomKey($size) {
        // Created 26/04/22 from https://stackoverflow.com/questions/1904809/how-can-i-create-a-new-joomla-user-account-from-within-a-script
        $bag = "abcefghijknopqrstuwxyzABCDDEFGHIJKLLMMNOPQRSTUVVWXYZabcddefghijkllmmnopqrstuvvwxyzABCEFGHIJKNOPQRSTUWXYZ";
        $key = array();
        $bagsize = strlen($bag) - 1;
        for ($i = 0; $i < $size; $i++) {
            $get = rand(0, $bagsize);
            $key[] = $bag[$get];
        }
        return implode($key);
    }

}
