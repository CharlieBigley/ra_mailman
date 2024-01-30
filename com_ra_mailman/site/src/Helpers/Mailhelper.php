<?php

/**
 * Contains functions used in the back end and the front end
 *
 * @version    4.0.11
 * @package    com_ra_mailman
 * @author     charles
 * Created November 2021
 * 10/05/22 CB renamed from mail.php / MailHelper
 * 22/04/23 CB sendDraft
 * 01/03/23 CB don't included blocked users as subscribers
 * 14/06/23 CB don't use DateTime (use Factory->getDate() instead)
 * 07/09/23 CB move showAudit to SubscriptionsController
 * 10/09/23 CB check for null attachment
 * 13/09/23 CB copy sendDraft and subscribe from Joomla 3 version
 * 16/06/23 CB further changes to messages about attachments from Joomla 3
 * 17/10/23 CB Grant authorship to all subscribers to a chatlist
 * 14/11/23 CB default blank attachment for SendEmail
 * 10/12/23 CB createProfile
 * 01/10/24 CB correction for email body
 * 06/01/24 CB check for valid subscription
 * 28/01/24 CB get email details from component parameters in batch
 */

namespace Ramblers\Component\Ra_mailman\Site\Helpers;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class Mailhelper {

    public $message;
    protected $db;
    protected $attachment;
    protected $email_title;
    protected $objApp;
    protected $objHelper;
    protected $query;
    protected $user_id;

    public function __construct() {
        $this->db = Factory::getDbo();
        $this->objHelper = new ToolsHelper;
        $this->objApp = Factory::getApplication();
        $this->user_id = Factory::getUser()->id;
    }

    public function buildMessage($mailshot_id) {
        /*
         * called by send to compile the final message:
         * 1. system header, from config options
         * 2. Name of the mailing list
         * 3. Actual body of the message as entered by the author
         * 4. Name of the member who last edited the list, plus the name of othe owner if defferent
         * 5. The footer defined for the list, followed by the email address of the list owner
         * 6. The system footer, from config options
         */
//
// Get details of the Mailing list
        $sql = "SELECT "
                . "m.title, m.body, m.attachment, "
                . "m.created, m.modified, m.modified_by, m.date_sent, "
                . "l.name, l.owner_id, l.footer, owner.email AS 'reply_to',"
                . "owner.name AS 'Owner', modifier.name as 'Modifier', creator.name as 'Creator' ";
        $sql .= 'FROM #__ra_mail_shots AS m ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = m.mail_list_id ';
        $sql .= 'LEFT JOIN #__users AS owner ON owner.id = l.owner_id ';
        $sql .= 'LEFT JOIN #__users AS modifier ON modifier.id = m.modified_by ';
        $sql .= 'LEFT JOIN #__users AS creator ON creator.id = m.created_by ';

        $sql .= 'WHERE m.id=' . $mailshot_id;

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        $db->execute();
        $item = $db->loadObject();
        if ((is_null($item->modified_by) OR ($item->modified_by == 0))) {
            $date = HTMLHelper::_('date', $item->created, 'd M y');
            $signatory = $item->Creator;
        } else {
            $date = HTMLHelper::_('date', $item->modified, 'd M y');
            $signatory = $item->Modifier;
        }

// Save the title for the email (used in $this->send)
        $this->email_title = $item->title;

        $params = ComponentHelper::getParams('com_ra_mailman');
        $header = '<i>' . $params->get('email_header') . '</i>';

// Footer comprises the footer from the list, plus the owners email address, plus the component footer
        $footer = $item->footer . ' ' . $item->reply_to;
        $footer .= '<br>' . $params->get('email_footer');

// Save any attachment for the email (used in $this->send)
        if ($item->attachment != '') {
            $mailshot_body .= 'Attachment ' . $item->attachment . '<br>';
            //$file = PATH_COMPONENT_ADMINISTRATOR . '/images/com_ra_mailman/' . $item->attachment;
            $file = '/images/com_ra_mailman/' . $item->attachment;
            if (file_exists($file)) {
                $this->attachment = $file;
            } else {
                $mailshot_body .= 'File not found<br>';
                $this->message .= $file . ' not found';
            }
        }

        $mailshot_body = $header . '<br>' . 'Mailing List: <b>' . $item->name . ' ' . $item->date_sent . '</b><br>';
        $mailshot_body .= $item->body;
        $mailshot_body .= 'From ';
        if ($signatory == $item->Owner) {
            $mailshot_body .= $item->Owner;
        } else {
            $mailshot_body .= $signatory . ' on behalf of ' . $item->Owner;
        }
        $mailshot_body .= ' ' . $date;
        $mailshot_body .= '<br>';
        $mailshot_body .= $footer . '<br>';
        $mailshot_body .= 'To unsubscribe from future emails, click ';
        return $mailshot_body;
    }

    function canDo($option = 'core.manage') {
// Checks that the current user is authorised for the given option

        if (Factory::getUser()->authorise($option, 'com_ra_mailman')) {
            return true;
        } else {
            return false;
        }
    }

    function countMailshots($list_id, $unsent = False) {
        $sql = 'SELECT COUNT(id) FROM `#__ra_mail_shots` ';
        $sql .= 'WHERE mail_list_id=' . $list_id;
        if ($unsent) {
            $sql .= ' AND date_sent IS NOT NULL';
        }
        return $this->objHelper->getValue($sql);
    }

