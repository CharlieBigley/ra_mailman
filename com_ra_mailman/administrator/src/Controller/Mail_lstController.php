<?php

/**
 * @version    [BUMP]
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 15/06/23 CB use ra_mail_access
 * 20/06/23 CB copy code from 1.1.4
 * 01/01/24 CB in showSubscribers, only show email if list owner or superuser
 * 08/01/24 CB correct message when updated a blank owner
 * 27/02/24 CB publish/unpublish
 * 29/01/24 CB delete
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHtml;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Mail_lst controller class.
 *
 * @since  1.0.6
 */
class Mail_lstController extends AdminController {

    protected $db;
    protected $objApp;
    protected $objHelper;
    protected $objMailHelper;
    protected $query;
    protected $view_list = 'mail_lsts';

    public function __construct(array $config = array(), \Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null) {
//        die('Mail_lstController');
        parent::__construct($config, $factory);
        $this->db = Factory::getDbo();
        $this->objHelper = new ToolsHelper;
        $this->objApp = Factory::getApplication();
        $this->objMailHelper = new Mailhelper;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function delete() {
        $id = (int) $this->objApp->input->getCmd('id', '');
        $this->setRedirect('index.php?option=com_ra_mailman&view=mail_lsts&layout=statistics&id=' . $id);
    }

    public function export() {
        ToolBarHelper::title('Ramblers MailMan');
        $id = (int) $this->objApp->input->getCmd('id', '');
        if ($id == 0) {
            Factory::getApplication()->enqueueMessage('List not found', 'error');
        } else {
            $sql = "SELECT l.group_code, l.name AS list_name, ";
            $sql .= "u.id, u.name, u.email, ";
            $sql .= "p.home_group ";
            $sql .= 'FROM  `#__ra_mail_lists` AS l ';
            $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s on s.list_id = l.id ';
            $sql .= 'INNER JOIN `#__users` AS u on u.id = s.user_id ';
            $sql .= 'LEFT JOIN #__ra_profiles AS p on p.id = s.user_id ';

            $sql .= 'WHERE u.block=0 ';
            $sql .= 'AND s.state=1 ';
            $sql .= 'AND l.id=' . $id;
            $sql .= ' ORDER BY p.home_group, u.name';
//        echo $sql;
            $count = 0;
            $rows = $this->objHelper->getRows($sql);
            foreach ($rows as $row) {
                if ($count == 0) {
                    echo '<h2>Subscribers for ' . $row->group_code . ' ' . $row->list_name . '</h2>';
                    echo '﻿Group,Name,email' . '<br>';
                }
                $count++;
                echo $row->home_group . ',' . $row->name . ',' . $row->email . '<br>';
            }
            echo '<br>';
        }
        $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.edit&id=' . $id;
        echo $this->objHelper->backButton($target);
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    Optional. Model name
     * @param   string  $prefix  Optional. Class prefix
     * @param   array   $config  Optional. Configuration array for model
     *
     * @return  object	The Model
     *
     * @since   1.0.6
     */
    public function getModelx($name = 'Mail_lst', $prefix = 'Administrator', $config = array()) {
        echo 'parent::getModel';
//        return parent::getModel($name, $prefix, array('ignore_request' => true));
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
        //$model = 99;
        var_dump($model);
        echo '<br>After dump<br>';
        die('parent::getModel');
    }

    public function prime() {
        $input = Factory::getApplication()->input;
        $jform = $input->get('jform', array(), 'ARRAY');
//        print_r($jform);
//        die();
        $id = (int) $jform['id'];
        $group_code = $jform['group_code'];
        $group_primary = $jform['group_primary'];
//        Factory::getApplication()->enqueueMessage('Prime: ' . $group_code . ', ' . $group_primary . ', ' . $id, 'Info');

        if ($group_primary == '') {
            $this->setPrime($id, $group_code);
        } else {
            $sql = 'UPDATE `#__ra_mail_lists` SET group_primary=NULL WHERE id=' . $id;
            $this->objHelper->executeCommand($sql);
        }

//        Factory::getApplication()->enqueueMessage('Save: id=' . $id . ', list_id=' . $list_id, 'comment');
        $this->setRedirect('index.php?option=com_ra_mailman&task=mail_lst.edit&id=' . $id);
    }

    public function publish() {
        // 30/01/24 - attempt to get the model fails wit blank screen
        // Checking if the user can remove object
        $user = $this->app->getIdentity();

        if ($user->authorise('core.edit', 'com_ra_mailman') || $user->authorise('core.edit.state', 'com_ra_mailman')) {
//            die('Get model');
            echo 'getModel<br>';
            $model = parent::getModel('Mail_lst', 'Administrator', array('ignore_request' => true));
            var_dump($model);
            echo '<br>After dump<br>';
            die();
            // Get the user data.
            $id = $this->input->getInt('id');
            $state = $this->input->getInt('state');

            // Attempt to save the data.
            $return = $model->publish($id, $state);

            // Check for errors.
            if ($return === false) {
                $this->setMessage(Text::sprintf('Save failed: %s', $model->getError()), 'warning');
            }

            // Clear the booking id from the session.
            $this->app->setUserState('com_ra_mailman.edit.mail_lst.id', null);

            // Flush the data from the session.
            $this->app->setUserState('com_ra_mailman.edit.mail_lst.data', null);

            // Redirect to the list screen.
            $this->setMessage(Text::_('COM_RA_MAILMAN_ITEM_SAVED_SUCCESSFULLY'));
            $menu = Factory::getApplication()->getMenu();
            $item = $menu->getActive();

            if (!$item) {
                // If there isn't any menu item active, redirect to list view
                $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mail_lsts', false));
            } else {
                $this->setRedirect(Route::_('index.php?Itemid=' . $item->id, false));
            }
        } else {
            throw new \Exception(500);
        }
    }

    public function purge() {
        $list_id = (int) $this->objApp->input->getCmd('list_id', '');
        if ($list_id == 0) {
            Factory::getApplication()->enqueueMessage('List not specified', 'error');
        } else {
            // First check user has access to delete
            $canDo = $this->objMailHelper->canDo('core.delete');
            if (!$canDo) {
                Factory::getApplication()->enqueueMessage('Invalid access', 'notice');
            }
            Factory::getApplication()->enqueueMessage('Function not yet implemented', 'notice');
        }
        $target = 'index.php?option=com_ra_mailman&view=mail_lsts';
        $this->setRedirect(Route::_($target, false));
    }

    public function save($key = null, $urlVar = null) {
        /*
         * A record must be present for the owner of each list in table ra_mail_subscriptions
         * with record_type = 3
         */
// get the data from the HTTP POST request
        $input = $this->objApp->input;
        $data = $input->get('jform', array(), 'array');
        $new_owner_id = (int) $data['owner_id'];
        if ($new_owner_id == 0) {
            Factory::getApplication()->enqueueMessage('Please select Owner of the list', 'Error');
            $return = false;
        }
//        Factory::getApplication()->enqueueMessage('New owner_id=' . $new_owner_id, 'Comment');
        $id = (int) $this->objApp->input->getCmd('id', '');
// For a new record, id will be blank, so processing done on second save
        if ($id > 0) {
            $sql = 'SELECT l.owner_id, l.group_code, l.home_group_only, u.name FROM `#__ra_mail_lists` AS l ';
            $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s on s.list_id = l.id ';
            $sql .= 'LEFT JOIN `#__users` AS u on u.id = l.owner_id ';
            $sql .= 'WHERE l.id=' . $id . ' ';
            $sql .= 'AND s.state=1 ';
            $item = $this->objHelper->getItem($sql);
            //   if (JDEBUG) {
            //       Factory::getApplication()->enqueueMessage('old=' . $item->owner_id, 'Comment');
            //   }
            if ($new_owner_id <> $item->owner_id) {
// Unsubscribe the old owner
                if (JDEBUG) {
                    Factory::getApplication()->enqueueMessage('Unsubscribing ' . $id . ', ' . $item->owner_id . ',3,2,0', 'Comment');
                }
                if ($item->owner_id > 0) {
                    $this->objMailHelper->updateSubscription($id, $item->owner_id, 3, 2, 0);
                }
                $sql = 'SELECT name FROM `#__users` WHERE id=' . $new_owner_id;
                $new_owner_name = $this->objHelper->getValue($sql);
//                Factory::getApplication()->enqueueMessage('new_owner_id=' . $new_owner_id . ', ' . $new_owner_name, 'Comment');
//                Factory::getApplication()->enqueueMessage('Subscribing ' . $id . ', ' . $item->owner_id . ',3,2,1', 'Comment');
                $this->objMailHelper->updateSubscription($id, $new_owner_id, 3, 2, 1);
                $message = 'Owner updated ';
                if ($item->name > '') {
                    $message .= 'from ' . $item->name;
                }
                Factory::getApplication()->enqueueMessage($message . ' to ' . $new_owner_name, 'Message');
            }

            // if home_group_only, check that all users belong to the correct group
            if ($item->home_group_only == '1') {
                $sql = 'SELECT s.id, p.preferred_name from #__ra_mail_subscriptions AS s ';
                $sql .= 'INNER JOIN #__ra_profiles AS p on p.id = s.user_id ';
                $sql .= 'WHERE s.list_id=' . $id . ' ';
                $sql .= 'AND s.state=1 ';
                $sql .= 'AND p.home_group = "' . $item->group_code . '" ';
//                die($sql);
                /*
                  $rows = $this->objMailHelper->getRows($sql);
                 */
            }
        }
//        Factory::getApplication()->enqueueMessage('mail list controller saving record: id=' . $id . ',' . $new_owner_id, 'comment');
        $return = parent::save($key, $urlVar);
//        $return = false;

        return $return;
    }

    private function setPrime($id, $group_code) {
        // Check that no other mailing list for this group is already set as prime group
        $sql = 'SELECT id FROM `#__ra_mail_lists` where group_primary ="' . $group_code . '"';
        $prime_id = (int) $this->objHelper->getValue($sql);
        if (($prime_id > 0) AND ($prime_id != $id)) {
            // another mailing list for this group already set as primary
            $new_name = $this->objHelper->getValue("SELECT name FROM `#__ra_mail_lists` WHERE id=" . $prime_id);
            Factory::getApplication()->enqueueMessage($new_name . ' is already set as primary list for ' . $group_code, 'Error');
            return false;
        } else {
            $sql = 'UPDATE `#__ra_mail_lists` SET group_primary="' . $group_code . '" WHERE id=' . $id;
            if (JDEBUG) {
                Factory::getApplication()->enqueueMessage($sql, 'notice');
            }
            $result = $this->objHelper->executeCommand($sql);
            // Confirm the update has worked
            $sql = 'SELECT id FROM `#__ra_mail_lists` where group_primary ="' . $group_code . '"';
            $prime_id = (int) $this->objHelper->getValue($sql);
            if ($prime_id != $id) {
                Factory::getApplication()->enqueueMessage('Unable to set' . $list_name . ' as primary list', 'Info');
                return false;
            }
            return true;
        }
    }

    public function showAuditAll() {
        $list_id = (int) $this->objApp->input->getCmd('list_id', '');

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        ToolBarHelper::title('Ramblers MailMan');
//      Show link that allows page to be printed
        $target = "administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditAll&list_id=" . $list_id;
        echo $this->objHelper->showPrint($target);

        $sql = "SELECT date_format(a.created,'%d/%m/%y') as 'Date', ";
        $sql .= "time_format(a.created,'%H:%i') as 'Time', ";
        $sql .= "a.field_name, a.old_value, ";
        $sql .= "a.new_value, a.ip_address, ";
        $sql .= "u.name AS 'UpdatedBy', member.name AS 'Member' ";
        $sql .= "from #__ra_mail_subscriptions_audit AS a ";
        $sql .= "INNER JOIN #__ra_mail_subscriptions AS s ON s.id = a.object_id ";
        $sql .= "INNER JOIN `#__users` AS u ON u.id = a.created_by ";
        $sql .= "INNER JOIN `#__users` AS member ON member.id = s.user_id ";
        $sql .= "where s.list_id=" . $list_id . " ORDER BY a.created DESC";
//        Factory::getApplication()->enqueueMessage('sql=' . $sql, 'notice');
        $rows = $this->objHelper->getRows($sql);
        echo '<h2>Audit records for ' . $this->objMailHelper->getDescription($list_id) . '</h2>';
        $objTable = new ToolsTable;
        $objTable->add_header("Date,Time,Member,Field,Old value,New value,Update by,IP address");

        foreach ($rows as $row) {
            $objTable->add_item($row->Date);
            $objTable->add_item($row->Time);
            $objTable->add_item($row->Member);
            $objTable->add_item($row->field_name);
            $objTable->add_item($row->old_value);
            $objTable->add_item($row->new_value);
            $objTable->add_item($row->UpdatedBy);
            $objTable->add_item($row->ip_address);

            $objTable->generate_line();
        }
        $objTable->generate_table();
        $target = 'administrator/index.php?option=com_ra_mailman&view=mail_lsts';
        echo $this->objHelper->backButton($target);
    }

    public function showAuditSingle() {
// Shows audit details for given subscription
// First check user is a Super-User
        if (!$this->objHelper->isSuperuser()) {
            Factory::getApplication()->enqueueMessage('Invalid access', 'notice');
            $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
            $this->setRedirect(Route::_($target, false));
        }
        $id = (int) $this->objApp->input->getCmd('id', '');
        $list_id = (int) $this->objApp->input->getCmd('list_id', '');   // ? is this used?
        $user_id = (int) $this->objApp->input->getCmd('user_id', '');
//      Show link that allows page to be printed
        $target = "administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditSingle&list_id=" . $list_id;
        echo $this->objHelper->showPrint($target);

        echo '<h3>Updates </h3>';
        echo "<h4>List: " . $this->objMailHelper->getDescription($list_id) . '</h4>';
        $sql = 'SELECT name from `#__users` WHERE id=' . $user_id;
        $username = $this->objHelper->getValue($sql);
        echo "User: " . $username . '<br>';

//        $sql = 'SELECT name from `#__users` WHERE id=' . $user_id;
//        $row = $this->objHelper->getItem($sql);
//        echo "<h4>Audit for " . $row->name . '</h4>';
        $sql = "SELECT date_format(a.created,'%d/%m/%y') as 'Date', ";
        $sql .= "time_format(a.created,'%H:%i') as 'Time', ";
        $sql .= "l.group_code AS 'Group', l.name AS 'List', ";
        $sql .= "a.field_name, a.old_value, a.new_value, ";
        $sql .= "s.user_id, ";
        $sql .= "a.ip_address ";
        $sql .= 'FROM #__ra_mail_subscriptions_audit as a ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions as s on s.id = a.object_id ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` as l on l.id = s.list_id ';
        $sql .= 'WHERE s.id=' . $id;
        $sql .= ' ORDER BY a.created DESC';
//        echo $sql;
        $rows = $this->objHelper->getRows($sql);
        $objTable = new ToolsTable;
        $objTable->add_header("Date,Time,Group,List,Field,Old value,New value,IP address"); // Update by,

        foreach ($rows as $row) {
            $objTable->add_item($row->Date);
            $objTable->add_item($row->Time);
            $objTable->add_item($row->Group);
            $objTable->add_item($row->List);
            $objTable->add_item($row->field_name);
            $objTable->add_item($row->old_value);
            $objTable->add_item($row->new_value);
//            $objTable->add_item($row->UpdatedBy);
            $objTable->add_item($row->ip_address);

            $objTable->generate_line();
        }
        $objTable->generate_table();

//        echo $this->objHelper->rows . ' Lists<br>';
//
        $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showLists';
        $target .= '&user_id=' . $user_id . '&list_id=' . $list_id;
        echo $this->objHelper->backButton($target);
    }

    public function showAuthors() {
        $list_id = (int) $this->objApp->input->getCmd('list_id', '');

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        ToolBarHelper::title('Ramblers MailMan');
//      Show link that allows page to be printed
        $target = "administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuthors&list_id=" . $list_id;
        echo $this->objHelper->showPrint($target);

        $description = $this->objMailHelper->getDescription($list_id);
        $sql_count = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions_audit WHERE object_id=';

        echo '<h2>Authors for ' . $this->objMailHelper->getDescription($list_id) . '</h2>';
        $sql = "SELECT ";
        $sql .= "u.id, u.name AS 'User', u.email, ";
        $sql .= "p.home_group, ";
        $sql .= "m.name as 'Method', ";
        $sql .= "s.id AS subscription_id, s.modified, ";
        $sql .= "modifier.name AS UpdatedBy, ";
        $sql .= "s.expiry_date, s.created, s.ip_address, s.state, ";
        $sql .= "CASE WHEN s.state =1 THEN 'Active' ELSE 'Inactive' END AS 'Status' ";
        $sql .= 'FROM `#__users` as u ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s on u.id = s.user_id ';
        $sql .= 'INNER JOIN #__ra_mail_methods AS m on m.id = s.method_id ';
        $sql .= 'LEFT JOIN `#__users` AS modifier on modifier.id = s.modified_by ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p on p.id = u.id ';
        $sql .= 'WHERE s.list_id=' . $list_id;
        $sql .= ' AND record_type=2';
        $sql .= ' ORDER BY s.state DESC, u.name';
//        echo $sql;
        $rows = $this->objHelper->getRows($sql);

        $objTable = new ToolsTable;
        $objTable->add_header("User,email,Group,Method,Created,Last Updated,Updated by,Expires,IP address,Status");
        $count_active = 0;
        $count_inactive = 0;
        foreach ($rows as $row) {
//            $objTable->add_item($row->id);
            $objTable->add_item($row->User);
            $objTable->add_item($row->email);
            $objTable->add_item($row->home_group);
            $objTable->add_item($row->Method);
            $objTable->add_item($row->created);
            $count = $this->objHelper->getValue($sql_count . $row->id);
            if ($count == 0) {
                $objTable->add_item($row->modified);
            } else {
                $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditSingle&id=' . $row->subscription_id;
                $target .= '&list_id=' . $list_id;
                $objTable->add_item($this->objHelper->buildLink($target, $row->modified));
            }
            $objTable->add_item($row->UpdatedBy);
            $objTable->add_item($row->expiry_date);
            $objTable->add_item($row->ip_address);
            $objTable->add_item($row->Status);
            if ($row->state == 1) {
                $count_active++;
            } else {
                $count_inactive++;
            }
            $objTable->generate_line();
        }
        $objTable->generate_table();
        echo $count_active . ' Active Authors';
        if ($count_inactive > 0) {
            echo ' (plus ' . $count_inactive . ' Inactive)';
        }
        echo '<br>';

        $target = 'administrator/index.php?option=com_ra_mailman&view=mail_lsts';
        echo $this->objHelper->backButton($target);
    }

    public function showLists() {
        // shows all mail-list for the given User
        //
        // First check user is a Super-User
        if (!$this->objHelper->isSuperuser()) {
            Factory::getApplication()->enqueueMessage('Invalid access', 'notice');
            $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
            $this->setRedirect(JRoute::_($target, false));
        }

        ToolBarHelper::title('Ramblers MailMan');
//   this line generates a button, but it does not work
//        ToolBarHelper::custom('mail_lsts.display', 'download.png', 'download.png', 'Back', false);


        $user_id = (int) $this->objApp->input->getCmd('user_id', '');
        $list_id = (int) $this->objApp->input->getCmd('list_id', '');
//      Show link that allows page to be printed
        $target = "administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditSingle";
        $target .= '&list_id=' . $list_id . '&user_id=' . $user_id;
        echo $this->objHelper->showPrint($target);

        $sql = 'SELECT name from `#__users` WHERE id=' . $user_id;
        $row = $this->objHelper->getItem($sql);
        echo "<h4>Lists for " . $row->name . '</h4>';
        $sql = "SELECT ";
        $sql .= "a.id, a.modified, a.ip_address,";
        $sql .= "l.group_code AS 'Group', l.name AS 'List', ";
        $sql .= 'CASE WHEN a.record_type =2 THEN "Author" ELSE "Subscriber" END as "Type", ';
        $sql .= "m.name as 'Method',";

        $sql .= "CASE WHEN a.state =1 THEN 'Active' ELSE 'Lapsed' END as 'Status' ";
        $sql .= 'FROM #__ra_mail_subscriptions AS a ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = a.list_id ';
        $sql .= 'INNER JOIN #__ra_mail_methods AS m ON m.id = a.method_id ';
        $sql .= 'WHERE a.user_id=' . $user_id;
        $sql .= ' ORDER BY l.group_code, l.name, Status';
//        echo $sql;
        $rows = $this->objHelper->getRows($sql);

        $objTable = new ToolsTable;
        $objTable->add_header("Group,List, Type,Method,Last updated,IP address,Status,id,Token");

        foreach ($rows as $row) {
            $objTable->add_item($row->Group);
            $objTable->add_item($row->List);
            $objTable->add_item($row->Type);
            $objTable->add_item($row->Method);
            if ($row->modified == '') {
                $objTable->add_item('');
            } else {
                $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditSingle';
                $target .= '&list_id=' . $list_id . '&user_id=' . $user_id . '&id=' . $row->id;
                $objTable->add_item($this->objHelper->buildLink($target, $row->modified));
            }
            $objTable->add_item($row->ip_address);
            $objTable->add_item($row->Status);
            $objTable->add_item($row->id);
            $token = $this->objMailHelper->encode($row->id, 0);
            $objTable->add_item($token);
            $objTable->generate_line();
        }
        $objTable->generate_table();

        echo $this->objHelper->rows . ' Lists<br>';
        $target = "administrator/index.php?option=com_ra_mailman&view=subscriptions";
        echo $this->objHelper->backButton($target);
    }

    public function showUserLists() {
        // Show mailing lists for given User or Profile

        $user_id = (int) $this->objApp->input->getCmd('user_id', 0);
        ToolBarHelper::title('Ramblers MailMan');

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.group_code, a.name, s.expiry_date, s.reminder_sent');
        $query->from('#__ra_mail_lists AS a');

        $query->select('u.name AS `owner`');
        $query->leftJoin($db->qn('#__users') . ' AS `u` ON u.id = a.owner_id');

        $query->select('s.state');
        $query->innerJoin($db->qn('#__ra_mail_subscriptions') . ' AS `s` ON s.list_id = a.id');
        $query->where($db->qn('s.user_id') . ' = ' . $user_id);
        $query->order('a.group_code, a.name');
        $mailshots = $this->objHelper->getRows($query);
//        Factory::getApplication()->enqueueMessage('Q=' . $query, 'notice');
        $username = Factory::getUser($user_id)->get('username');
        echo '<h2>Mailing Lists for User ' . $username . " ($user_id)" . '</h2>';
        $objTable = new ToolsTable;
        $objTable->add_header("Group,List,Last sent,Owner,Active,Expires");
        $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showMailshot&id=';
        foreach ($mailshots as $row) {
            $objTable->add_item($row->group_code);
            $objTable->add_item($row->name);
            // Find most recent mailshots user has been sent
            $sql = 'SELECT MAX(date_sent) AS Max ';
            $sql .= 'FROM #__ra_mail_shots AS a ';
            $sql .= 'INNER JOIN `#__ra_mail_lists` as l ON l.id = a.mail_list_id ';
            $sql .= 'INNER JOIN #__ra_mail_subscriptions as s ON s.list_id = l.id ';
            $sql .= 'WHERE s.user_id=' . $user_id;
            $last_sent = $this->objHelper->getValue($sql);
            //$objTable->add_item(Html::_('date', $last_sent, 'd-m-y'));
            $objTable->add_item($last_sent);
//            if ($count > 0) {
//                $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showIndividualMailshots&user_id=' . $user_id;
//                echo '<a>' . $this->objHelper->buildLink($target, $count) . '</a>';
//        }
            $objTable->add_item($row->owner);
            $objTable->add_item($row->state);
            //$details = Html::_('date', $row->expiry_date, 'd-m-y'); //$row->expiry_date;
            $details = $row->expiry_date;
            if (!is_null($row->reminder_sent)) {
                $details .= '<br>' . Html::_('date', $row->reminder_sent, 'd-m-y');
                $details .= '<br>' . $row->reminder_sent;
            }
            $objTable->add_item($details);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        $back = 'administrator/index.php?option=com_ra_mailman&view=profiles';
        echo $this->objHelper->backButton($back);
    }

    public function showSubscribers() {
        // shows all Users for the given mail-list
        // Could also invoke showLists for each User, but this would complicated where @back@ should return to
        //

        $back = "administrator/index.php?option=com_ra_mailman&view=mail_lsts";
        ToolBarHelper::title('Ramblers MailMan');

        // This button does not work!
//       ToolbarHelper::cancel($back, 'Back');
        $list_id = $this->objApp->input->getCmd('list_id', '');
        $author = $this->objApp->input->getCmd('author', 'N');
        $description = $this->objMailHelper->getDescription($list_id);
        $sql_count = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions_audit WHERE object_id=';

//      Show link that allows page to be printed
        $target = "index.php?option=com_ra_mailman&task=mail_lst.showSubscribers&list_id=" . $list_id;
        echo $this->objHelper->showPrint($target);
        echo '<h4>';
        if ($author == 'Y') {
            echo 'Authors';
        } else {
            echo 'Subscribers';
        }
        echo " for " . $description . '</h4>';

        // Check whether to show email addresses
        // $showEmail = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_ra_mailman');
        if ($this->objHelper->isSuperuser()) {
            $showEmail = true;
            $table_headings = 'User,email,Group,Access,Created,Last Updated,IP address,Expires,Status';
        } else {
            $showEmail = false;
            $table_headings = 'User,Group,Access,Created,Last Updated';
        }

        $sql = "SELECT ";
        $sql .= "u.id, u.name AS 'User', u.email, ";
        $sql .= "p.home_group, ";
        $sql .= "m.name as 'Method', ma.name as Type,";
        $sql .= "s.id AS subscription_id, s.modified, ";
        $sql .= "modifier.name AS UpdatedBy, ";
        $sql .= "s.expiry_date, s.created, s.ip_address, s.state, ";
        $sql .= "CASE WHEN s.state =1 THEN 'Active' ELSE 'Inactive' END AS 'Status' ";
        $sql .= 'FROM `#__users` as u ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s on u.id = s.user_id ';
        $sql .= 'INNER JOIN #__ra_mail_methods AS m on m.id = s.method_id ';
        $sql .= 'LEFT JOIN `#__users` AS modifier on modifier.id = s.modified_by ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p on p.id = u.id ';
        $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
        $sql .= 'WHERE u.block=0 ';
        if ($author == 'Y') {
            $sql .= ' AND s.record_type=2 ';
        }
        $sql .= 'AND s.list_id=' . $list_id;
        $sql .= ' ORDER BY s.state DESC, u.name';
        //        echo $sql;
        $rows = $this->objHelper->getRows($sql);

        $objTable = new ToolsTable;
        $objTable->add_header($table_headings);
        $count_active = 0;
        $count_inactive = 0;
        foreach ($rows as $row) {
            //            $objTable->add_item($row->id);
            $objTable->add_item($row->User);
            if ($showEmail) {
                $objTable->add_item($row->email);
            }
            $objTable->add_item($row->home_group);
            $objTable->add_item($row->Type . '<br>' . $row->Method);
            $objTable->add_item($row->created);
            $details = '';
            // See if audit records exist
            $count = $this->objHelper->getValue($sql_count . $row->id);
            if ($count == 0) {
                $details = $row->modified;
            } else {
                $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditSingle&id=' . $row->subscription_id;
                $target .= '&list_id=' . $list_id;
                $details = $this->objHelper->buildLink($target, $row->modified);
            }
            if ($row->UpdatedBy != '') {
                $details .= '<br>' . $row->UpdatedBy;
            }
            $objTable->add_item($details);
            if ($showEmail) {
                $objTable->add_item($row->ip_address);
                $objTable->add_item($row->expiry_date);
                $objTable->add_item($row->Status);
            }
            if ($row->state == 1) {
                $count_active++;
            } else {
                $count_inactive++;
            }
            $objTable->generate_line();
        }
        $objTable->generate_table();
        echo $count_active . ' Active Subscribers';
        if ($count_inactive > 0) {
            echo ' (plus ' . $count_inactive . ' Inactive)';
        }
        echo '<br>';

        echo $this->objHelper->backButton($back);
    }

    public function subscribe() {
        $list_id = $this->objApp->input->getInt('list_id', '');
        $record_type = $this->objApp->input->getInt('record_type', '');
        $user_id = $this->objApp->input->getInt('user_id', '');
        $callback = $this->objApp->input->getCmd('callback', 'user_select');
//        Factory::getApplication()->enqueueMessage("Updating list=$list_id, user=$user_id, record_type=$record_type", 'notice');
        $this->objMailHelper->subscribe($list_id, $user_id, $record_type, 2);
        Factory::getApplication()->enqueueMessage($this->objMailHelper->message, 'notice');
        $target = 'index.php?option=com_ra_mailman&view=' . $callback;
        $target .= '&record_type=' . $record_type . '&list_id=' . $list_id;
        if ($callback == 'list_select') {
            $target .= '&user_id=' . $user_id;
        }
        $this->setRedirect(Route::_($target, false));
    }

    public function unsubscribe() {
        $list_id = (int) $this->objApp->input->getCmd('list_id', '');
        $record_type = (int) $this->objApp->input->getCmd('record_type', '');
        $user_id = (int) $this->objApp->input->getCmd('user_id', '');
        $callback = $this->objApp->input->getCmd('callback', 'user_select');
        $result = $this->objMailHelper->unsubscribe($list_id, $user_id, 2);
        Factory::getApplication()->enqueueMessage($this->objMailHelper->message, 'notice');
        $target = 'index.php?option=com_ra_mailman&view=' . $callback;
        $target .= '&record_type=' . $record_type . '&list_id=' . $list_id;
        if ($callback == 'list_select') {
            $target .= '&user_id=' . $user_id;
        }
        $this->setRedirect(Route::_($target, false));
    }

}
