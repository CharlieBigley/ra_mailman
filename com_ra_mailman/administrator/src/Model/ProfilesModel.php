<?php

/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 27/08/23 CB Don't show blocked users
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
 * Methods supporting a list of Profiles records.
 *
 * @since  4.0.0
 */
class ProfilesModel extends ListModel {

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
                'u.id',
                'u.email',
                'u.registerDate',
                'u.lastvisitDate',
                'u.block',
                'p.home_group',
                'p.preferred_name',
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
        parent::populateState('preferred_name', 'ASC');

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
     * @since   4.0.0
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
     * @since   4.0.0
     */
    protected function getListQuery() {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $this->_db->getQuery(true);

        $query->select('p.id, p.home_group, p.preferred_name');
        //       $query->select('p.group_code');
        $query->select('u.id as user_id, u.name, u.email');
        $query->select(' u.block, u.registerDate, u.lastvisitDate');
        $query->from('`#__users` AS u');

        $query->leftJoin($this->_db->qn('#__ra_profiles') . ' AS `p` ON p.id = u.id');
//      Don't show blocked users
        $query->where($this->_db->qn('u.block') . '= 0');
        // Search for this word
        $searchWord = $this->getState('filter.search');

        // Search in these columns
        // Fileds specifie here mustb also be defined in the construct function
        $searchColumns = array(
            'u.name',
            'u.email',
            'p.home_group',
        );

        if (!empty($searchWord)) {
            if (stripos($searchWord, 'id:') === 0) {
                // Build the ID search
                $idPart = (int) substr($searchWord, 3);
                $query->where($this->_db->qn('p.id') . ' = ' . $this->_db->q($idPart));
            } else {
                // Build the search query from the search word and search columns
                $query = ToolsHelper::buildSearchQuery($searchWord, $searchColumns, $query);
            }
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        if ($orderCol && $orderDirn) {
            $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
        }
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage('sql = ' . (string) $query, 'notice');
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

        foreach ($items as $oneItem) {
            $oneItem->privacy_level = !empty($oneItem->privacy_level) ? Text::_('COM_RA_PROFILE_PROFILES_PRIVACY_LEVEL_OPTION_' . strtoupper(str_replace(' ', '_', $oneItem->privacy_level))) : '';
        }

        return $items;
    }

}