    function countSubscribers($list_id, $author = 'N') {
        // Returns number of active subscribers for given mail list
        $sql = 'SELECT COUNT(*) ';
        $sql .= 'FROM  `#__ra_mail_lists` AS l ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s ON s.list_id = l.id ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = s.user_id ';
        $sql .= 'WHERE s.state=1 AND u.block=0 AND l.id=' . $list_id;
        if ($author == 'Y') {
            $sql .= ' AND s.record_type=2';
        }
        return $this->objHelper->getValue($sql);
    }

    function createRecipent($mailshot_id, $user_id) {
        if ($mailshot_id == 0) {
            Factory::getApplication()->enqueueMessage('Recipient ' . $user_id . ', mailshot id =0', 'comment');
            return false;
        }
        $db = Factory::getDbo();
        $jinput = Factory::getApplication()->input;
        $ip_address = $jinput->server->get('REMOTE_ADDR');
        $current_user = Factory::getUser()->id;
        $email = Factory::getUser($user_id)->email;
        $columns = array('mailshot_id', 'user_id', 'email', 'ip_address', 'created_by');

        $values = array($db->quote($mailshot_id), $db->quote($user_id), $db->quote($email), $db->quote($ip_address), $db->quote($current_user));
//        $date = Factory::getDate();

        $query = $db->getQuery(true);
// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__ra_mail_recipients'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

// Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
        $id = $db->insertid();
        if ($id == 0) {
            return 0;
        }
        return 1;
    }

    function decode($encoded, &$subscription_id, &$mode, $debug = False) {
        /*
         * Takes the string that has been obfuscated by function "encode",
         *  and splits it into constituents
         */
        if ($debug) {
            echo "Mail/decode: " . $encoded . "<br>";
        }
        $temp = $encoded;
        $temp = strrev(substr($temp, 0, strlen($temp) - 1));

        $token = "";
        for ($i = 0; $i < strlen($temp); $i++) {
            $char = substr($temp, $i, 1);
            $token .= (hexdec($char) - 6);
        }
        if ($debug) {
            echo "After decoding: " . $token . "<br>";
        }
//      Split the string into constituents:
        $mode = substr($token, 6, 1);
        $temp = substr($token, 7);
        if ($debug) {
            echo "Mode " . $mode . ", temp " . $temp . "<br>";
        }
        $length_id = (int) substr($temp, 0, 2);
        $subscription_id = substr($temp, 2, $length_id);

        if ($debug) {
            echo "Length " . $length_id . ', temp=' . $temp . ", parts " . $mode . "-" . $subscription_id . "<br>";
        }
        $subscription_id = $subscription_id / 7;
    }

    public function encode($subscription_id, $mode) {
        /*
          Generates a token so that a User can subscribe/Cancel their subscription
          without having to log on. This token can safely be embedded into an email
          $subscription id points to the record in ra_mail_subscribers
          mode = 0 Cancel or 1 Subscribe

          The first six characters are a random number, this is followed by the mode,
          then 7 times the subscription_id.  Since the length of the id field is unpredictable,
          it is preceded by a two character length

          The string thus generated is obfuscated in two stages by processing each digit in turn,
          firstly by adding 6, then changing its representation to Hexadecimal Thus 0123789 would become 678def

          Finally the whole string is reversed

         */
        $debug = 0;
// generate a random 6 character number
        $part1 = mt_rand(100000, 999999);
        $part2 = $mode;

        $id = (string) (7 * $subscription_id);
        $length = strlen($id);
        $part3 = sprintf("%02d", $length);
        if ($debug) {
            echo "subscription id = $subscription_id, length $length, parts:" . $part1 . "-" . $part2 . "-" . $part3 . $id . "<br>";
        }
        $temp = $part1 . $part2 . $part3 . $id;
        if ($debug) {
            echo "2: token before coding: " . $temp . "<br>";
        }
        $token = "";
        for ($i = 0; $i < strlen($temp); $i++) {
//            echo $i . substr($encoded, $i, 1) . " " . dechex(substr($encoded, $i, 1) + 6) . "<br>";
            $token .= dechex(substr($temp, $i, 1) + 6);
        }
        if ($debug) {
            echo "3: token:" . $token . "<br>";
        }

        return strrev($token) . "M";
    }

    public function getDescription($list_id) {
        $sql = 'SELECT group_code, name, record_type, state FROM `#__ra_mail_lists` WHERE id=' . $list_id;
        $row = $this->objHelper->getItem($sql);
        if (is_null($row)) {
            return false;
        } else {
            $description = $row->group_code . ' ' . $row->name . ' ';
            if ($row->state == 0) {
                $description .= '(Inactive)';
            } else {
                if ($row->record_type == 'O') {
                    $description .= '(Open)';
                } else {
                    $description .= '(Closed)';
                }
            }
            return $description;
        }
    }

    public function getHome_group() {
// Returms to home group of the current User
// use Joomla\CMS\Factory;
        $user_id = Factory::getUser()->id;
        if ($user_id == 0) {
            Factory::getApplication()->enqueueMessage("You are not logged in", 'error');
            return false;
        }
        $sql = 'SELECT home_group FROM #__ra_profiles WHERE id=' . $user_id;
        return $this->objHelper->getValue($sql);
    }

