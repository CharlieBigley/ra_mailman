<?php

/**
 * @version     4.0.13
 * @package     com_ra_mailman
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 05/01/24 CB Created
 * 08/01/24 CB use SubscriptionHelper
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class SystemController extends FormController {

    protected $back;
    protected $objApp;
    protected $objHelper;

    public function __construct() {
        parent::__construct();
        $this->objHelper = new ToolsHelper;
        $this->objApp = Factory::getApplication();
        $this->back = 'administrator/index.php?option=com_ra_tools&view=dashboard';

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function checkRenewals() {
// log the fact that the program has successfully been called
        $this->logMessage("R1", 1, "ra_renewals.php Started");
// initialise the Helper classes
        $Mailhelper = new Mailhelper();
        $objHelper = new ToolsHelper;

        /*
         *
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = DATE_ADD(expiry_date,INTERVAL 12 MONTH) WHERE id=1
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = DATE_ADD(created,INTERVAL 12 MONTH) WHERE state=1 and method_id=4
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = DATE_ADD(created,INTERVAL 12 MONTH) WHERE state=1 and list_id=1
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = NULL WHERE expiry_date='0000-00-00 00:00:00'
          $config = Factory::getConfig();
          $sender = array(
          $config->get('mailfrom'),
          $config->get('fromname')
          );
          var_dump($config);
          echo '<br>';
          var_dump($sender);
          echo '<br>';
          die();
         */
//==============================================================================
// find Subscription records close to their expiry date
//==============================================================================
// 08/01/24 - next few lines are temporary
        $sql = 'UPDATE `#__ra_mail_subscriptions` SET expiry_date = current_date() WHERE expiry_date IS NULL';
        $objHelper->executeCommand($sql);
        $sql = 'UPDATE `#__ra_mail_subscriptions` SET reminder_sent = NULL';
        $objHelper->executeCommand($sql);


        $notify_interval = ComponentHelper::getParams('com_ra_mailman')->get('notify_interval');

        $this->logMessage("R2", 2, "reminders.php: Seeking Subscriptions in " . $notify_interval) . ' days time';
        echo "Seeking Subscriptions in " . $notify_interval . ' days time<br>' . PHP_EOL;

        $sql = "SELECT s.id, s.list_id, s.user_id, expiry_date, datediff(expiry_date, CURRENT_DATE) AS days_to_go ";
        $sql .= "FROM `#__ra_mail_subscriptions` AS s ";
        $sql .= "WHERE (s.state =1) ";
        $sql .= "AND ((datediff(expiry_date, CURRENT_DATE) < " . $notify_interval . ') ';
        $sql .= " AND (s.reminder_sent IS NULL)) ";
        $sql .= "ORDER BY s.id ";
//        $sql .= 'LIMIT 5';
//        echo $sql . PHP_EOL;

        $rows = $objHelper->getRows($sql);
        $this->logMessage("R3", 3, "Number of Subscriptions due=" . $objHelper->rows);
//        $sql = 'UPDATE`#__ra_mail_subscriptions` SET reminder_sent=CURRENT_DATE WHERE id=';
        if ($rows) {
            echo $objHelper->rows . ' records found <br>';
            $objSubscription = new SubscriptionHelper;
            $date = Factory::getDate('now', Factory::getConfig()->get('offset'));
            foreach ($rows as $row) {
                $this->logMessage("R4", $row->user_id, "id:" . $row->id . "," . $row->expiry_date);
                if ($Mailhelper->sendRenewal($row->id)) {
                    $objSubscription->list_id = $row->list_id;
                    $objSubscription->user_id = $row->user_id;
                    $objSubscription->getData();
                    $objSubscription->set_reminder_sent($date->toSql(true));
                    $objSubscription->update();
//                    echo $sql . $row->id . PHP_EOL;
//                    $objHelper->executeCommand($sql . $row->id);
                }
            }
        } else {
            echo 'No subscriptions found';
        }
        echo '<br>';
        $target = 'administrator/index.php?option=com_ra_mailman&view=subscriptions';
        echo $objHelper->backButton($target);
    }

    function logMessage($record_type, $ref, $message) {
        $db = Factory::getDbo();

// Create a new query object.
        $query = $db->getQuery(true);
// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__ra_logfile'))
                ->set('record_type =' . $db->quote($record_type))
                ->set('ref = ' . $db->quote($record_type))
                ->set('message =' . $db->quote($message));

// Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
    }

}
