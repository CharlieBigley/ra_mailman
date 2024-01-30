<?php

/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Methods supporting a list of Subscriptions records.
 *
 * @since  1.0.4
 */
class SubscriptionsModel extends ListModel {

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see        JController
     * @since      1.6
     */
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'l.group_code',
                'l.name',
                'u.name',
                'm.name',
                'a.state',
                'a.modified',
                'a.expiry_date',
                'a.ip_address',
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   Elements order
     * @param   string  $direction  Order direction
     *
     * @return void
     *
     * @throws Exception
     */
    protected function populateState($ordering = null, $direction = null) {
        // List state information.
        parent::populateState('subscriber', 'ASC');

        $context = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $context);

        // Split context into component and optional section
        if (!empty($context)) {
            $parts = FieldsHelper::extract($context);

            if ($parts) {
                $this->setState('filter.component', $parts[0]);
                $this->setState('filter.section', $parts[1]);
            }
        }
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string A store id.
     *
     * @since   1.0.4
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');


        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery
     *
     * @since   1.0.4
     */
    protected function getListQuery() {
        // Create a new query object.
        $query = $this->_db->getQuery(true);

        $query->select('a.id, a.state, a.ip_address, a.user_id, a.state');
        $query->select("CASE WHEN a.state = 0 THEN 'Inactive' ELSE 'Active' END AS 'Status'");
        $query->select('a.method_id, a.created, a.modified,a.expiry_date');
        $query->select('u.name AS `subscriber`');
        $query->select('l.name AS `list`, l.id as list_id');
        $query->select('l.group_code AS `group`');
        $query->select('m.name AS `Method`');
//        $query->select("CASE WHEN a.record_type=3 THEN 'Owner' WHEN a.record_type=2 THEN 'Author' ELSE 'Subscriber' END AS `Access`");
        $query->select("ma.name `Access`");
        $query->from('`#__ra_mail_subscriptions` AS a');
        $query->innerJoin($this->_db->qn('#__ra_mail_methods') . ' AS `m` ON m.id = a.method_id');
        $query->leftJoin($this->_db->qn('#__users') . ' AS `u` ON u.id = a.user_id');
        $query->leftJoin($this->_db->qn('#__ra_mail_lists') . ' AS `l` ON l.id = a.list_id');
        $query->leftJoin($this->_db->qn('#__ra_mail_access') . ' AS `ma` ON ma.id = a.record_type');


        // Filter by published state
        $published = $this->getState('filter.state');

        if (is_numeric($published)) {
            $query->where('a.state = ' . (int) $published);
        } elseif (empty($published)) {
            $query->where('(a.state IN (0, 1))');
        }
        $search_fields = array(
            'l.group_code',
            'l.name',
            'u.name',
            'm.name',
            'a.ip_address',
        );
        // Filter by search
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $query = ToolsHelper::buildSearchQuery($search, $search_fields, $query);
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'l.group_code');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        if ($orderCol && $orderDirn) {
            $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
            if ($orderCol == 'l.group_code') {
                $query->order('l.name ASC');
                $query->order('u.name ASC');
                //               } elseif ($orderCol == 'a.name') {
                //                   $query->order('a.group_code ASC');
            }
        }
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage($this->_db->replacePrefix($query), 'message');
        }
        return $query;
    }

    /**
     * Get an array of data items
     *
     * @return mixed Array of data items on success, false on failure.
     */
    public function getItems() {
        $items = parent::getItems();
        return $items;
    }

    /**
     * Subscribe an array of Users
     * This is invoked from the "Subscribe" button generated by view User_select,
     * even thogh the task is given as subscribe, not subscribeAll
     *
     * @return always returns true
     */
    public function subscribeAll($primary_keys) {
        $objMailhelper = new Mailhelper;
        $method = '2';  // Administrator
        // Retrieve the list id saved by the View
        $list_id = Factory::getApplication()->getUserState('com_ra_mailman.user_select.user_id', 0);
        $record_type = Factory::getApplication()->getUserState('com_ra_mailman.user_select.record_type', 1);
        foreach ($primary_keys as $user_id) {
            $user_name = $objMailhelper->lookupUser($user_id);
            $subscription = $objMailhelper->getSubscription($list_id, $user_id);
            if ($subscription) {

                Factory::getApplication()->enqueueMessage($user_name . ' already subscribed', 'error');
            } else {
//                echo $user_id . '<br>';
                $result = $objMailhelper->subscribe($list_id, $user_id, $record_type, $method);
                //Factory::getApplication()->enqueueMessage($user_id . ' ' . $record_type, 'message');
                //Factory::getApplication()->enqueueMessage($objMailhelper->message, 'info');
                Factory::getApplication()->enqueueMessage($user_name . ' has been subscribed', 'message');
            }
        }
        //       die();
        return true;
    }

}
