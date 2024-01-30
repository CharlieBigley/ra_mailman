<?php

/**
 * @version    4.0.7
 * @package    com_ra_mailman
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 19/06/23 CB Created from com_ra_tools
 * 22/07/23 CB showDue copied from Joomla 3
 * 14/11/23 CB change message for profiles no user
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
//use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHtml;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

class ReportsController extends FormController {

    protected $back = 'administrator/index.php?option=com_ra_mailman&view=reports';
    protected $db;
    protected $objApp;
    protected $objHelper;
    protected $prefix;
    protected $query;
    protected $scope;

    public function __construct() {
        parent::__construct();
        $this->db = Factory::getDbo();
        $this->objHelper = new ToolsHelper;
        $this->objApp = Factory::getApplication();
        $this->prefix = 'Reports: ';
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function blockedUsers() {
        ToolBarHelper::title($this->prefix . 'Blocked users');
        $objTable = new ToolsTable();
        $objTable->add_header("Group,List,Name,email,ID");
        $sql = "SELECT l.id, l.group_code, l.name as List, ";
        $sql .= "u.id, u.name as 'User', u.email AS 'email' ";
        $sql .= 'FROM  `#__ra_mail_lists` AS l ';
        $sql .= 'INNER JOIN `#__ra_mail_subscriptions` AS s on s.list_id = l.id ';
        $sql .= 'INNER JOIN `#__users` AS u on u.id = s.user_id ';

        $sql .= ' WHERE u.block=1';
        $sql .= ' ORDER BY l.group_code, l.name';
//        $target = 'administrator/index.php?option=com_users&view=users';
        $rows = $this->objHelper->getRows($sql);
        foreach ($rows as $row) {
            $objTable->add_item($row->group_code);
            $objTable->add_item($row->List);

            $objTable->add_item($row->User);
            $objTable->add_item($row->email);
            $objTable->add_item($row->id);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        echo $this->objHelper->backButton($this->back);
    }

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    public function duffUsers() {
        ToolBarHelper::title($this->prefix . 'Users without a Profile');
        $objTable = new ToolsTable();
        $objTable->add_header("ID,Name,email,Action");
        $sql = "SELECT u.id, u.name, u.email ";
        $sql .= "FROM `#__users` as u ";
        $sql .= "LEFT JOIN #__ra_profiles AS p on p.id = u.id ";
        $sql .= "WHERE p.id IS NULL ";
        $sql .= "order by u.id";
        $target = 'administrator/index.php?option=com_users&view=users';
        $rows = $this->objHelper->getRows($sql);
        foreach ($rows as $row) {
            $objTable->add_item($row->id);
            $objTable->add_item($row->name);
            $objTable->add_item($row->email);
            $link = $this->objHelper->buildLink($target, 'Purge');
            $objTable->add_item($link);
            $objTable->generate_line();
        }
        $objTable->generate_table();

//  See if any Profiles without a User
        $sql = "SELECT count(*) FROM #__ra_profiles AS p ";
        $sql .= "LEFT JOIN `#__users` as u on u.id = p.id ";
        $sql .= "WHERE u.id IS NULL ";
        $count = $this->objHelper->getValue($sql);
        if ($count > 0) {
            echo 'User has been deleted manually, Profiles still present<br>';
            $target = 'administrator/index.php?option=com_ra_mailman&task=profile.purgeProfile&id=';
            $objTable->add_header("ID,Group,Name,Action");
            $sql = "SELECT p.id, p.home_group, p.preferred_name ";
            $sql .= "FROM #__ra_profiles AS p ";
            $sql .= "LEFT JOIN `#__users` as u on u.id = p.id ";
            $sql .= "WHERE u.id IS NULL ";
            $sql .= "order by p.id";

            $rows = $this->objHelper->getRows($sql);
            foreach ($rows as $row) {
                $objTable->add_item($row->id);
                $objTable->add_item($row->home_group);
                $objTable->add_item($row->preferred_name);
                $link = $this->objHelper->buildLink($target . $row->id, 'Purge');
                $objTable->add_item($link);
                $objTable->generate_line();
            }
            $objTable->generate_table();
        }
        echo $this->objHelper->backButton($this->back);
    }

    function showDue() {
        /*
          $objHelper = new ToolsHelper;
          $sql = 'SELECT s.id, s.expiry_date, DATE(s.expiry_date) AS `Due`, datediff(s.created, CURRENT_DATE) AS `Days`, ';
          $sql .= 's.created, s.modified,';
          $sql .= 'u.name AS `subscriber`, ';
          $sql .= 'l.group_code AS `group`, l.name AS `list`, ';
          $sql .= 'm.name AS `Method`, ma.name as Access  ';
          $sql .= 'FROM `#__ra_mail_subscriptions` AS s ';
          $sql .= 'INNER JOIN `#__ra_mail_methods` AS `m` ON m.id = s.method_id ';
          $sql .= 'LEFT JOIN `#__users` AS `u` ON u.id = s.user_id ';
          $sql .= 'LEFT JOIN `#__ra_mail_lists` AS `l` ON l.id =s.list_id ';
          $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
          $sql .= 'WHERE s.state=1 ';
          $sql .= 'AND expiry_date IS NULL ';
          //        $sql .= 'LIMIT 20';
          echo $sql;
          $rows = $objHelper->getRows($sql);
          $objTable = new ToolsTable;
          $header = 'Group,List,User,Due,Days,Created,Modified,Method,Access';
          $objTable->add_header($header);
          $sql = 'UPDATE `#__ra_mail_subscriptions` SET expiry_date="';
          foreach ($rows as $row) {
          if ($row->Days < -730) {
          $objTable->add_item($row->group);
          $objTable->add_item($row->list);
          $objTable->add_item($row->subscriber);
          $details = $row->created;
          //                echo 'row->expiry_date ' . gettype($row->expiry_date) . ', expiry_date ' . gettype($expiry_date) . '<br>';
          $temp = strtotime(date("Y-m-d", strtotime($row->expiry_date)) . " +1 year");
          $expiry_date = date('Y-m-d', $temp);
          $details .= '<br>' . $expiry_date;
          $objTable->add_item($details);
          //                $objTable->add_item($row->Due);
          $objTable->add_item($row->Days);
          $objTable->add_item($row->created);
          $objTable->add_item($row->modified);
          $objTable->add_item($row->Method);
          $objTable->add_item($row->Access);
          $objTable->generate_line();
          //                $objHelper->executeCommand($sql . $expiry_date . '" WHERE id=' . $row->id);
          //                echo $sql . $expiry_date . '" WHERE id=' . $row->id;
          }
          }
          $objTable->generate_table();
         */

        // Shows a matrix of the number of subscriptions due for renewal
        // Columns are months, with a row for each mailing list
        ToolBarHelper::title('Mailman report');
        $objHelper = new ToolsHelper;
        $current_year = date('Y');
        $current_month = date('m');
        echo "<h2>Renewals by Date</h2>";
        if ($current_month == '01') {
            $month_string = '1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12,1';
        } else {
            $month_string = '';
            for ($i = $current_month; $i < 13; $i++) {
                $month_string .= $i . ', ';
            }
            for ($i = 1; $i < $current_month; $i++) {
                $month_string .= $i . ', ';
            }
            $month_string .= (int) $current_month;
        }
        $months = explode(', ', $month_string);
        $yyyy = $current_year;
        $sql = 'SELECT id, group_code, name from `#__ra_mail_lists` ';
        $sql .= 'ORDER BY group_code, name';
        $lists = $objHelper->getRows($sql);
        $objTable = new ToolsTable;
        $header = 'Group,List';
        $yyyy = $current_year;

