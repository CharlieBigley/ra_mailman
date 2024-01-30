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
use \Ramblers\Component\Ra_mailman\Administrator\Helpers\BackendHelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Methods supporting a list of Subscriptions records.
 *
 * @since  1.0.4
 */
class SubscriptionsModel extends ListModel {

    protected $search_felds;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see        JController
     * @since      1.6
     */
    public function __construct($config = array()) {
        //       if (empty($config['filter_fields'])) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'l.group_code',
                'l.name', // List
                'u.name', // Subscriber
                'ma.name', // Access
                'u.name', // Subscriber
                'm.name', // Method
//                'Method',
                'a.state', // Status
                'a.modified', // Last updated
                'a.expiry_date',
                'a.ip_address',
            );
            $this->search_felds = $config['filter_fields'];
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
        parent::populateState('l.group_code', 'ASC');

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
     * @return	JDatabaseQuery
     * @since	1.6
     */
    protected function getListQuery() {
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
            $query->where($this->_db->qn('a.state') . ' = ' . (int) $published);
        } else if ($published === '') {
            $query->where('(' . $this->_db->qn('a.state') . ' IN (0, 1))');
        }
        /*
         * https://www.codingace.com/joomla-extdev/adding-search-feature-to-component-frontend-list-view
          if ($filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array'))
          {
          foreach ($filters as $name => $value)
          {
          $this->setState('filter.' . $name, $value);
          }
          }
         */
        // Search for this word
        $searchWord = $this->getState('filter.search');


        if (!empty($searchWord)) {
            if (stripos($searchWord, 'id:') === 0) {
                // Build the ID search
                $idPart = (int) substr($searchWord, 3);
                $query->where($this->_db->qn('a.id') . ' = ' . $this->_db->q($idPart));
            } else {
                // Build the search query from the search word and search columns
//                $query = ToolsHelper::buildSearchQuery($searchWord, $this->search_felds, $query);
                $query = BackendHelper::buildSearchQuery($searchWord, $this->search_felds, $query);
            }
        }

        // Add the list ordering clause, defaut to name ASC
        $orderCol = $this->state->get('list.ordering', 'l.group_code');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        if ($orderCol == 'l.group_code') {
            $orderCol = $this->_db->quoteName('l.group_code') . ' ' . $orderDirn;
            $orderCol .= ', ' . $this->_db->quoteName('l.name');
            $orderCol .= ', ' . $this->_db->quoteName('u.name');
        }

        $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));

//        if ($orderCol && $orderDirn) {
//            $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
//        } else {
//            $query->order('l.group_code, l.name, u.name DESC');
//        }
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

}