    public function getOwner_id($list_id) {
        $sql = 'SELECT owner_id FROM `#__ra_mail_lists` WHERE id=' . $list_id;
        $row = $this->objHelper->getItem($sql);
        if (is_null($row)) {
            return 0;
        } else {
            return $row->owner_id;
        }
    }

    public function getSubscription($list_id, $user_id) {
// Returns the record in the subscription table for the given
// list_id and user_id
        $sql = "SELECT s.id, s.record_type, m.name as 'Method', s.state, ma.name as Access ";
        $sql .= "FROM #__ra_mail_subscriptions  AS s ";
        $sql .= "LEFT JOIN  #__ra_mail_methods as m ON m.id = s.method_id ";
        $sql .= 'LEFT JOIN `#__ra_mail_access` AS `ma` ON ma.id = s.record_type ';
        $sql .= 'WHERE s.list_id=' . $list_id;
        $sql .= ' AND s.user_id=' . $user_id;
        $row = $this->objHelper->getItem($sql);
//        echo $sql . '<br>';
        if (is_null($row)) {
            return false;
        } else {
            return $row;
        }
    }

    private function getSubscribers($mailshot_id, $restart = 'N') {
//        $this->message .= 'getSubscribers mailshot_id=' . $mailshot_id . ', ';
// returns an array of users currently subscribed to the given list
        $sql = "SELECT s.id AS subscription_id, l.id AS list_id, ";
        $sql .= "u.id as user_id, u.name AS 'User', u.email AS 'email' ";
        $sql .= 'FROM #__ra_mail_shots AS m ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = m.mail_list_id ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s ON s.list_id = l.id ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = s.user_id ';
        if ($restart == 'N') {
            $sql .= 'WHERE ';
        } else {
            $sql .= 'LEFT JOIN #__ra_mail_recipients AS mr ON mr.mailshot_id =m.id ';
            $sql .= 'AND u.id = mr.user_id ';
            $sql .= 'WHERE mr.id IS NULL ';
            $sql .= 'AND ';
        }
        $sql .= 'm.id=' . $mailshot_id;

        $sql .= ' AND s.state=1';
        $sql .= ' AND u.block=0';
        $sql .= ' ORDER BY u.username';

//        echo $sql;
//        $this->objHelper->showSql($sql);
//        die;
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        $db->execute();
        $rows = $db->loadObjectList();
        return $rows;
    }

    public function isAuthor($list_id) {
// check that the current user is either:
//   - owner of the list, or
//   - included in the list of Subscriptions, record type = 2
//   - subscribed, and the list is a "Chat list"
        $user_id = Factory::getUser()->id;
//      See if current user is the owner of this list
        if ($this->getOwner_id($list_id) == $user_id) {
            return true;
        }

//      If a chat list, all subscribers are allowed to send
        if ($this->isChatlist($list_id) == 1) {
            if ($this->isSubscriber($list_id, $user_id) != '') {
                return true;
            }
        }

//     Get the list of all Authors for this list
        $sql = 'SELECT user_id FROM #__ra_mail_subscriptions '
                . 'WHERE list_id=' . $list_id
                . ' AND record_type=2'
                . ' AND state=1';
        $this->db->setQuery($sql);
        $this->db->execute();
        $authors = $this->db->loadObjectList();
//        if (JDEBUG) {
//            print_r($authors);
//        }
        foreach ($authors as $author) {
            if ($user_id == $author->user_id) {
                return true;
            }
        }
        return false;
    }

    public function isChatlist($list_id) {
// check that the current list is a chat_list, where all members can send emails
// it will return (as integer) 0 or 1
        $sql = 'SELECT chat_list FROM `#__ra_mail_lists` ';
        $sql .= 'WHERE id = ' . $list_id;
        return $this->objHelper->getValue($sql);
    }

    public function isSubscriber($list_id, $user_id) {
// check if the given user is a Subscriber, and if so
// what method was used to enrol them
//
//      Get the list of all Active subscribers for this list
        $sql = 'SELECT s.method_id, s.record_type, m.name  as "Method" '
                . 'FROM #__ra_mail_subscriptions AS s '
                . 'INNER JOIN #__ra_mail_methods as m ON m.id = s.method_id '
                . 'INNER JOIN #__users as u ON u.id = s.user_id '
                . 'WHERE s.list_id=' . $list_id
                . ' AND s.user_id=' . $user_id
                . ' AND s.state=1'
                . ' AND u.block=0';
        $this->db->setQuery($sql);
        $this->db->execute();
        $subscriber = $this->db->loadObject();
        if (is_null($subscriber)) {
            return '';
        }
//       return $subscriber->Method;
        switch ($subscriber->method_id) {
            case 1: return $subscriber->Method;   // Self Registered
            case 2:                               // Administrator
                $role = $subscriber->Method . ' (';
                if ($subscriber->record_type == 2) {
                    return $role . 'Author)';
                } else {
                    return $role . 'Subscriber)';
                }
            case 3: return $subscriber->Method;   // Corporate feed
            case 4: return $subscriber->Method;   // Mailchimp
            case 5: return $subscriber->Method;   // CSV
            case 6: return $subscriber->Method;   // Email
            default: return $subscriber->method_id;
        }

        return false;
    }