//      we need a total for each column of the report
        $total = array();

//      we need arrays to hold the actual date for each column of the report
        $param_year = array();
        $param_month = array();

        $i = 0;
        foreach ($months as $month) {
            $header .= ',' . $month . ' ' . $yyyy;
            if ($month == '12') {
                $yyyy++;
            }
            $total[] = 0;
            $param_year[] = $yyyy;
            $param_month[] = $month;
        }

        $objTable->add_header($header);
        $report_url = 'administrator/index.php?option=com_ra_mailman&task=reports.showSubscriptionsDue';
        foreach ($lists as $list) {
            $objTable->add_item($list->group_code);
            $objTable->add_item($list->name);
            $yyyy = $current_year;
            $col = 0;
            foreach ($months as $month) {
//                echo "$month<br>";

                $sql = 'SELECT COUNT(s.id) ';
                $sql .= 'FROM `#__ra_mail_subscriptions` AS s ';
                $sql .= 'INNER JOIN `#__ra_mail_methods` AS `m` ON m.id = s.method_id ';
                $sql .= 'LEFT JOIN `#__users` AS `u` ON u.id = s.user_id ';
                $sql .= 'LEFT JOIN `#__ra_mail_lists` AS `l` ON l.id = s.list_id ';
                $sql .= 'WHERE `s`.`state`=1 ';
                $sql .= 'AND `u`.`block`=0 ';
                $sql .= 'AND l.id=' . $list->id . ' ';
                //if ($month == $current_month) {
                if ($col == 0) {
// check for any that may have been missed
                    $sql .= 'AND (YEAR(s.expiry_date) <= "' . $yyyy . '" AND MONTH(s.expiry_date) <="' . $month . '" ';
                    $sql .= 'OR s.expiry_date IS NULL)';
//                    echo "$sql<br>";
                } else {
                    $sql .= 'AND YEAR(s.expiry_date)="' . $yyyy . '" AND MONTH(s.expiry_date)="' . $month . '" ';
                }

//                echo "$sql<br>";
                $count = $objHelper->getValue($sql);
                if ($count == 0) {
                    $objTable->add_item('');
                } else {
                    $link = $report_url . '&list_id=' . $list->id . '&year=' . $yyyy . '&month=' . $month;
                    $objTable->add_item($objHelper->buildLink($link, $count));
                    $total[$col] = $total[$col] + $count;
                }

                $col++;
                if ($month == '12') {
                    $yyyy++;
                }
            }
            $objTable->generate_line();
        }

