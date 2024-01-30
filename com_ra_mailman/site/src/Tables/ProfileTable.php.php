<?php

/**
 * @version    4.0.0
 * @package    Com_Ra_profile
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_profile\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Filesystem\File;
use \Joomla\Registry\Registry;
use \Ramblers\Component\Ra_profile\Administrator\Helper\Ra_profileHelper;
use \Joomla\CMS\Helper\ContentHelper;

/**
 * Profile table
 *
 * @since 4.0.0
 */
class ProfileTable extends Table implements VersionableTableInterface, TaggableTableInterface {

    use TaggableTableTrait;

    /**
     * Constructor
     *
     * @param   JDatabase  &$db  A database connector object
     */
    public function __construct(DatabaseDriver $db) {
        $this->typeAlias = 'com_ra_mailam.register';
        parent::__construct('#__ra_profiles', 'id', $db);
    }

    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   4.0.0
     */
    public function getTypeAlias() {
        return $this->typeAlias;
    }

    /**
     * Overloaded bind function to pre-process the params.
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  Optional array or list of parameters to ignore
     *
     * @return  boolean  True on success.
     *
     * @see     Table:bind
     * @since   4.0.0
     * @throws  \InvalidArgumentException
     */
    public function bind($array, $ignore = '') {
        $date = Factory::getDate();
        $task = Factory::getApplication()->input->get('task');
        $user = Factory::getApplication()->getIdentity();

        $input = Factory::getApplication()->input;
        $task = $input->getString('task', '');

        if ($array['id'] == 0) {
            $array['created'] = $date->toSql();
        }

        if ($array['id'] == 0 && empty($array['created_by'])) {
            $array['created_by'] = Factory::getUser()->id;
        }

        if ($array['id'] == 0 && empty($array['modified_by'])) {
            $array['modified_by'] = Factory::getUser()->id;
        }

        if ($task == 'apply' || $task == 'save') {
            $array['modified_by'] = Factory::getUser()->id;
        }

        // Support for checkbox field: acknowledge_follow
        if (!isset($array['acknowledge_follow'])) {
            $array['acknowledge_follow'] = 0;
        }

        if ($task == 'apply' || $task == 'save') {
            $array['modified'] = $date->toSql();
        }

        // Support for multiple field: privacy_level
        if (isset($array['privacy_level'])) {
            if (is_array($array['privacy_level'])) {
                $array['privacy_level'] = implode(',', $array['privacy_level']);
            } elseif (strpos($array['privacy_level'], ',') != false) {
                $array['privacy_level'] = explode(',', $array['privacy_level']);
            } elseif (strlen($array['privacy_level']) == 0) {
                $array['privacy_level'] = '';
            }
        } else {
            $array['privacy_level'] = '';
        }

        // Support for checkbox field: contactviaemail
        if (!isset($array['contactviaemail'])) {
            $array['contactviaemail'] = 0;
        }

        // Support for checkbox field: contactviatextmessage
        if (!isset($array['contactviatextmessage'])) {
            $array['contactviatextmessage'] = 0;
        }

        // Support for checkbox field: notify_joiners
        if (!isset($array['notify_joiners'])) {
            $array['notify_joiners'] = 0;
        }

        if (isset($array['params']) && is_array($array['params'])) {
            $registry = new Registry;
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new Registry;
            $registry->loadArray($array['metadata']);
            $array['metadata'] = (string) $registry;
        }

        if (!$user->authorise('core.admin', 'com_ra_mailman.profile.' . $array['id'])) {
            $actions = Access::getActionsFromFile(
                            JPATH_ADMINISTRATOR . '/components/com_ra_mailman/access.xml',
                            "/access/section[@name='profile']/"
            );
            $default_actions = Access::getAssetRules('com_ra_mailman.profile.' . $array['id'])->getData();
            $array_jaccess = array();

            foreach ($actions as $action) {
                if (key_exists($action->name, $default_actions)) {
                    $array_jaccess[$action->name] = $default_actions[$action->name];
                }
            }

            $array['rules'] = $this->JAccessRulestoArray($array_jaccess);
        }

        // Bind the rules for ACL where supported.
        if (isset($array['rules']) && is_array($array['rules'])) {
            $this->setRules($array['rules']);
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Method to store a row in the database from the Table instance properties.
     *
     * If a primary key value is set the row with that primary key value will be updated with the instance property values.
     * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   4.0.0
     */
    public function store($updateNulls = true) {
        return parent::store($updateNulls);
    }

    /**
     * Overloaded check function
     *
     * @return bool
     */
    public function check() {
        // If there is an ordering column and this is a new row then get the next ordering value
        if (property_exists($this, 'ordering') && $this->id == 0) {
            $this->ordering = self::getNextOrder();
        }



        return parent::check();
    }

}