    public function lastSent($list_id) {
//       For the given list, returns the date the most recent mailshot, or blank
        $query = $this->db->getQuery(true);
        $query->select('MAX(date_sent) AS DateSent');
        $query->from($this->db->qn('#__ra_mail_shots'));
        $query->where('mail_list_id=' . $list_id);
        $this->db->setQuery($query);
        $this->db->execute();
        $item = $this->db->loadObject();
        if (is_null($item)) {
            $this->message .= 'No record found';
            return '';
        } else {
            if (($item->DateSent == '') or ($item->DateSent == '0000-00-00')) {
                $this->message .= 'Found blank with id=' . $list_id;
                return '';
            } else {
                $this->message .= $item->DateSent;
// format date as dd/mm/yyyy
//                return $item->DateSent . '=' . substr($item->DateSent, 8, 2) . '/' . substr($item->DateSent, 5, 2) . '/' . substr($item->DateSent, 0, 4);
                return substr($item->DateSent, 8, 2) . '/' . substr($item->DateSent, 5, 2) . '/' . substr($item->DateSent, 0, 4);
            }
        }
    }

    public function lookupUser($user_id) {
        $sql = 'SELECT preferred_name FROM #__ra_profiles ';
        $sql .= 'WHERE id = ' . $user_id;
        return $this->objHelper->getValue($sql);
    }

    public function outstanding($list_id) {
// For the given list, looks up the most recent mailshot and its date_sent
// If it has not been sent, returns its id
// If it has     been sent, returns zero
        $query = $this->db->getQuery(true);
        $query->select('id, date_sent');
        $query->from($this->db->qn('#__ra_mail_shots'));
        $query->where('mail_list_id=' . $list_id);
        $query->order('id DESC');
        $query->setLimit('1');

        $this->db->setQuery($query);
        $this->db->execute();
        $item = $this->db->loadObject();
        if (is_null($item)) {
            //      Factory::getApplication()->enqueueMessage('Helper/outstanding: mail_list_id=' . $list_id . ' not found', 'notice');
            return 0;
        } else {
//          A record has been found for this list
            $this->message .= 'id=' . $item->id . ', ' . $item->date_sent;
            if ((is_null($item->date_sent)) OR ($item->date_sent == '0000-00-00')) {
                //          Factory::getApplication()->enqueueMessage('Helper/outstanding: mail_list_id=' . $list_id . ' Null', 'notice');
                return $item->id;
            } else {
                return 0;
            }
        }
    }

    public function resubscribe($subscription_id) {
        /*
         * Invoked from view Subscriptions
         */

        $sql = 'SELECT state, list_id, record_type, user_id FROM #__ra_mail_subscriptions ';
        $sql .= 'WHERE id=' . $subscription_id;
        $item = $this->objHelper->getItem($sql);
        if ($item->state == 0) {
            $this->subscribe($item->list_id, $item->user_id, $item->record_type, 2);
        } else {
            $this->unsubscribe($item->list_id, $item->user_id, 2);
        }
    }

    public function send($mailshot_id) {
// UPDATE `dem_ra_mail_shots` set date_sent = NULL WHERE id=
//
//      Set up maximum time of 10 mins (should be parameter in config
        $max = 10 * 60;
        set_time_limit($max);
// Compile the final message from its components
        $mailshot_body = $this->buildMessage($mailshot_id);
// Find the email address of the list's owner
        $sql = 'SELECT u.email FROM #__ra_mail_shots AS ms ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = ms.mail_list_id ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = l.owner_id ';
        $sql .= 'WHERE ms.id=' . $mailshot_id;
//        echo "$sql<br>";
        $reply_to = $this->objHelper->getValue($sql);

// See if the send is only part way through
        $sql = 'SELECT processing_started, date_sent, title FROM #__ra_mail_shots ';
        $sql .= 'WHERE id=' . $mailshot_id;
//        echo "$sql<br>";
        $item = $this->objHelper->getItem($sql);
//        echo $item->processing_started . ' ' . $item->date_sent . '<br>';
//       if (is_null($item->date_sent)) {
//           echo $item->date_sent . ' date_sent is null<br>';
//       }
        if (is_null($item->processing_started)) {
            Factory::getApplication()->enqueueMessage('Sending of Mailshot "' . $item->title . '" started at ' . date('d-m-Y h:i:s A'), 'notice');
        } else {
            Factory::getApplication()->enqueueMessage('Sending of Mailshot "' . $item->title . '" restarting ' . $item->processing_started, 'notice');
        }
        if ($item->date_sent > '') {
            Factory::getApplication()->enqueueMessage('Mailshot "' . $item->title . '" was sent ' . $item->date_sent, 'error');
            $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mail_lsts', false));
            return 0;
        }

        if ($item->processing_started > '') {
// Send had started but not completed
            $restart = true;
// Only get users who have not yet received their message
            $subscribers = $this->getSubscribers($mailshot_id, 'Y');
        } else {
// Save the status that processing has started
            if (!$this->updateDate($mailshot_id, 'processing_started')) {
                $this->message .= ', Unable to update ProcessingDate';
                return 0;
            }
            $restart = false;
// Store the final composite message
            if (!$this->storeMessage($mailshot_id, $mailshot_body . 'Un-subscribe')) {
                $this->message .= ', Unable to update final message';
                return 0;
            }
            $subscribers = $this->getSubscribers($mailshot_id);
        }
// Find the reference point for the un-subscribe link
        $base = uri::base();
        if (substr($base, -14, 13) == 'administrator') {
            $target = substr($base, 0, strlen($base) - 14);
        } else {
            $target = $base;
        }
        $error_count = 0;
        $count = 0;

        foreach ($subscribers as $subscriber) {
            $token = $this->encode($subscriber->subscription_id, 0);

            $link = $this->objHelper->buildLink($target . 'index.php?option=com_ra_mailman&task=mail_lst.processEmail&token=' . $token, 'Un-subscribe');
//            if (substr(JPATH_ROOT, 14, 6) == 'joomla') {            // Development
//                $this->message .= ' ' . $token;
//            }
            if ($this->sendEmail($subscriber->email, $reply_to, $this->email_title, $mailshot_body . $link, $this->attachment)) {
                $count++;
            } else {
                $error_count++;
            }
            $this->createRecipent($mailshot_id, $subscriber->user_id);
        }
//        $this->message .= 'Testing ' . $count . ', user=' . $users . ', for mailshot ' . $mailshot_id . $message;
        $this->message .= ' Mailshot ' . $this->email_title . ' sent to ' . $count . ' users ';

        if ($error_count > 0) {
            $this->message .= ' ' . $error_count . ' Errors';
        }
        if ($restart == true) {
            $date_field = '';
        } else {
            $date_field = '';
        }
        if (!$this->updateDate($mailshot_id, 'date_sent')) {
            $this->message .= ', Unable to update DateSent';
            return 0;
        }
        return 1;
    }

