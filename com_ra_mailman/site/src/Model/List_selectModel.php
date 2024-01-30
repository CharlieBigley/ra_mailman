<?php

/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
  UPDATE dev_ra_events SET modified_by = 980 WHERE id =1;
  UPDATE dev_ra_events SET modified_by = 981 WHERE id =2;
  UPDATE dev_ra_events SET modified_by = 982 WHERE id =3;
  UPDATE dev_ra_events SET modified_by = 983 WHERE id =4;
  UPDATE dev_ra_events SET modified_by = 984 WHERE id =5;
  UPDATE dev_ra_events SET modified_by = 985 WHERE id =6;
  UPDATE dev_ra_events SET modified_by = 985 WHERE id =7;
  UPDATE dev_ra_events SET modified_by = 987 WHERE id =8;
  UPDATE dev_ra_events SET modified_by = 988 WHERE id =9;
  UPDATE dev_ra_events SET modified_by = 989 WHERE id =10;
  UPDATE dev_ra_events SET modified_by = 990 WHERE id =11;
 */

namespace Ramblers\Component\Ra_mailman\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use \Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Methods supporting a list of User_select records.
 *
 * @since  1.0.3
 */
class List_selectModel extends ListModel {

    protected $list_id;
    protected $search_columns;

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
                'a.group_code',
                'a.name',
                'g.name',
                'a.record_type',
                'a.home_group_only',
            );
            $this->search_columns = $config['filter_fields'];
        }

        parent::__construct($config);
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
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery
     *
     * @since   1.0.3
     */
    protected function getListQuery() {
        // user_id will have been passed as a parameter
        // It identifies the person for whom lists are being selected
        $query = $this->_db->getQuery(true);
        $query->select('a.id, a.state, a.ordering');
        $query->select('a.name, a.group_code, a.record_type');
        $query->select('a.home_group_only, a.footer');
        $query->select('a.owner_id');
        $query->select("CASE WHEN a.record_type = 'O' THEN 'Open' ELSE 'Closed' END AS 'list_type'");
        $query->select("CASE WHEN a.home_group_only = 1 THEN 'Yes' ELSE 'No' END AS 'home_only'");

        $query->from('#__ra_mail_lists AS a');
        $query->select('g.name AS `owner`');
        $query->leftJoin($this->_db->qn('#__users') . ' AS `g` ON g.id = a.owner_id');

        $query->where('a.state = 1');
        /*
         * Should only show lists "home group only=1" for the users home group
         * ((a.group = $this->home_group) OR (a.home_group_only=0))
         *
         */
        // Search for this word
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            // Build the search query from the search word and search columns
            $query = ToolsHelper::buildSearchQuery($search, $this->search_columns, $query);
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'p.preferred_name');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        if ($orderCol && $orderDirn) {
            $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
            if ($orderCol == 'a.group_code') {
                $query->order('a.name ASC');
            } elseif ($orderCol == 'a.name') {
                $query->order('a.group_code ASC');
            }
        }
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage($this->_db->replacePrefix($query), 'message');
        }
        return $query;
    }

    // Copied from J3 - is it used?
    public function getMessage() {
        return $this->message;
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
     * @since   1.0.3
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');


        return parent::getStoreId($id);
    }

    protected function lookupUser($id) {
        $objHelper = new ToolsHelper;
        $sql = ' SELECT username FROM #__users WHERE id=' . $id;
        return $objHelper->getValue($sql);
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
        parent::populateState('name', 'ASC');

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

    public function subscribeAll($primary_keys) {
        $app = Factory::getApplication();
        $list_id = $app->getUserState('list_id');
        $record_type = $app->getUserState('record_type');

//        JFactory::getApplication()->enqueueMessage('Subscribe multiple:' . $list_id, 'comment');
        $objMailHelper = new Mailhelper;
        $message = '';
        $error = false;
        foreach ($primary_keys as $user_id) {
            // echo $user_id
            $sub = $objMailHelper->getSubscription($list_id, $user_id);
            if ($sub) {
                if ($sub->state == 1) {
                    Factory::getApplication()->enqueueMessage('id=' . $user_id, 'error');
                    if ($message == '') {
                        $message .= $this->lookupUser($user_id);
                    } else {
                        $message .= ', ' . $this->lookupUser($user_id);
                    }
//                    $message .= 'User ' . $user_id . $this->lookupUser($user_id) . ' already subscribed to list ' . $list_id;
                    $error = true;
                }
            }
        }
//        if ($error) {
        $this->message = $message . '  already subscribed';
//        }
        return false;

        foreach ($primary_keys as $user_id) {
            $result = $objMailHelper->subscribe($this->list_id, $user_id, $this->record_type, 2);
            $message .= $objMailHelper->message;
        }
        Factory::getApplication()->enqueueMessage($message, 'notice');
    }

}
