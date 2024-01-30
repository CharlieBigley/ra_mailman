<?php

/**
 * @version    4.0.11
 * @package    Com_Ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * 08/08/23 CB create afresh from Mailshotform controller
 * 13/11/23 CB define $objHelper
 * 14/11/23 CB store and pass menu_id
 * 21/11/23 CB use ToolsTable
 * 22/12/23 CB formatting of dates when displaying mailshot
 * 02/01/24 CB correct fomatting of dates
 */

namespace Ramblers\Component\Ra_mailman\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Mailshot class.
 *
 * @since  1.0.2
 */
class MailshotController extends FormController {

    /**
     * Method to abort current operation
     *
     * @return void
     *
     * @throws Exception
     */
    public function cancel($key = NULL) {

        // Get the current edit id.
        $editId = (int) $this->app->getUserState('com_ra_mailman.edit.mailshot.id');

        // Get the model.
        $model = $this->getModel('Mailshot', 'Site');

        // Check in the item
        if ($editId) {
            $model->checkin($editId);
        }

        $menu = Factory::getApplication()->getMenu();
        $item = $menu->getActive();
        $url = (empty($item->link) ? 'index.php?option=com_ra_mailman&view=mailshots' : $item->link);
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Method to check out an item for editing and redirect to the edit form.
     *
     * @return  void
     *
     * @since   1.0.2
     *
     * @throws  Exception
     */
    public function edit($key = NULL, $urlVar = NULL) {
        // Get the previous edit id (if any) and the current edit id.
        $previousId = (int) $this->app->getUserState('com_ra_mailman.edit.mailshot.id');
        $editId = $this->input->getInt('id', 0);

        // Set the user id for the user to edit in the session.
        $this->app->setUserState('com_ra_mailman.edit.mailshot.id', $editId);

        // Get the model.
        $model = $this->getModel('Mailshot', 'Site');

        // Check out the item
        if ($editId) {
            $model->checkout($editId);
        }

        // Check in the previous user.
        if ($previousId) {
            $model->checkin($previousId);
        }

        // Redirect to the edit screen.
        $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mailshot&layout=edit', false));
    }

    /**
     * Function that allows child controller access to model data
     * after the data has been saved.
     *
     * @param   BaseDatabaseModel  $model      The data model object.
     * @param   array              $validData  The validated data.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function postSaveHook(BaseDatabaseModel $model, $validData = array()) {

    }

    /**
     * Method to save data.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   1.0.2
     */
    public function save($key = NULL, $urlVar = NULL) {
        // Check for request forgeries.
        $this->checkToken();

        // Initialise variables.
        $model = $this->getModel('Mailshot', 'Site');

        // Get the user data.
        $data = $this->input->get('jform', array(), 'array');

        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new \Exception($model->getError(), 500);
        }

        // Send an object which can be modified through the plugin event
        $objData = (object) $data;
        $this->app->triggerEvent(
                'onContentNormaliseRequestData',
                array($this->option . '.' . $this->context, $objData, $form)
        );
        $data = (array) $objData;

        // Validate the posted data.
        $data = $model->validate($form, $data);

        // Check for errors.
        if ($data === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $this->app->enqueueMessage($errors[$i], 'warning');
                }
            }

            $jform = $this->input->get('jform', array(), 'ARRAY');

            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.mailshot.data', $jform);

            // Redirect back to the edit screen.
            $id = (int) $this->app->getUserState('com_ra_mailman.edit.mailshot.id');
            $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mailshot&layout=edit&id=' . $id, false));

            $this->redirect();
        }

        // Attempt to save the data.
        $return = $model->save($data);

        // Check for errors.
        if ($return === false) {
            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.mailshot.data', $data);

            // Redirect back to the edit screen.
            $id = (int) $this->app->getUserState('com_ra_mailman.edit.mailshot.id');
            $this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mailshot&layout=edit&id=' . $id, false));
            $this->redirect();
        }

        // Check in the profile.
        if ($return) {
            $model->checkin($return);
        }

        // Clear the mailshot id from the session.
        $this->app->setUserState('com_ra_mailman.edit.mailshot.id', null);

        // Redirect to the list screen.
        if (!empty($return)) {
            $this->setMessage(Text::_('Mailshot updated'));
        }