    public function sendDraft($mailshot_id) {
//        die('helper sendDraft ' . $mailshot_id);
        // Compile the final message from its components
        $mailshot_body = $this->buildMessage($mailshot_id);

//      Find the email address of the list's owner
        $sql = 'SELECT u.email FROM #__ra_mail_shots AS ms ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = ms.mail_list_id ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = l.owner_id ';
        $sql .= 'WHERE ms.id=' . $mailshot_id;
        $owner_email = $this->objHelper->getValue($sql);

//      Find the email address of the current user
        $user_email = Factory::getUser()->email;


        $title = 'DRAFT MESSAGE: ' . $this->email_title;
        if ($this->attachment == '') {
            $this->message .= '(no attachment) ';
        }

        $count = 0;
        // Send message to the editor of the message
        if ($this->sendEmail($user_email, $owner_email, $title, $mailshot_body . $link, $this->attachment)) {
            $this->message .= ' [' . $this->email_title . '] sent to you at ' . $user_email;
            $count++;
        } else {
            $this->message .= ' Unable to send Draft "' . $this->email_title . '" to ' . $user_email . ' ';
            return 0;
        }
//        die('user email ' . $user_email . '<br>' . $this->message);
//      If current user not the list owner, send another copy to the owner, reply_to = author
        if ($user_email != $owner_email) {
            if ($this->sendEmail($owner_email, $user_email, $title, $mailshot_body . $link, $this->attachment)) {
                $this->message .= ', also sent to the owner at ' . $owner_email;
                $count++;
            } else {
                $this->message .= ' Unable to send ' . $title . ' to ' . $owner_email;
                return 0;
            }
        }
        if ($count > 0) {
            $this->message .= ' ' . $count . ' emails sent';
        }
//        die($this->message);
        return true;
    }

    function sendEmail($to, $reply_to, $subject, $body, $attachment = '') {

        $objMail = Factory::getMailer();
        $config = Factory::getConfig();
        if (is_null($config)) {
            // being run in batch mode
            $params = ComponentHelper::getParams('com_ra_mailman');
            $email_details = $params['email_details'];
            $sender = explode('.', $email_details);
            //           $sender = array(
            //               'webmaster@bigley.me.uk',
            //               'MailMan'
            //           );
            //           $reply_to = 'webmaster@bigley.me.uk';
        } else {
            $sender = array(
                $config->get('mailfrom'),
                $config->get('fromname')
            );

            if ($reply_to == '') {
                $reply_to = $config->get('mailfrom');
            }
        }
        $objMail->setSender($sender);
        $objMail->addRecipient($to);
        $objMail->addReplyTo($reply_to);
        $objMail->isHtml(true);
        $objMail->Encoding = 'base64';
        $objMail->setSubject($subject);
        $objMail->setBody($body);

//          Add embedded image
//          This adds the logo as an attachment, which could then be referenced as cid:xxx)
//            $objMail->AddEmbeddedImage(JPATH_COMPONENT_SITE . '/media/com_ra_mailman/logo.png', 'logo', 'logo.jpg', 'base64', 'image/png');
//           Optional file attached
        if ($attachment != '') {
            $objMail->addAttachment($attachment);
        }
        // debug code for development - echo write to a buffer that is not actually displayed
        if ((substr(JPATH_ROOT, 14, 6) == 'joomla') OR (substr(JPATH_ROOT, 14, 6) == 'MAMP/h')) {  // Development
            $this->message .= $to . ' ';
            echo "To: <b>$to</b><br>";
            echo "Reply_to: <b>$reply_to</b><br>";
            echo "Subject: <b>$subject</b><br>";
            echo $body;
            if ($attachment != '') {
                echo "<br>";
                echo "Attachment: <b>$attachment</b><br>";
            }
//            die('MailHelper');
            return true;
        } else {
            $send = $objMail->Send();

//        if ($send !== true) {
//            $this->logEmail("ME", 'Error sending email: ' . $to);
//        } else {
//            $this->logEmail("MS", $to);
//        }
            return $send;
        }
    }

