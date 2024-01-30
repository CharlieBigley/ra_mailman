<?php

/**
 * @version    4.1.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 15/06/23 CB use ra_mail_access
 * 20/06/23 CB copy code from 1.1.4
 * 22/12/23 CB prettify date sent
 * 30/01/24 CB return to mailshots view, not mail-lists
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Mail_lst controller class.
 *
 * @since  1.0.6
 */
class MailshotController extends FormController {

    protected $db;
    protected $objApp;
    protected $objHelper;
    protected $objMailHelper;
    protected $query;
    protected $view_item = 'mailshot';
    protected $view_list = 'mail_lsts';

    public function __construct(array $config = array(), \Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null) {
        parent::__construct($config, $factory);
        $this->db = Factory::getDbo();
        $this->objHelper = new ToolsHelper;
        $this->objApp = Factory::getApplication();
        $this->objMailHelper = new Mailhelper;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function apply($key = null, $urlVar = null) {
        // does not seem to be invoked
        //die('apply invoked');
        $return = parent::apply($key, $urlVar);
        // Get the parameters passed as part of the URL
        $app = Factory::getApplication();
        $id = $app->input->getInt('id', '1');
        $list_id = $app->input->getInt('list_id', '0');
        // Redirect back to edit form
        $target = 'index.php?option=com_ra_mailman&view=mailshot&layout=edit';
        $target .= '&id=' . $id . '&list_id=' . $list_id;
        $this->setRedirect(Route::_($target, false));
        return $return;
    }

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('/administrator/index.php?option=com_ra_mailman&view=mail_lsts');
    }

    public function save($key = null, $urlVar = null) {
        $return = parent::save($key, $urlVar);
        if ($return) {
            // Redirect back to mailing lists
            $target = '/administrator/index.php?option=com_ra_mailman&view=mail_lsts';
        } else {
            // Get the parameters passed as part of the URL
            $app = Factory::getApplication();
            $id = $app->input->getInt('id', '1');
            $list_id = $app->input->getInt('list_id', '0');
            // Redirect back to edit form
            $target = 'index.php?option=com_ra_mailman&view=mailshot&layout=edit';
            $target .= '&id=' . $id . '&list_id=' . $list_id;
        }
        $this->setRedirect(Route::_($target, false));
        return $return;
    }

    public function saveContinue($key = null, $urlVar = null) {
        $return = parent::save($key, $urlVar);
        // Get the parameters passed as part of the URL
        $app = Factory::getApplication();
        $id = $app->input->getInt('id', '1');
        $list_id = $app->input->getInt('list_id', '0');
        // Redirect back to edit form
        $target = 'index.php?option=com_ra_mailman&view=mailshot&layout=edit';
        $target .= '&id=' . $id . '&list_id=' . $list_id;
        $this->setRedirect(Route::_($target, false));
        return $return;
    }

