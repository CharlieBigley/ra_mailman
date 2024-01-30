<?php

/**
 * 4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 25/07/23
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
 * Methods supporting a list of Mail_lsts records.
 *
 * @since  1.0.6
 */
class Mail_lstsModel extends ListModel {

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
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'a.group_code',
                'a.name',
                'g.name',
                'a.record_type',
                'a.home_group_only',
                'a.state',
            );
            $this->search_fields = $config['filter_fields'];
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
        parent::populateState('a.name', 'ASC');

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
     * @since   1.0.6
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
     * @since   1.0.6
     */
    protected function getListQuery() {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select('a.id, a.state, a.ordering');
        $query->select('a.name, a.group_code, a.record_type');
        $query->select('a.owner_id, a.home_group_only, a.group_primary');
        $query->select('CASE WHEN record_type ="C" THEN "Closed" ELSE "Open" END as "Type"');
        $query->select("CASE WHEN a.state = 0 THEN 'Inactive' ELSE 'Active' END AS 'Status'");
        $query->select('CASE WHEN home_group_only =1 THEN "Yes" ELSE "No" END as "HomeGroup"');
        $query->from('#__ra_mail_lists AS a');
        $query->select('g.name AS `owner`');
        $query->leftJoin($this->_db->qn('#__users') . ' AS `g` ON g.id = a.owner_id');

        // Filter by published state
        $published = $this->getState('filter.state');

        if (is_numeric($published)) {
            $query->where('a.state = ' . (int) $published);
        } elseif (empty($published)) {
            $query->where('(a.state IN (0, 1))');
        }

        // Filter by search in title
        $searchWord = $this->getState('filter.search');

        if (!empty($searchWord)) {
            if (stripos($searchWord, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($searchWord, 3));
            } else {
                $query = ToolsHelper::buildSearchQuery($searchWord, $this->search_fields, $query);
            }
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
            if ($orderCol && $orderDirn) {
                $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
                if ($orderCol == 'a.group_code') {
                    $query->order('a.name ASC');
                } elseif ($orderCol == 'a.name') {
                    $query->order('a.group_code ASC');
                }
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

}