    public function sendRenewal($subscription_id) {
        /*
         * Invoked from batch program renewals.php
         * parameter identifies a subscription that is about to elapse
         *
         * first check that user_id is still valid (user may have been deleted)
         */
        if ($this->validSubscription($subscription_id) == false) {
            return;
        }
// get$params =   component parameters
        $params = ComponentHelper::getParams('com_ra_mailman');
        $body = '<i>' . $params->get('email_header') . '</i><br>';
        $website_base = rtrim($params->get('website'), '/') . '/';
//        echo 'base ' . $website_base . '<br>';

        $sql = 'SELECT s.user_id, s.list_id, s.expiry_date, ';
        $sql .= 'l.group_code, l.name as "List", u.name AS "Recipient", u.email, ';
        $sql .= 'o.name as "Owner", o.email as reply_to ';
        $sql .= 'FROM `#__ra_mail_lists` AS l ';
        $sql .= 'INNER JOIN `#__ra_mail_subscriptions` AS s ON s.list_id = l.id ';
        $sql .= 'LEFT JOIN `#__users` AS u ON u.id = s.user_id ';
        $sql .= 'LEFT JOIN `#__users` AS o ON o.id = l.owner_id ';
        $sql .= 'WHERE s.id=' . $subscription_id;
        $sql .= ' AND u.block=0';
//        echo $sql . '<br>';
        $item = $this->objHelper->getItem($sql);
        if ($item) {
//        var_dump($item);
            $body .= 'Hi ' . $item->Recipient . '<br>';
            $body .= '<b>List: ' . $item->group_code . ' ' . $item->List . '</b><br>';
            $body .= '<b>Owned by: ' . $item->Owner . '</b><br>';
            //$body .= 'Your subscription to receive emails from MailMan, will expire on ' . HTMLHelper::_('date', $item->expiry_date, 'd-M-Y');
            $body .= 'Your subscription to receive emails from MailMan, will expire on ' . $item->expiry_date;
            $body .= ', so we want to make sure you are still happy to continue.<br>';
            $body .= '<br>';

// Show details of Mailshots for this list
            $sql = 'SELECT m.date_sent, m.title ';
            $sql .= 'FROM #__ra_mail_shots AS m ';
            $sql .= 'INNER JOIN #__ra_mail_recipients AS r ON r.mailshot_id  = m.id ';
            $sql .= ' WHERE m.date_sent IS NOT NULL ';
            $sql .= 'AND m.mail_list_id=' . $item->list_id . ' ';
            $sql .= 'ORDER BY m.date_sent DESC ';
            $sql .= 'LIMIT 6';
            $rows = $this->objHelper->getRows($sql);
            if ($rows) {
                $body .= 'You have been sent these Mailshots:<br>';
                foreach ($rows as $row) {
                    $body .= $row->date_sent . ' ' . $row->title . '<br>';
                }
            }

            $token = $this->encode($subscription_id, 3);   // bump
            $link = $this->objHelper->buildLink($website_base . 'index.php?option=com_ra_mailman&task=mail_lst.processEmail&token=' . $token, 'Renew');
            $body .= 'To renew your subscription for another year please click ' . $link . '<br>';

            $token = $this->encode($subscription_id, 2);
            $link = $this->objHelper->buildLink($website_base . 'index.php?option=com_ra_mailman&task=mail_lst.processEmail&token=' . $token, 'Cancel');
            $body .= 'If you want to cancel your subscription please click ' . $link . '<br>';
        }
// Find if this user is subscribed to other lists
        $sql = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions ';
        $sql .= 'WHERE user_id=' . $item->user_id;
        $sql .= ' AND state=1';
        $count = $this->objHelper->getValue($sql);
//        echo $count . ' ' . $sql . '<br>';
        if ($count > 1) {
            $body .= '<br>';
            $body .= 'You are also subscribed to these list:<br>';
            $sql = 'SELECT  s.list_id, s.expiry_date, ';
            $sql .= 'l.group_code, l.name as "List", o.name as "Owner" ';
            $sql .= 'FROM #__ra_mail_subscriptions AS s ';
            $sql .= 'LEFT JOIN #__ra_mail_lists AS l ON l.id  = s.list_id ';
            $sql .= 'LEFT JOIN #__users AS u ON u.id  = s.user_id ';
            $sql .= 'LEFT JOIN #__users AS o ON o.id = l.owner_id ';
            $sql .= 'WHERE s.user_id=' . $item->user_id;
            $sql .= ' AND s.state=1';
//            echo $sql . '<br>';
            $rows = $this->objHelper->getRows($sql);

            foreach ($rows as $row) {
                if ($row->list_id <> $item->list_id) {
                    $body .= $row->group_code . ' ' . $row->List;
                    if (!is_null($row->expiry_date)) {
//                        $body .= ' (expires ' . HTMLHelper::_('date', $row->expiry_date, 'd-M-y') . ')';
                        $body .= ' (expires ' . $row->expiry_date . ')';
                    }
                    $body .= '<br>';
                }
            }
            $body .= '<br>';
            $token = $this->encode($subscription_id, 5);   // bump all
            $link = $this->objHelper->buildLink($website_base . 'index.php?option=com_ra_mailman&task=mail_lst.processEmail&token=' . $token, 'Renew');
            $body .= 'To renew <b>ALL</b> your subscription for another year please click ' . $link . '<br>';

            $token = $this->encode($subscription_id, 4);
            $link = $this->objHelper->buildLink($website_base . 'index.php?option=com_ra_mailman&task=mail_lst.processEmail&token=' . $token, 'Cancel');
            $body .= 'If you want to cancel <b>ALL</b> your subscription please click ' . $link . '<br>';
        }
        $body .= $params->get('email_footer');
        $body .= '';

        $title = $item->List . ' for group ' . $item->group_code . ' - Renewal required';
//        echo $item->email . '<br>';
//        echo $title . '<br>';
//        echo $body;

        return $this->sendEmail('webmaster@bigley.me.uk', $item->reply_to, $title, $body, '');

//        return $this->sendEmail($item->email, $item->reply_to, $title, $body, '');
    }

