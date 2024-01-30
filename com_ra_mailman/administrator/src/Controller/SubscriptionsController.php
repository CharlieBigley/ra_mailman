<?php

/**
 * @version    4.0.13
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 08/01/24 CB use standard css
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Utilities\ArrayHelper;
use \Ramblers\Component\Ra_mailman\Site\Helpers\MailHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Subscriptions list controller class.
 *
 * @since  1.0.4
 */
class SubscriptionsController extends AdminController {

    function __construct() {
        parent::__construct();
//        $this->objHelper = new ToolsHelper;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    public function cancel2($key = null, $urlVar = null) {
        // temp test from button on list_select
        die('cancel2');
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    /**
     * Method to publish existing Subscriptions
     *
     * @return  void
     *
     * @throws  Exception
     */
    public function publish() {
        // Check for request forgeries
        $this->checkToken();

        // Get id
        $pks = $this->input->post->get('cid', array(), 'array');
        $id = $pks[0];
        try {
            if ($id == 0) {
                throw new \Exception(Text::_('No subscription selected'));
            }
            $objMailHelper = new MailHelper;
            $result = $objMailHelper->resubscribe($id);
            $this->setMessage($objMailHelper->message);
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        $this->setRedirect('index.php?option=com_ra_mailman&view=subscriptions');
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
     * @since   1.0.4
     */
    public function getModel($name = 'Subscriptions', $prefix = 'Administrator', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function forceRenewal() {
        // Invoked from list of subscriptions, reset renewal date to force user to renew
        $list_id = Factory::getApplication()->input->getInt('list_id', '0');
        $user_id = Factory::getApplication()->input->getInt('user_id', '0');
        $objSubscription = new SubscriptionHelper;
        $objSubscription->forceRenewal($list_id, $user_id);
        $this->setRedirect('/administrator/index.php?option=com_ra_mailman&view=subscriptions');
    }

    public function showAudit() {
        $id = Factory::getApplication()->input->getInt('id', '0');
        ToolBarHelper::title('Audit records' . $id);

        $objHelper = new ToolsHelper;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
//        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $sql = "SELECT u.name, l.name as 'list', l.group_code FROM #__ra_mail_subscriptions AS a ";
        $sql .= "LEFT JOIN `#__ra_mail_lists` AS l ON l.id = a.list_id ";
        $sql .= "LEFT JOIN `#__users` AS u ON u.id = a.user_id ";
        $sql .= "WHERE a.id=$id";
        $db->setQuery($sql);
        $db->execute();
        $item = $db->loadObject();
        echo 'Name: <b>' . $item->name . '</b><br>';
        echo 'List: <b>' . $item->group_code . ' ' . $item->list . '</b><br>';

        $sql = "SELECT date_format(a.created,'%d/%m/%y') as 'Date', ";
        $sql .= "time_format(a.created,'%H:%i') as 'Time', ";
        $sql .= "a.field_name, a.old_value, ";
        $sql .= "a.new_value, a.ip_address, ";
        $sql .= "u.name ";
        $sql .= "FROM #__ra_mail_subscriptions_audit AS a ";
        $sql .= "LEFT JOIN `#__users` AS u ON u.id = a.created_by ";
        $sql .= "WHERE object_id=$id ORDER BY created DESC";

        $db->setQuery($sql);
        $db->execute();
        $rows = $db->loadObjectList();
        echo "<br>ShowAudit<br>";
        $objTable = new ToolsTable;
        $objTable->add_header("User,Date,Time,Field,Old,New,IP Address");
        foreach ($rows as $row) {
            $objTable->add_item($row->name);
            $objTable->add_item($row->Date);
            $objTable->add_item($row->Time);
            $objTable->add_item($row->field_name);
            $objTable->add_item($row->old_value);
            $objTable->add_item($row->new_value);
            $objTable->add_item($row->ip_address);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        $back = '/administrator/index.php?option=com_ra_mailman&view=subscriptions';
        echo $objHelper->backButton($back);
    }

    public function subscribe() {
        // not invoked - see maillist.subscribe
        die('SubsController: Subscribe:');
        // Get the input
        $input = Factory::getApplication()->input;
        $primary_keys = $input->post->get('cid', array(), 'array');

        // Sanitize the input
        ArrayHelper::toInteger($primary_keys);

//      Get the model
        $model = $this->getModel();
        $response = $model->subscribeAll($primary_keys);
//        if (!$response) {
//            //Factory::getApplication()->enqueueMessage($model->getMessage(), 'error');
//            Factory::getApplication()->enqueueMessage('response from model ' . $response, 'error');
//        }
        // Retrieve the list id save by the View
        $list_id = Factory::getApplication()->getUserState('com_ra_mailman.user_select.user_id', 0);
        $this->setRedirect('index.php?option=com_ra_mailman&view=user_select&list_id=' . $list_id);
    }

}
