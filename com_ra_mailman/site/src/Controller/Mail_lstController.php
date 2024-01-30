<?php

/**
 * @version    4.0.13
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 03/09/23 CB copied bump, bumpAll etc from J3
 * 19/10/23 CB Don't check cando before allowing registered user to subscribe
 * 13/11/23 CB pass required parameter to canDo
 * 14/11/23 CB use menu_id as passed as parameter
 * 07/12/23 CB showPrint
 * 08/01/24 CB use SubscriptionController for Bump and BumpAll
 * 16/01/24 CB when displaying subscribers, don't show email / IP address
 */

namespace Ramblers\Component\Ra_mailman\Site\Controller;

\defined('_JEXEC') or die;

use \Joomla\CMS\Application\SiteApplication;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Controller\BaseController;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Uri\Uri;
use \Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Mail_lst class.
 *
 * @since  1.6.0
 */
class Mail_lstController extends BaseController {

    protected $objHelper;
    protected $objMailHelper;
    protected $message;

    function __construct() {
        parent::__construct();
        $this->objMailHelper = new Mailhelper;
        $this->objHelper = new ToolsHelper;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    private function cancelSubscription($list_id, $user_id) {
        /*
         * Invoked from a CLI programme, invoked via email, that cancels any subscriptions for the specified User
         */
        $this->unsubscribe($this->user_id, $this->list_id, 6);
    }

    private function cancelAll($user_id) {
        /*
         * Invoked from a CLI programme, invoked via email, that cancels any subscriptions for the specified User
         */
        $message = '';
        $record_type = 1; // user
        $method_id = 6;   // Email
        $sql = 'SELECT id from #__ra_mail_subscriptions WHERE user_id=' . $user_id;
        $sql .= ' AND state=1';
        $rows = $this->objHelper->getRows($sql);
        foreach ($rows as $row) {
            $this->objMailHelper->updateSubscription($row->list_id, $user_id, $record_type, $method_id, 0);
        }
    }

    function debug() {
        // temp function to debug the creation of users/profiles
        // can only be invoked from as a URL: index.php?option=com_ra_mailman&task=mail_lst.debug
        $user_id = 5;
        $group_code = 'NS05';
        $this->name = 'Debug5';
        $objProfile = new Ra_toolsProfile;
        $objProfile->id = $user_id;
        if ($objProfile->getData()) {
            // There should not be an existing records, but if there is, update it
            $objProfile->set_home_group($group_code);
            $result = $objProfile->update();
        } else {
//           Factory::getApplication()->enqueueMessage('Creating ' . $group_code . ' profile record for ' . $user_id, 'Comment');
            $objProfile->set_ra_group_code($group_code);
            $objProfile->set_home_group($group_code);
            $objProfile->set_ra_display_name($this->name);
            $result = $objProfile->add();
        }
        echo "Result from createProfile for $group_code + $this->name is " . $result . '<br>';
    }

    function decode($encoded, &$subscription_id, &$mode, $debug = False) {
        /*
         * Takes the string that has been obfuscated by function "encode",
         *  and splits it into constituents
         */
        $validchars = '0123456789abcdef';
        if ($debug) {
            echo "Mail/decode: " . $encoded . "<br>";
        }
        $temp = $encoded;
        $temp = strrev(substr($temp, 0, strlen($temp) - 1));

        $token = "";
        for ($i = 0; $i < strlen($temp); $i++) {
            $char = substr($temp, $i, 1);
//            $test = strpos($validchars, $char);
//            echo 'char=' . $char . ' ' . $test . '<br>';
            if (strpos($validchars, $char) == '') {
                $this->message = $char . ' invalid<br>';
                return false;
            }
            $token .= (hexdec($char) - 6);
        }
        if ($debug) {
            echo "After decoding: " . $token . "<br>";
        }
//      Split the string into constituents, ignoring first 6 characters
        $mode = substr($token, 6, 1);
        $temp = substr($token, 7);
        if ($debug) {
            echo "Mode " . $mode . ", temp " . $temp . "<br>";
        }
        $length_id = (int) substr($temp, 0, 2);
        if ($length_id === 0) {
            $this->message = 'length ' . substr($temp, 0, 2) . ' invalid<br>';
            return false;
        }
        $sub7 = substr($temp, 2, $length_id);
        $subscription_id = $sub7 / 7;

        if ($debug) {
            echo "Length " . $length_id . ', temp=' . $temp . ', sub7=' . $sub7 . ", mode= " . $mode . ", subscription_id=" . $subscription_id . "<br>";
        }
        return true;
    }

    public function processEmail() {
        /*
         * This will be invoked by the user clicking on a link from an email
         * they have been sent by a batch job containing a token
         */
        $objApp = Factory::getApplication();
        $token = $objApp->input->getCmd('token', '');
//        echo "Controller: token is $token<br>";
// For diagnostics, set final parameter to True
        if ($this->decode($token, $subscription_id, $mode, false) === false) {
            echo "Sorry, this seems be be in invalid token" . '<br>';
            $this->showButtons();
            die;
        }
        $objSubscription = new SubscriptionHelper;

        $sql = 'SELECT s.list_id, s.user_id, s.state, '
                . 'l.group_code, l.name, '
                . ' m.name as "Method", u.name as "User" '
                . 'FROM `#__ra_mail_subscriptions` AS s '
                . 'INNER JOIN `#__ra_mail_lists` AS l ON l.id = s.list_id '
                . 'LEFT JOIN #__users AS u ON u.id = s.user_id '
                . "INNER JOIN  #__ra_mail_methods as m on m.id = s.method_id "
                . 'WHERE s.id=' . $subscription_id;
        $item = $this->objHelper->getItem($sql);
        if (is_null($item)) {
            echo "Controller: token is $token<br>";
            echo "Sorry, this seems be be in invalid reference" . '<br>';
            $this->showButtons();
            die;
        }
        $list = $item->group_code . ' ' . $item->name;

        if ($mode == 0) {
            if ($item->state == 0) {
                $message = 'You are already unsubscribed from ' . $list;
                if (JDEBUG) {
                    $message .= ', mode=' . $mode . ', id=' . $subscription_id;
                }
                echo $message . '<br>';
                $this->showButtons();
                die;
            }
            $result = $this->objMailHelper->unsubscribe($item->list_id, $item->user_id, 6);
            $action = 'unsubscribed from';
        } elseif ($mode == 1) {
            if ($item->state == 1) {

                $message = 'You are already subscribed to ' . $list;
//               if (JDEBUG) {
                $message .= ', mode=' . $mode . ', id=' . $subscription_id;
//                }
                echo $message . '<br>';
                echo 'You are already subscribed to ' . $list . ' as ' . $item->Method . '<br>';
                $this->showButtons();
                die;
            }
            $result = $this->objMailHelper->subscribe($item->list_id, $item->user_id, 1, 6);
            $action = 'subscribed to';
        } elseif ($mode == 2) {  // Cancel specific subscription
            $record_type = 1; // user
            $method_id = 6;   // Email
            $this->objMailHelper->updateSubscription($item->list_id, $item->user_id, $record_type, $method_id, 0);
            $action = 'Cancelled your subscription to ' . ' ' . $list;
        } elseif ($mode == 3) {  // Renew specific subscription for another 12 months
            $objSubscription->bumpSubscription($item->list_id, $item->user_id);
            $action = 'Renewed your subscription to ' . $list . ' for another 12 months';
        } elseif ($mode == 4) {  // Cancel all subscriptions
            $this->cancelAll($item->user_id);
            $action = 'You are no longer subscribed to MailMan<br>Bye!';
        } elseif ($mode == 5) {  // Renew all subscriptions for another 12 months
            $objSubscription->bumpAll($item->user_id);
            $action = 'Renewed all your subscription for another 12 months';
        } else {
            echo "Sorry, this seems be be in invalid mode" . '<br>';
            $this->showButtons();
            die;
        }
        echo $item->User . ': You have successfully ' . $action;

        $this->showButtons();
    }

    public function sendMessage($message) {
        echo $message;
        $target = 'index.php?option=com_ra_mailman&view=mail_lsts';
        echo $this->objHelper->buildLink($target, "Home", False);
    }

    public function showButtons() {
        $back = uri::base();
        echo $this->objHelper->buildLink($back, "Home", False);
        echo $this->objHelper->buildLink($back . 'index.php?option=com_users&view=login', "Login", False);
    }

    public function showSubscribers() {
        // shows all Users for the given mail-list
        //

        $app = Factory::getApplication();

        $list_id = $app->input->getInt('list_id', '');
        $menu_id = $app->input->getInt('Itemid', 0);
        $back = 'index.php?option=com_ra_mailman&view=mail_lsts';
        $back .= '&Itemid=' . $menu_id;

        // First check user is the owner of the list
        if ($this->objMailHelper->isAuthor($list_id)) {

        } else {
            Factory::getApplication()->enqueueMessage('Invalid access', 'notice');
            $this->setRedirect(Route::_($back, false));
        }

        $description = $this->objMailHelper->getDescription($list_id);
        $sql_count = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions_audit WHERE object_id=';

//      Show link that allows page to be printed
        $target = "index.php?option=com_ra_mailman&task=mail_lst.showSubscribers&list_id=" . $list_id;
        echo $this->objHelper->showPrint($target);

        echo "<h4>Subscribers for " . $description . '</h4>';

        $sql = "SELECT ";
        $sql .= "u.id, u.name AS 'User',  ";
        $sql .= "p.home_group, ";
        $sql .= "m.name as 'Method', ";
        $sql .= 'CASE WHEN record_type=3 THEN "Owner" WHEN record_type=2 THEN "Author" ELSE "Subscriber" END AS "Type", ';
        $sql .= "s.id AS subscription_id, s.modified, ";
        $sql .= "modifier.name AS UpdatedBy ";
        $sql .= 'FROM #__users as u ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s on u.id = s.user_id ';
        $sql .= 'INNER JOIN #__ra_mail_methods AS m on m.id = s.method_id ';
        $sql .= 'LEFT JOIN #__users AS modifier on modifier.id = s.modified_by ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p on p.id = u.id ';
        $sql .= 'WHERE s.list_id=' . $list_id;
        $sql .= ' AND s.state=1';
        $sql .= ' ORDER BY s.state DESC, u.name';
//        echo $sql;
        $rows = $this->objHelper->getRows($sql);

        $objTable = new ToolsTable;
        $objTable->add_header("User,Group,Method,Type,Last Updated,Updated by");
        foreach ($rows as $row) {
            $objTable->add_item($row->User);
            $objTable->add_item($row->home_group);
            $objTable->add_item($row->Method);
            $objTable->add_item($row->Type);
            $count = $this->objHelper->getValue($sql_count . $row->id);
            if ($count == 0) {
                $objTable->add_item($row->modified);
            } else {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.showAuditSingle&id=' . $row->subscription_id;
                $target .= '&list_id=' . $list_id;
                $objTable->add_item($this->objHelper->buildLink($target, $row->modified));
            }
            $objTable->add_item($row->UpdatedBy);
            $objTable->add_item($row->Status);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        echo $objTable->get_rows() . ' active Subscribers';
        echo $this->objHelper->backButton($back);
    }

    public function subscribe() {
        /*
         *  This takes two parameters: the id of the mailing list and the id of the User to be
         * enrolled on it. It can be invoked from two places: if from view mail_lists, then
         * new_user_id will be the same as the currently logged on user, and control will be
         * passed back to there; else from view list_select when the current user must be a Manager.
         */
        $objApp = Factory::getApplication();
        $list_id = (int) $objApp->input->getCmd('list_id', '');
        $new_user_id = (int) $objApp->input->getCmd('user_id', '0');
        $menu_id = (int) $objApp->input->getCmd('Itemid', 0);
        $current_user_id = Factory::getUser()->id;
        //die("Mail_lst/Controller: list=$list_id,  current_user=$current_user_id," . ToolsHelper::canDo('com_ra_mailman'));

        if ($current_user_id == 01) {
            Factory::getApplication()->enqueueMessage('You must log in to access this function', 'error');
            // throw new \Exception('You must log in to access this function', 401);
        } else {
//            Factory::getApplication()->enqueueMessage('User=' . $new_user_id, 'notice');
//            Factory::getApplication()->enqueueMessage(ToolsHelper::canDo('com_ra_mailman'), 'notice');
            // record_type = 1 (subscribe as recipient)
            // $method = 1 (User registration)
            if ($new_user_id == $current_user_id) {
                $method = 1;  // (User registration)
            } else {
                $method = 6;  //  (Admin Frontend)
            }
            $result = $this->objMailHelper->subscribe($list_id, $new_user_id, 1, $method);
            Factory::getApplication()->enqueueMessage($this->objMailHelper->message, 'notice');

            $target = 'index.php?option=com_ra_mailman&view=' . $this->callback;
            if ($method == 1) {
                $callback = 'mail_lsts';
            } else {
                $callback = 'list_select&user_id=' . $new_user_id;
            }
        }
        $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=' . $callback . '&Itemid=' . $menu_id, false));
    }

    public function test() {
        $i = 5;
        $mode = 0;
        $subscription_id = 0;
        for ($mode = 0; $mode < 2; $mode++) {
            for ($i = 5; $i < 10; $i++) {
                $token = $this->objMailHelper->encode($i, $mode);
                echo "Encoding $i with mode $mode gives <b>" . $token . "</b>";
                $this->decode($token, $subscription_id, $mode, false);
                echo ", Decoding $token gives  <b>$subscription_id ,mode $mode</b><br>";
            }
        }
        $token = array();
        $token[] = 'd768b9f98fM';            // 1
        $token[] = 'a7868be66d9M';           // 2
        $token[] = 'e8868daeb9cM';           // 4
        $token[] = 'fe7968e7fff7M';          // 27
        $token[] = '769968abb6d9M';          // 43
        for ($i = 0; $i < 5; $i++) {
            echo $token[$i] . "</b><br>";
            $this->decode($token[$i], $subscription_id, $mode, false);
            echo "Decoding $token[$i] gives  <b>$subscription_id</b><br>";
        }
    }

    public function unsubscribe() {
        $objApp = Factory::getApplication();
        $list_id = (int) $objApp->input->getCmd('list_id', '');
        $new_user_id = (int) $objApp->input->getCmd('user_id', '0');
        $menu_id = (int) $objApp->input->getCmd('Itemid', 0);

        $current_user = Factory::getUser();
        $current_user_id = $current_user->id;
        if ($current_user_id == 0) {
            Factory::getApplication()->enqueueMessage('You must log in to access this function', 'error');
            //throw new \Exception('You must log in to access this function', 401);
        } else {
//            Factory::getApplication()->enqueueMessage('User=' . $user_id, 'notice');
            if (ToolsHelper::canDo('com_ra_mailman') === true) {
                $result = $this->objMailHelper->unsubscribe($list_id, $new_user_id, 1);
                Factory::getApplication()->enqueueMessage($this->objMailHelper->message, 'notice');
            } else {
                //Factory::getApplication()->enqueueMessage(ToolsHelper::canDo('com_ra_mailman'), 'error');
                throw new \Exception('Access not permitted', 401);
            }
        }
        if ($current_user_id == $new_user_id) {
            $callback = 'mail_lsts';
        } else {
            $callback = 'list_select&user_id=' . $new_user_id;
        }
        $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=' . $callback . '&Itemid=' . $menu_id, false));
    }

}
