<?php

/**
 * @version    4.0.7
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 14/11/23 CB remove reference to author_id
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
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Methods supporting a list of Mailshots records.
 *
 * @since  1.0.2
 */
class MailshotsModel extends ListModel {

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
            // This determined which fields are used for sorting
            $config['filter_fields'] = array(
                'a.date_sent',
                'a.title',
                'mail_list', 'mail_list.name',
                'modified', 'a.modified',
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
        parent::populateState('date_sent', 'DESC');

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
     * @since   1.0.2
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');


        return parent::getStoreId($id);
    }

    public function getTable($type = 'mailshots', $prefix = 'Ra_mailmanTable', $config = array()) {
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  DatabaseQuery
     *
     * @since   1.0.2
     */
    protected function getListQuery() {
        // list_id may have been passed as a parameter to identify the mailing list being queried
        $this->list_id = Factory::getApplication()->input->getInt('list_id', 0);

        $query = $this->_db->getQuery(true);

        $query->select('a.id, a.title, a.body');
        $query->select('a.date_sent, a.mail_list_id, a.state');
        $query->select('a.processing_started');
        $query->select("CASE when CHAR_LENGTH(a.attachment) = 0 THEN" .
                " '-' ELSE " .
                "'Y' END as attachment");
        $query->select('a.created, a.modified');
        $query->select('a.modified_by');

        $query->from('`#__ra_mail_shots` AS a');

        $query->select('mail_list.name AS `list_name`');
        $query->leftJoin($this->_db->qn('#__ra_mail_lists') . ' AS `mail_list` ON mail_list.id = a.mail_list_id');
        $query->select('u.name AS `modified_by`');
        $query->leftJoin($this->_db->qn('#__users') . ' AS `u` ON u.id = a.modified_by');

        // Only ever need published records
//        $query->where($this->_db->qn('a.state') . ' = 1');
        if ($this->list_id > '0') {
            $query->where($this->_db->qn('a.mail_list_id') . ' = ' . $this->_db->q($this->list_id));
        }
        // Search for this word
        $searchWord = $this->getState('filter.search');

        // Search in these columns
        $searchColumns = array(
            'a.title',
            'mail_list.name',
            'a.body',
        );

        if (!empty($searchWord)) {
            // Build the search query from the search word and search columns
            $query = ToolsHelper::buildSearchQuery($searchWord, $searchColumns, $query);
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        /*
          if ($orderCol && $orderDirn) {
          $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
          if ($orderCol == 'a.group_code') {
          $query->order('a.name ASC');
          } elseif ($orderCol == 'a.name') {
          $query->order('a.group_code ASC');
          }
          }

         */
        if ($orderCol && $orderDirn) {
            $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
        } else {
            $query->order($this->_db->escape('date_sent DESC'));
        }
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage('sql=' . (string) $query, 'message');
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