//        $menu = Factory::getApplication()->getMenu();
//        $item = $menu->getActive();
//        die($item->link);  // this was index.php?option=com_content&view=article&id=17
//        $url = (empty($item->link) ? 'index.php?option=com_ra_mailman&view=mail_lsts' : $item->link);
        $url = 'index.php?option=com_ra_mailman&view=mail_lsts';
        $this->setRedirect(Route::_($url, false));

        // Flush the data from the session.
        $this->app->setUserState('com_ra_mailman.edit.mailshot.data', null);

        // Invoke the postSave method to allow for the child class to access the model.
        $this->postSaveHook($model, $data);
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

    public function showMailshot() {
        $objHelper = new ToolsHelper;
        $objApp = Factory::getApplication();
        $id = (int) $objApp->input->getCmd('id', 0);
        $menu_id = (int) $objApp->input->getCmd('Itemid', 0);

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.date_sent,a.mail_list_id,a.date_sent');
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
        $item = $db->loadObject();
        echo "<h3>List: " . $item->list . "</h3>";
        echo "<h3>Sent: " . $item->sent_time . " " . HTMLHelper::_('date', $item->date_sent, 'D d/m/y') . "</h3>";
        echo "<h2>" . $item->title . "</h2>";
//        echo HTMLHelper::_('date', $item->date_sent, 'h:i D d/m/y');
        // Find any more Mailshots

        $sql = 'SELECT id FROM #__ra_mail_shots WHERE mail_list_id=' . $item->mail_list_id;
        $sql .= ' AND date_sent IS NOT NULL';
        $sql .= ' AND id>' . $item->id;
        $sql .= ' ORDER BY id ASC LIMIT 1';
        $next_id = $objHelper->getValue($sql);

        $sql = 'SELECT id FROM `#__ra_mail_shots` WHERE mail_list_id=' . $item->mail_list_id;
        $sql .= ' AND id<' . $item->id;
        $sql .= ' ORDER BY id DESC LIMIT 1';
        $prev_id = $objHelper->getValue($sql);

        echo "<p>" . $item->body . "</p>";
        if ($item->attachment !== '') {
            echo 'Attachment: ' . $objHelper->buildLink(Juri::Base() . 'images/com_ra_mailman/' . $item->attachment, $item->attachment, true);
        }
        echo "<p>Created by " . $item->creator . ' at ' . HTMLHelper::_('date', $item->created, 'h:i D d/m/y');
        if (($item->modified_by > 0) AND (HTMLHelper::_('date', $item->created, 'h:i D d/m/y') != HTMLHelper::_('date', $item->modified, 'h:i D d/m/y'))) {
            echo ', Updated by ' . $item->updater . ' at ' . HTMLHelper::_('date', $item->modified, 'h:i D d/m/y');
        }
        echo "</p>";
        echo "<p>";

        $back = 'index.php?option=com_ra_mailman&view=mailshots&list_id=' . $item->mail_list_id;
        $back .= '&Itemid=' . $menu_id;
        echo $objHelper->backButton($back);

        $target = "index.php?option=com_ra_mailman&task=mailshot.showMailshot&Itemid=$menu_id&id=";
        if ($prev_id) {
            $prev = $objHelper->buildLink($target . $prev_id, "Prev", False, "link-button button-p0159");
            echo $prev;
        }
        if ($next_id) {
            $next = $objHelper->buildLink($target . $next_id, "Next", False, "link-button button-p0159");
            echo $next;
        }
        echo "<p>";
    }

    public function showRecipients() {
        $objHelper = new ToolsHelper;
        $objApp = Factory::getApplication();
        $id = $objApp->input->getInt('id', 0);
        $list_id = $objApp->input->getInt('list_id', 0);
        $menu_id = $objApp->input->getInt('Itemid', 0);

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
//        ToolBarHelper::title('Ramblers MailMan');
        echo "<h3>List: " . $row->list . "</h3>";
        echo "<h3>Processing started: " . $row->processing_started;
//        echo ", Completed: " . $row->sent_time . " " . $row->sent_date . "</h3>";
        echo ", Completed: " . $row->date_sent . "</h3>";
        echo "<h2>" . $row->title . "</h2>";

//        $sql = 'SELECT u.name AS Recipient, a.email ';
//        $sql .= 'FROM `#__ra_mail_recipients` AS a ';
//        $sql .= 'LEFT JOIN #__users AS u ON u.id = a.user_id ';
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
        $target = 'index.php?option=com_ra_mailman&task=mailshot.showRecipients&id=' . $id;
        $target .= '&list_id=' . $list_id;
        $target .= '&Itemid=' . $menu_id;
        echo $objHelper->showPrint($target) . '<br>' . PHP_EOL;
//        echo (string) $query;
        $sql = (string) $query;
        $rows = $objHelper->getRows($sql);
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
        $back = 'index.php?option=com_ra_mailman&view=mailshots&list_id=' . $list_id;
        $back .= '&Itemid=' . $menu_id;
        echo $objHelper->backButton($back);
        echo "<p>";
    }

}