    private function storeMessage($mailshot_id, $mailshot_body) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $fields = array(
            $db->quoteName('final_message') . '=' . $db->quote($mailshot_body)
        );

// Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($mailshot_id)
        );

        $query->update($db->quoteName('#__ra_mail_shots'))->set($fields)->where($conditions);
        $db->setQuery($query);
        if ($db->execute()) {
//            $this->message .= $mailshot_body;                           // Debug
            return 1;
        }
        return 0;
    }

    public function subscribe($list_id, $user_id, $record_type, $method_id) {
// Subscribes the given user to the given list
// if invoked from the front-end, $user_id will usually be the current user,
// but from the back-end, or if invoked from view list_select, it could be any user
//
// $record_type (from back end) could be 1=Subscription or 2=author
        //      Get the id of the current user
        $current_user_id = Factory::getUser()->id;

        if (JDEBUG) {
            echo "Creating subscription for list=" . $list_id . ', user=' . $user_id;
            echo ", record_type=" . $record_type . ', method_id=' . $method_id . '<br>';
        }
        // in mail_lists, record_type signifies open/closed
        $sql = 'SELECT group_code, name, record_type, home_group_only ';
        $sql .= 'FROM `#__ra_mail_lists` ';
//        $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
        $sql .= 'WHERE id=' . $list_id;
        $list = $this->objHelper->getItem($sql);

        // in subscriptions, record_type signifies type of access
        $sql = 'SELECT s.id, s.record_type, s.state, ma.name ';
        $sql .= 'FROM #__ra_mail_subscriptions as s ';
        $sql .= 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = s.list_id ';
        $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
        $sql .= 'WHERE s.user_id=' . $user_id;
        $sql .= ' AND s.list_id=' . $list_id;
        $item = $this->objHelper->getItem($sql);
        if ($item) {
            if (($item->state == 1) AND ($item->record_type == $record_type)) {
                $this->message .= 'User is already subscribed to ' . $list->name . ' as ' . $item->name;
                return false;
            }
        }
//  Check that user is in the correct group
        if ($list->home_group_only == '1') {
            $sql = 'SELECT home_group FROM #__ra_profiles ';
            $sql .= 'WHERE id=' . $user_id;
            $home_group = $this->objHelper->getValue($sql);
            if ($list->group_code != $home_group) {
                $this->message .= 'You cannot subscribe to ' . $list->name . ' because it is only open to ' . $list->group_code;
                return false;
            }
        }

// Extra check if we are in the front end and self registering
        if ((JPATH_BASE == JPATH_SITE) AND ($current_user_id == 0)) {
//          Check that the list is open to subscription by this user
            if ($list->record_type != 'O') {
                $this->message .= 'You cannot subscribe to ' . $list->group_code . ' ' . $list->name . ' because it is a Closed list';
                return false;
            }
        }

        $state = 1;       // Active
        if ($this->updateSubscription($list_id, $user_id, $record_type, $method_id, $state)) {
            if ($current_user_id == $user_id) {
                $message = 'You have ';
            } else {
                $message = 'User has been ';
            }
            $message .= $this->message;
            $message .= ' ' . $list->group_code . ' ' . $list->name;
            $this->message = $message;
            return true;
        } else {
// error will already have been displayes
            return false;
        }
    }

    public function unsubscribe($list_id, $user_id, $method_id) {
        $sql = 'SELECT id, record_type FROM #__ra_mail_subscriptions WHERE user_id=' . $user_id;
        $sql .= ' AND list_id=' . $list_id;
        $item = $this->objHelper->getItem($sql);
        if (is_null($item)) {
            $this->message = 'You are not already subscribed to this list';
            $this->message .= $sql;
            return false;
        }
        $record_type_original = $item->record_type;
// Check that this is not a closed list
        $sql = 'SELECT * FROM `#__ra_mail_lists` WHERE id=' . $list_id;
//        Factory::getApplication()->enqueueMessage($sql, 'notice');
        $item = $this->objHelper->getItem($sql);
// Extra check if we are in the front end
        if (JPATH_BASE == JPATH_SITE) {
//            $this->message = 'You are in the front end';
            if (!$item->record_type == 'O') {
                $this->message = 'You cannot unsubscribe from ' . $item->name . ' because it is a Closed list';
                return false;
            }
        }
//        $message .= "Unsub from $list_id, $user_id, 1, $method_id,0 ";
        $state = 0;       // Cancelled
        if ($this->updateSubscription($list_id, $user_id, $record_type_original, $method_id, $state)) {
            $message = (JPATH_BASE == JPATH_SITE) ? 'User has been ' : $message = 'User has been ';
        }
        $message .= ' ' . $item->group_code . ' ' . $item->name;
        $this->message = $message;
    }

    private function updateDate($mailshot_id, $date_field) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