// Generate a final line with totals for each month
        $target = 'administrator/index.php?option=com_ra_mailman&task=reports.showSubscriptionsDue';

        $objTable->add_item('<b>Total</b>');
        $objTable->add_item('');

        for ($i = 0; $i < 12; $i++) {
//            echo "i=$i," . $total[$i] . '<br>';
            if ($total[$i] == 0) {
                $objTable->add_item('');
            } else {
                $link = $target . '&year=' . $param_year[$i] . '&month=' . $param_month[$i];
                $objTable->add_item($objHelper->buildLink($link, $total[$i]));
            }
        }
        $objTable->generate_line();
        $objTable->generate_table();

        $back = "administrator/index.php?option=com_ra_mailman&view=reports";
        echo $objHelper->backButton($back);
    }

    public function showLogfile() {

        $offset = $this->objApp->input->getCmd('offset', '');
        $next_offset = $offset - 1;
        $previous_offset = $offset + 1;
        $rs = "";

        $date_difference = (int) $offset;
        $today = date_create(date("Y-m-d 00:00:00"));
        if ($date_difference === 0) {
            $target = $today;
        } else {
            if ($date_difference > 0) { // positive number
                $target = date_add($today, date_interval_create_from_date_string("-" . $date_difference . " days"));
            } else {
                $target = date_add($today, date_interval_create_from_date_string($date_difference . " days"));
            }
        }
        ToolBarHelper::title($this->prefix . 'Logfile records for ' . date_format($target, "D d M"));

        $sql = "SELECT date_format(log_date, '%a %e-%m-%y') as Date, ";
        $sql .= "date_format(log_date, '%H:%i:%s.%u') as Time, ";
        $sql .= "record_type, ";
        $sql .= "ref, ";
        $sql .= "message ";
        $sql .= "FROM #__ra_logfile ";
        $sql .= "WHERE log_date >='" . date_format($target, "Y/m/d H:i:s") . "' ";
        $sql .= "AND log_date <'" . date_format($target, "Y/m/d 23:59:59") . "' ";
        $sql .= "ORDER BY log_date DESC, record_type ";
        if ($this->objHelper->showSql($sql)) {
            echo "<h5>End of logfile records for " . date_format($target, "D d M") . "</h5>";
        } else {
            echo 'Error: ' . $this->objHelper->error . '<br>';
        }

        echo $this->objHelper->buildLink("administrator/index.php?option=com_ra_tools&task=reports.showLogfile&offset=" . $previous_offset, "Previous day", False, 'btn btn-small button-new');
        if ($next_offset >= 0) {
            echo $this->objHelper->buildLink("administrator/index.php?option=com_ra_tools&task=reports.showLogfile&offset=" . $next_offset, "Next day", False, 'btn btn-small button-new');
        }
        $target = "administrator/index.php?option=com_ra_tools&view=reports";
        echo $this->objHelper->backButton($target);
    }

    public function showSubscriptionsDue() {
// Shows subscription due for the given Year / Month
        ToolBarHelper::title('Mailman report');
        $objHelper = new ToolsHelper;
        $current_year = date('Y');
        $current_month = (int) date('m');
//        echo "Date is $current_month $current_year< br>";
        $app = Factory::getApplication();
        $year = $app->input->getInt('year', $current_year);
        $month = $app->input->getInt('month', $current_month);
        $list_id = $app->input->getInt('list_id', '0');

        $sql = 'SELECT DATE(s.expiry_date) AS `Due`, datediff(s.expiry_date, CURRENT_DATE) AS `Days to go`, ';
        $sql .= 's.created, s.modified, s.ip_address, ';
        $sql .= 'u.name AS `subscriber`, ';
        if ($list_id == 0) {
            $sql .= 'l.group_code AS `group`, l.name AS `list`, ';
        }
        $sql .= 'm.name AS `Method`, ma.name as Access  ';
        $sql .= 'FROM `#__ra_mail_subscriptions` AS s ';
        $sql .= 'INNER JOIN `#__ra_mail_methods` AS `m` ON m.id = s.method_id ';
        $sql .= 'LEFT JOIN `#__users` AS `u` ON u.id = s.user_id ';
        $sql .= 'LEFT JOIN `#__ra_mail_lists` AS `l` ON l.id =s.list_id ';
        $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
        $sql .= 'WHERE s.state=1 ';
        if ($list_id == 0) {
            $title = '';
        } else {
            $item = $objHelper->getItem('SELECT group_code, name FROM `#__ra_mail_lists` WHERE id=' . $list_id);
            $sql .= 'AND l.id=' . $list_id . ' ';
            $title = ', List=' . $item->group_code . ' ' . $item->name;
        }
        if ($month == $current_month) {
            if ($year == $current_year) {
//              check for any that may have been missed
                echo '<h2>Subscriptions due on or before ' . $month . ' ' . $year;
                $sql .= 'AND (YEAR(s.expiry_date) <= "' . $year . '" AND MONTH(s.expiry_date) <="' . $month . '" ';
                $sql .= 'OR s.expiry_date IS NULL)';
            } else {
                echo '<h2>Subscriptions due on or after ' . $month . ' ' . $year;
                $sql .= 'AND (YEAR(s.expiry_date) >= "' . $year . '" AND MONTH(s.expiry_date) >="' . $month . '") ';
            }
        } else {
            echo '<h2>Subscriptions due in ' . $month . ' ' . $year;
            $sql .= 'AND YEAR(s.expiry_date)="' . $year . '" AND MONTH(s.expiry_date)="' . $month . '" ';
        }
        echo $title . '</h2>';
        $sql .= 'ORDER BY s.expiry_date ';
        $objHelper->showQuery($sql);
        $back = "administrator/index.php?option=com_ra_mailman&task=reports.showDue";

        echo $objHelper->backButton($back);
    }

}
