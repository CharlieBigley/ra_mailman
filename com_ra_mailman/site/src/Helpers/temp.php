<?php

/**
 * Contains functions used in the back end and the front end
 *
 * @author charles
 * Created Feb 2022
 * This contains generic code to Add/Delete/Get data or Update an Entity
 * (in this case a Subscription).
 * 13/12/21 CB Created using a code generator
 * 15/01/22 CB changed comparison of fields: for some unknown reason needed "else" when comparing old and new values
 * 22/04/22 CB correction for current_user
 * 19/05/22 CB Don't user SERVER for ip_address
 * 03/06/22 CB include JFactory
 * 20/07/22 CB use current_ip_address when updating records
 * 21/06/23 CB always create expiry date
 * 03/09/23 CB always update expiry date when renewing subscription
 */

namespace Ramblers\Component\Ra_mailman\Site\Helpers;

use Joomla\CMS\Factory;
//use Joomla\Date\Date;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class SubscriptionHelper {

    protected $current_user;
    public $fields_modified;
    public $action;
    protected $current_ip_address;
    public $message;
// database fields
    public $id;
    public $list_id;
    public $user_id;
    public $record_type;
    public $method_id;
    public $state;
    public $ip_address;
    public $created;
    public $created_by;
    public $modified;
    public $modified_by;
    public $expiry_date;

    function __construct() {
        $this->id = 0;
        $this->list_id = 0;
        $this->user_id = 0;
        $this->record_type = 0;
        $this->method_id = 0;
        $this->state = 0;
        $this->modified = 0;
        $this->created_by = 0;
        $this->modified_by = 0;
        $this->action = 'Failed';
        $this->current_user = Factory::getUser()->id;
        $this->message = '';
        $this->current_ip_address = Factory::getApplication()->input->server->get('REMOTE_ADDR', '');
    }

    function add() {
        $date = Factory::getDate();
        $db = Factory::getDbo();

// Create a new query object.
        $query = $db->getQuery(true);
// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__ra_mail_subscriptions'))
                ->set('list_id =' . $db->quote($this->list_id))
                ->set('user_id =' . $db->quote($this->user_id))
                ->set('record_type =' . $db->quote($this->record_type))
                ->set('method_id =' . $db->quote($this->method_id))
                ->set('state =' . $db->quote($this->state))
                ->set('ip_address =' . $db->quote($this->current_ip_address))
                ->set('created =' . $db->quote($date->toSQL()))
                ->set('created_by =' . $db->quote($this->current_user));
        // If addding from MailChimp or CSV file, expire after 1 year
//        if (($this->method_id == 4) or ($this->method_id == 5)) {
        $date = Factory::getDate('now', Factory::getConfig()->get('offset'));
        $date->modify('+1 year');

        $query->set('expiry_date=' . $db->quote($date->toSql(true)));
//        }
//        die($date->toSql(true));
// Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage($query, 'notice');
        }
        $this->id = $db->insertid();
        if ($this->id == 0) {
            return 0;
        }
        $this->createAudit('Record', '', 'created');
        $this->message = "Record created";
        $this->action = 'subscribed to';
        return 1;
    }

    function createAudit($field_name, $old_value, $new_value) {
        $this->message .= ", updating " . $field_name;
        $this->fields_updated++;
//            $this->createAuditRecord($field_name, $old_value, $new_value, $this->id, "#__ra_mail_subscriptions");
        $db = Factory::getDbo();

// Create a new query object.
        $query = $db->getQuery(true);
// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__ra_mail_subscriptions_audit'))
                ->set('object_id =' . $db->quote($this->id))
                ->set('field_name =' . $db->quote($field_name))
                ->set('old_value =' . $db->quote($old_value))
                ->set('new_value =' . $db->quote($new_value))
                ->set('ip_address =' . $db->quote($this->current_ip_address))
//                ->set('created =' . $db->quote($date->toSQL()))
                ->set('created_by =' . $db->quote($this->current_user));
        //             Factory::getApplication()->enqueueMessage($this->_db->replacePrefix($query), 'message');
//        echo $db->replacePrefix($query) . '<br>';

        $db->setQuery($query);
        $db->execute();
    }

    function delete() {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__ra_mail_subscriptions_audit'));
        $query->where('object_id=' . $this->id);
        $db->setQuery($query);
        if ($db->execute()) {
            $query->delete($db->quoteName('#__ra_mail_subscriptions'));
            $query->where('id=' . $this->id);
            try {
                $db->setQuery($query);
                $result = $db->execute();
            } catch (Exception $ex) {
                $this->error = $ex->getCode() . ' ' . $ex->getMessage();
            }
        }
        return $result;
    }

    function getData() {
        if ($this->list_id == 0) {
            $this->message = "clssubscribersGetData - list_id is zero";
            return 0;
        }
        if ($this->user_id == 0) {
            $this->message = "clssubscribersGetData - user_id is zero";
            return 0;
        }
        $this->fields_updated = 0;
        $date = Factory::getDate();
        $user = Factory::getUser();
        $db = Factory::getDbo();

        $query = $db->getQuery(true);

        $query->select('id, list_id, user_id, record_type, method_id');
        $query->select('state, ip_address');
        $query->select('created, created_by, modified, modified_by,expiry_date');
        $query->from('`#__ra_mail_subscriptions`');
        $query->where($db->qn('list_id') . ' = ' . $db->q($this->list_id));
        $query->where($db->qn('user_id') . ' = ' . $db->q($this->user_id));
        $db->setQuery($query);
//        echo $query;
        $row = $db->loadObject();
        if ($row) {
            $this->id = $row->id;
            $this->user_id = $row->user_id;
            $this->record_type = $row->record_type;
            $this->method_id = $row->method_id;
            $this->state = $row->state;
            $this->ip_address = $row->ip_address;
            $this->created = $row->created;
            $this->created_by = $row->created_by;
            $this->modified = $row->modified;
            $this->modified_by = $row->modified_by;
            $this->expiry_date = $row->expiry_date;
            return 1;
        } else {
            $this->message = 'Database error on get';
            return 0;
        }
    }

    function update() {
        $fields = array();    // List of fields requiring update
        $this->fields_updated = 0;
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // find existing field values in the database
        $query->select('*');
        $query->from('`#__ra_mail_subscriptions`');
        $query->where($db->qn('list_id') . ' = ' . $db->q($this->list_id));
        $query->where($db->qn('user_id') . ' = ' . $db->q($this->user_id));
        $db->setQuery($query);
        $row = $db->loadObject();
        if (!$row) {
            $this->message = "Can't find record for " . $this->list_id . '/' . $this->user_id;
            return 0;
        }
//        if (($this->method_id == 4) or ($this->method_id == 5)) {
        $currentDate = date("Y-m-d");
        $expiry_date = strtotime(date("Y-m-d", strtotime($currentDate)) . " +1 year");
        $fields[] = $db->quoteName('expiry_date') . '=' . $db->quote($expiry_date);
//        }
// Must create audit records before record itself is updated,
// otherwise original value will be lost
// values in $row come from the existing data, $this->xx are the new values

        if ($row->record_type == $this->record_type) {

        } else {
            $fields[] = $db->quoteName('record_type') . '=' . $db->quote($this->record_type);
            $this->createAudit("record_type", $row->record_type, $this->record_type);
            if ($this->record_type == 2) {
                $this->action = 'granted Authorship';
            } elseif ($this->record_type == 1) {
                $this->action = 'made subscriber to';
            }
        }
        if ($row->method_id == $this->method_id) {

        } else {
            $fields[] = $db->quoteName('method_id') . '=' . $db->quote($this->method_id);
            $this->createAudit("method_id", $row->method_id, $this->method_id);
        }
        if ($row->state == $this->state) {

        } else {
            $fields[] = $db->quoteName('state') . '=' . $db->quote($this->state);
            $this->createAudit("state", $row->state, $this->state);
            if ($row->state == 0) {    // i.e changing state to 1
                $this->action = 're-subscribed';
            } else {                   // i.e currently state = 1
                $this->action = 'cancelled from';
            }
        }
        if ($row->ip_address == $this->ip_address) {

        } else {
            $fields[] = $db->quoteName('ip_address') . ' = ' . $db->quote($this->ip_address);
            $this->createAudit("ip_address", $row->ip_address, $this->ip_address);
        }
        if ($row->modified_by == $this->current_user) {

        } else {
            $fields[] = $db->quoteName('modified_by') . ' = ' . $db->quote($this->current_user);
            $this->createAudit("modified_by", $row->modified_by, $this->current_user);
        }
//        if ($row->expiry_date != $this->expiry_date) {
//            $this->createAudit("expiry_date", $row->expiry_date, $this->expiry_date);
//        }
//        $dateTimeNow = new DateTime('NOW');
//        $modified = $dateTimeNow->format('Y-m-d H:i:s');
        $modified = Factory::getDate()->toSql();
        $fields[] = $db->quoteName('modified') . ' = ' . $db->quote($modified);        // $db->quote($dateTimeNow->toSQL()))
        $query = $db->getQuery(true);

// Most of the fields to update will by now have been set

        if (($this->method_id == 4) or ($this->method_id == 5)) {
            $fields[] = $db->quoteName('expiry_date') . ' = ' . $db->quote($expiry_date);
//            } else {
            // If you would like to store NULL value, you should specify that.
//             $fields[] = $db->quoteName('expiry_date') . ' = NULL';
// Conditions for which records should be updated.
        }
        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($this->id)
        );

        $query->update($db->quoteName('#__ra_mail_subscriptions'))->set($fields)->where($conditions);
        $db->setQuery($query);
//        $this->message .= 'sql=' . (string) $query;
        if ($db->execute()) {
            $this->message .= "Record updated";
            return 1;
        } else {
            $this->message .= $query->toSql();
            $this->action = 'update failed';
        }
        return 0;
    }

    function set_list_id($newValue) {
        $this->list_id = (int) $newValue; // ensure value is numeric
    }

    function set_user_id($newValue) {
        $this->user_id = (int) $newValue; // ensure value is numeric
    }

    function set_record_type($newValue) {
        $this->record_type = (int) $newValue; // ensure value is numeric
    }

    function set_method_id($newValue) {
        $this->method_id = (int) $newValue; // ensure value is numeric
    }

    function set_state($newValue) {
        $this->state = (int) $newValue; // ensure value is numeric
    }

    function set_ip_address($newValue) {
        $this->ip_address = trim(substr($newValue, 0, 200));
    }

    function set_expiry_date($newValue) {
        $this->expiry_date = $newValue;
    }

}