//        $dateTimeNow = new DateTime('NOW');
        $dateTimeNow = Factory::getDate()->toSql();

        $fields = array(
            $db->quoteName($date_field) . '=' . $db->quote($dateTimeNow)
        );

// Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . ' = ' . $db->quote($mailshot_id)
        );
        $query->update($db->quoteName('#__ra_mail_shots'))->set($fields)->where($conditions);

        $db->setQuery($query);
        if ($db->execute()) {
            return 1;
        }
        $this->message = "Unable to set date";
        return 0;
    }

    public function updateSubscription($list_id, $user_id, $record_type, $method_id, $state) {
        /*
         * Can cancel a subscription or set one up
         * If no record exists in #__ra_mail_subscriptions, one will be created
         * list_id - id of the particular mailing list
         * record_type - 1 = user, 2 = Author, 3 = Owner
         * method_id - 1 = User self registered, 2 = Administrator, 3 = corporate feed,
         *             4 = MailChimp, 5 = CSV file, 6 = Unscribed via link, 7 = Administrator from front end
         *             8 = User self registration, 9 = Batch housekeeping
         * user_id -  User that subscribed/Unsubscribed
         * state - 1 = current, 0 = Cancelled

         */

        $objSubscription = new SubscriptionHelper;
        $objSubscription->list_id = $list_id;
        $objSubscription->user_id = $user_id;
        $date = Factory::getDate('now', Factory::getConfig()->get('offset'));
        $expiry_date = substr($date, 0, 10);  // ignore times
        $objSubscription->expiry_date = $expiry_date->toSql(true);
        if ($objSubscription->getData()) {
//            Factory::getApplication()->enqueueMessage('UpdateSubscription1: type=' . $objSubscription->record_type . ',method=' . $objSubscription->record_type . ', state=' . $objSubscription->state . ',modified_by=' . $objSubscription->modified_by . ', id=' . $objSubscription->id, 'notice');
            $objSubscription->record_type = $record_type;
            // don't allow MaillChimp or self registration to be overwritten by Corporate feed
            $valid = array(2, 5, 7, 9);
            if (in_array($objSubscription->method_id, $valid)) {
                $objSubscription->method_id = $method_id;
            }
            $objSubscription->state = $state;
//            Factory::getApplication()->enqueueMessage("UpdateSubscription2: type=$record_type,method=$record_type, state=$state", 'notice');
            $return = $objSubscription->update();
        } else {
// If state is zero, we are unsubscribing the old user - so we
// don't care if record is present or not
            if ($state == 0) {
                $return = 0;
            } else {
                // Subscription record not yet present
                $objSubscription->message = '';
//                Factory::getApplication()->enqueueMessage("UpdateSubscription: record NOT found", 'notice');
                $objSubscription->record_type = $record_type;
                $objSubscription->method_id = $method_id;
                $objSubscription->state = $state;
// If enrolment is via MailChimp or Corporate feed, expiry date will be set in the class
                $return = $objSubscription->add();
            }
        }
        if ($return) {

        } else {
            Factory::getApplication()->enqueueMessage('UpdateSubscription: action=' . $objSubscription->message, 'error');
        }
        $this->message = $objSubscription->action;
        return $return;
    }

    private function validSubscription($id) {
        $sql = 'SELECT l.name, u.username ';
        $sql .= 'FROM `#__ra_mail_subscriptions` AS s  ';
        $sql .= 'LEFT JOIN `#__ra_mail_lists` AS l ON l.id  = s.list_id ';
        $sql .= 'LEFT JOIN `#__users` AS u ON u.id = s.user_id ';
        $sql .= 'WHERE s.id=' . $id;
        $item = $this->objHelper->getItem($sql);
        if ((is_null($item->name) OR (is_null($item->username)))) {
            die('Duff ' . $id);
            Factory::getApplication()->enqueueMessage('Invalid subscription deleted ' . $id, 'info');
            $sql = 'DELETE FROM `#__ra_mail_subscriptions_audit` WHERE object_id=' . $id;
            $this->objHelper->executeCommand($sql);
            $sql = 'DELETE FROM `#__ra_mail_subscriptions` WHERE id=' . $id;
            $this->objHelper->executeCommand($sql);
            return false;
        }
        return true;
    }

}