    public function showIndividualMailshots() {
        // Show mailshots for given User or Profile

        $objApp = Factory::getApplication();
        $user_id = (int) $objApp->input->getCmd('user_id', 0);
        $user = Factory::getUser($user_id);
        ToolBarHelper::title('Ramblers MailMan');
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.title,r.email,r.ip_address');
        $query->select('r.created, a.mail_list_id');
        $query->select('a.body,a.attachment');
        $query->select("CASE when CHAR_LENGTH(a.attachment) = 0 THEN" .
                " '-' ELSE " .
                "'Y' END as filename");
        $query->from('`#__ra_mail_shots` AS a');

        $query->innerJoin($db->qn('#__ra_mail_recipients') . ' AS `r` ON r.mailshot_id = a.id');

        $query->select('mail_list.name AS `list_name`');
        $query->leftJoin($db->qn('#__ra_mail_lists') . ' AS `mail_list` ON mail_list.id = a.mail_list_id');

        $query->select('u.name AS `modified_by`');
        $query->leftJoin($db->qn('#__users') . ' AS `u` ON u.id = a.created_by');

        $query->where($db->qn('r.user_id') . ' = ' . $user_id);
        $query->order('a.date_sent DESC');

        $mailshots = $this->objHelper->getRows($query);
//        Factory::getApplication()->enqueueMessage('Q=' . $query, 'notice');
        $details = '<h2>Messages sent to User ' . $user_id;
        $details .= ', ' . $user->name . '</h2>';
        echo $details;
        $objTable = new ToolsTable;
        $objTable->add_header("Details,List,Title,Message,File,Author");
        $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showMailshot&id=';
        foreach ($mailshots as $row) {
            $display_date = HTMLHelper::_('date', $row->created, 'H:i:s d/m/Y');
            $details = $this->objHelper->buildLink($target . $row->id, $display_date);
            $objTable->add_item($details . '<br>' . $row->ip_address . '<br>' . $row->email);
            $objTable->add_item($row->list_name);
            $objTable->add_item($row->title);
            $objTable->add_item($row->body);
            $objTable->add_item($row->filename);
            $objTable->add_item($row->modified_by);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        $back = 'administrator/index.php?option=com_ra_mailman&view=profiles';
        echo $this->objHelper->backButton($back);
    }

    public function showMailshot() {
        $objApp = Factory::getApplication();
        $id = (int) $objApp->input->getCmd('id', 0);

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.date_sent,a.mail_list_id');
        $query->select("DATE_FORMAT(a.date_sent, '%d/%b/%y') as sent_date");
        $query->select("DATE_FORMAT(a.date_sent, '%k:%i') as sent_time");
        $query->select("a.title,a.attachment");
        $query->select("CONCAT(l.group_code,' ',l.name) as list");
        $query->select("a.body, a.created, a.created_by, a.modified, a.modified_by");

        $query->from('`#__ra_mail_shots` AS a');
        $query->where('a.id = ' . $id);
        $query->innerJoin('#__ra_mail_lists AS l ON l.id = a.mail_list_id');
        $query->select("u.name as creator");
        $query->leftJoin('#__users AS u ON u.id = a.created_by');
        $query->select("u2.name as updater");
        $query->leftJoin('#__users AS u2 ON u2.id = a.modified_by');
        $db->setQuery($query);
        if (!$item = $db->loadObject()) {
            Factory::getApplication()->enqueueMessage('Unable to find details for ' . $id, 'notice');
            $this->setRedirect('/administrator/index.php?option=com_ra_mailman&view=mailshots');
            return;
        }
        ToolBarHelper::title('Ramblers MailMan');

        echo "<h3>List: " . $item->list . "</h3>";
        echo "<h3>Sent: " . $item->sent_time . " " . $item->sent_date . "</h3>";
        echo "<h2>" . $item->title . "</h2>";

        // Find any more Mailshots

        $sql = 'SELECT id FROM #__ra_mail_shots WHERE mail_list_id=' . $item->mail_list_id;
        $sql .= ' AND id>' . $item->id;
        $sql .= ' ORDER BY id ASC LIMIT 1';
        $next_id = $this->objHelper->getValue($sql);

        $sql = 'SELECT id FROM `#__ra_mail_shots` WHERE mail_list_id=' . $item->mail_list_id;
        $sql .= ' AND id<' . $item->id;
        $sql .= ' ORDER BY id DESC LIMIT 1';
        $prev_id = $this->objHelper->getValue($sql);

        echo "<p>" . $item->body . "</p>";
        if ($item->attachment != '') {
            echo 'Attachment: ' . $this->objHelper->buildLink(Juri::Base() . 'images/com_ra_mailman/' . $item->attachment, $item->attachment, true);
        }
        echo "<p>Created by " . $item->creator . ' at ' . HTMLHelper::_('date', $item->created, 'h:i D d/m/y');
        if (($item->modified_by > 0) AND (HTMLHelper::_('date', $item->created, 'h:i D d/m/y') != HTMLHelper::_('date', $item->modified, 'h:i D d/m/y'))) {
            echo ', Updated by ' . $item->updater . ' at ' . HTMLHelper::_('date', $item->created, 'h:i D d/m/y');
        }
        echo "</p>";
        echo "<p>";

//        echo JSITE_BASE . '<br>';
        $back = 'administrator/index.php?option=com_ra_mailman&view=mailshots&list_id=' . $item->mail_list_id;
        echo $this->objHelper->backButton($back);

        $target = "administrator/index.php?option=com_ra_mailman&task=mailshot.showMailshot&id=";
        if ($prev_id) {
            $prev = $this->objHelper->buildButton($target . $prev_id, "Prev", False, 'grey');
            echo $prev;
        }
        if ($next_id) {
            $next = $this->objHelper->buildButton($target . $next_id, "Next", False, 'teal');
            echo $next;
        }
        echo "<p>";
    }

    public function showRecipients() {
//        $this->objHelper->showQuery('SELECT * FROM #__ra_mail_recipients');
        /*
          $sql = "SELECT r.id, r.user_id, r.email AS recipient, u.name, u.email FROM #__ra_mail_recipients AS r INNER JOIN `#__users` AS u ON r.user_id = u.id"; //WHERE r.email = ''";
          $rows = $this->objHelper->getRows($sql);
          foreach ($rows as $row) {
          $sql = "UPDATE #__ra_mail_recipients SET email='" . $row->email . "' WHERE id=" . $row->id;
          echo "Updating $row->name from $row->recipient $sql<br>";
          $this->objHelper->executeCommand($sql);
          }
         */

        $objApp = Factory::getApplication();
        $id = (int) $objApp->input->getCmd('id', 0);
        $list_id = (int) $objApp->input->getCmd('list_id', 0);

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.date_sent');
//        $query->select("DATE_FORMAT(a.date_sent, '%d/%b/%y') as sent_date");
//        $query->select("DATE_FORMAT(a.date_sent, '%k:%i') as sent_time");
        $query->select("a.processing_started, a.date_sent, a.title");
        $query->select("CONCAT(l.group_code,' ',l.name) as list");
        $query->from('`#__ra_mail_shots` AS a');
        $query->innerJoin('#__ra_mail_lists AS l ON l.id = a.mail_list_id');
        $query->where('a.id = ' . $id);

        $db->setQuery($query);
        $row = $db->loadObject();
        ToolBarHelper::title('Ramblers MailMan');
        echo "<h3>List: " . $row->list . "</h3>";
        echo "<h3>Processing started: " . $row->processing_started;
//        echo ", Completed: " . $row->sent_time . " " . $row->sent_date . "</h3>";
        echo ", Completed: " . $row->date_sent . "</h3>";
        echo "<h2>" . $row->title . "</h2>";

//        $sql = 'SELECT u.name AS Recipient, a.email ';
//        $sql .= 'FROM `#__ra_mail_recipients` AS a ';
//        $sql .= 'LEFT JOIN `#__users` AS u ON u.id = a.user_id ';
//        $sql .= 'WHERE a.mailshot_id = ' . $id;
        $query = $db->getQuery(true);
        $query->select("u.name as Recipient, u.email AS 'user_email', a.email AS 'target_email'");
        $query->select("DATE_FORMAT(a.created, '%d/%b/%y') as sent_date");
        $query->select("DATE_FORMAT(a.created, '%k:%i:%s') as sent_time");
        $query->from('`#__ra_mail_recipients` AS a');
//        $query->innerJoin('#__ra_mail_lists AS l ON l.id = a.mail_list_id');

        $query->leftJoin('#__users AS u ON u.id = a.user_id');
        $query->where('a.mailshot_id = ' . $id);
        $query->order($db->escape('u.username'));

        //      Show link that allows page to be printed
//        $target = "index.php?option=com_ra_mailman&task=mailshot.showRecipients&id=" . $id;
//        echo $this->objHelper->showPrint($target) . '<br>' . PHP_EOL;
//        echo (string) $query;
        $sql = (string) $query;
        $rows = $this->objHelper->getRows($sql);
        $objTable = new ToolsTable;
        $objTable->add_header("Recipient,Date,Time,email");
//        $rows = $db->loadObjectList();
        $count = 0;
        foreach ($rows as $row) {
            $count++;
            $objTable->add_item($row->Recipient);
            $objTable->add_item($row->sent_date);
            $objTable->add_item($row->sent_time);

            $detail = $row->target_email;
            if ($row->target_email != $row->user_email) {
                $detail .= '<br><b>' . $row->user_email . '</b>';
            }
            $objTable->add_item($detail);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        echo $count . ' Recipients<br>';
        echo $this->objHelper->backButton("administrator/index.php?option=com_ra_mailman&view=mailshots&list_id=" . $list_id);
        echo "<p>";
    }

    public function send() {
        $objApp = Factory::getApplication();
        $mailshot_id = (int) $objApp->input->getCmd('mailshot_id', '');

        $user = Factory::getUser();
        $user_id = $user->id;
        if ($user_id == 0) {
            Factory::getApplication()->enqueueMessage('You must log in to access this function', 'error');
        } else {
//            Factory::getApplication()->enqueueMessage('User=' . $user_id, 'notice');
            $objMailHelper = new Mailhelper;
            $objMailHelper->send($mailshot_id);
            Factory::getApplication()->enqueueMessage($objMailHelper->message, 'notice');
        }

        $this->setRedirect('index.php?option=com_ra_mailman&view=mail_lsts');
    }

}
