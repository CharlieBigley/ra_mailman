<?php

/*
 * Installation script
 * 01/08/23 CB move from Admin
 * 07/08/23 CB don't check if component is present if uninstalling
 * 24/08/23 CB code for copying cli file
 * 19/08/23 CB correct copying of ra_renewals
 * 14/11/23 CB delete ra_mailshots / author_id
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class Com_Ra_mailmanInstallerScript {

    private $component;
    private $minimumJoomlaVersion = '4.0';
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;

    function checkColumn($table, $column, $mode, $details = '') {
//  $mode = A: add the field
//  $mode = U: update the field
//  $mode = D: delete the field

        $count = $this->checkColumnExists($table, $column);
        $table_name = $this->dbPrefix . $table;
        echo 'mode=' . $mode . ': Seeking ' . $table_name . '/' . $column . ', count=' . $count . "<br>";
        if (($mode == 'A') AND ($count == 1)
                OR ($mode == 'D') AND ($count == 0)) {
            return true;
        }
        if (($mode == 'U') AND ($count == 0)) {
            echo 'Field ' . $column . ' not found in ' . $table_name . '<br>';
            return false;
        }
        $sql = 'ALTER TABLE ' . $table_name . ' ';
        if ($mode == 'U') {
            $sql .= $details;
        } elseif ($mode == 'D') {
            $sql .= 'DROP ' . $column;
        }
        echo "$sql<br>";

        $response = $this->executeCommand($sql);
        if ($response) {
            echo 'Success';
        } else {
            echo 'Failure';
        }
        echo ' for ' . $table_name . '<br>';
        return $count;
    }

    private function checkColumnExists($table, $column) {
        $config = JFactory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $this->dbPrefix . $table . "' ";
        $sql .= "AND COLUMN_NAME='" . $column . "'";
//    echo "$sql<br>";

        return $this->getValue($sql);
    }

    function checkTable($table, $details, $details2 = '') {

        $config = JFactory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $table_name . "' ";
        echo "$sql<br>";

        $count = $this->getValue($sql);
        echo 'Seeking ' . $table_name . ', count=' . $count . "<br>";
        if ($count > 0) {
            return $count;
        }
        $sql = 'CREATE TABLE ' . $table_name . ' ' . $details;
        echo "$sql<br>";
        $response = $this->executeCommand($sql);
        if ($response) {
            echo 'Table created OK';
        } else {
            echo 'Failure';
            return false;
        }
        if ($details2 != '') {
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $details2;
            $response = $this->executeCommand($sql);
            if ($response) {
                echo 'Table altered OK<br>';
            } else {
                echo 'Failure<br>';
                return false;
            }
        }
    }

    private function executeCommand($sql) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->execute();
    }

    public function getDatabaseVersion($component = 'com_ra_mailman') {
// Get the extension ID
        $db = JFactory::getDbo();
        $eid = $this->getExtensionId($component);

        if ($eid != null) {
// Get the schema version
            $query = $db->getQuery(true);
            $query->select('version_id')
                    ->from('#__schemas')
                    ->where('extension_id = ' . $db->quote($eid));
            $db->setQuery($query);
            $version = $db->loadResult();

            return $version;
        }

        return null;
    }

    /**
     * Loads the ID of the extension from the database
     *
     * @return mixed
     */
    public function getExtensionId($component = 'com_ra_mailman') {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);
        $query->select('extension_id')
                ->from('#__extensions')
                ->where($db->qn('element') . ' = ' . $db->q($component) . ' AND type=' . $db->q('component'));
        $db->setQuery($query);
        $eid = $db->loadResult();
//        echo $db->replacePrefix($query) . '<br>';
        return $eid;
    }

    private function getvalue($sql) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->loadResult();
    }

    public function getVersion($component = 'com_ra_mailman') {
        $db = JFactory::getDbo();
        $version = '';
        $extension_id = $this->getExtensionId($component);
        if ($extension_id) {
            $sql = 'SELECT version_id from #__schemas WHERE extension_id=' . $extension_id;
//            echo 'Seeking  ' . $db->replacePrefix($sql) . '<br>';
            $query = $db->getQuery(true);
            $query->select('version_id')
                    ->from('#__schemas')
                    ->where($db->qn('extension_id') . ' = ' . $db->q($extension_id));
            $db->setQuery($query);

//            echo $db->replacePrefix($query) . '<br>';
            $version = $db->loadResult();
        }
        return $version;
    }

    public function install($parent): bool {
        echo '<p>Installing RA MailMan (com_ra_mailman) ' . '</p>';
        if (ComponentHelper::isEnabled('com_ra_tools', true)) {
            echo 'com_ra_tools found, version=' . $this->getVersion('com_ra_tools') . '<br>';
        } else {
            echo 'This component cannot be installed unless component RA Tools (com_ra_tools) is installed first';
            return false;
        }
        return true;
    }

    public function uninstall($parent): bool {
        echo '<p>Uninstalling RA MailMan (com_ra_mailman) version=' . $this->getVersion() . '<br>';
        return true;
    }

    public function update($parent): bool {
        echo '<p>Updating RA MailMan (com_ra_mailman)</p>';
        $extension_id = $this->getExtensionId();
        if ($extension_id) {
            echo '<p>Extension ID=' . $extension_id . '</p>';
            echo 'Version=' . $this->getVersion() . '<br>';
        }
// You can have the backend jump directly to the newly updated component configuration page
// $parent->getParent()->setRedirectURL('index.php?option=com_ra_mailman');
        return true;
    }

    public function postflight($type, $parent) {
        '<p>Postflight RA MailMan (com_ra_mailman)</p>';

        if ($type == 'uninstall') {
            return 1;
        }
        echo '<p>com_ra_mailman is now at ' . $this->getVersion() . '</p>';


        $new_script = JPATH_SITE . "/components/com_ra_mailman/ra_renewals.php";
        $target = JPATH_SITE . '/cli/ra_renewals.php';
        if (file_exists($new_script)) {
            echo 'Copying ' . $new_script . '<br> to ' . $target;
            copy($new_script, $target);
            if (file_exists($target)) {
                echo ' Success<br>';
            } else {
                echo ' Failed<br>';
            }
        } else {
            echo $new_script . ' not found<br>';
        }

//      $this->checkColumn('ra_areas', 'chair_id', 'A', "ADD chair_id INT NOT NULL DEFAULT '0' AFTER cluster; ");
        $this->checkColumn('ra_mail_shots', 'author_id', 'D');
        return true;
    }

    public function preflight($type, $parent): bool {
        echo '<p>Preflight RA MailMan (type=' . $type . ')</p>';

        if ($type == 'uninstall') {
            return true;
        }

        if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
            Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion),
                    Log::WARNING,
                    'jerror'
            );
            return false;
        }
        if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion),
                    Log::WARNING,
                    'jerror'
            );
            return false;
        }

        if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
            echo 'com_ra_mailman already present, version=' . $this->getVersion('com_ra_mailman') . '<br>';
        }
        // Can only be installed if com_ra_tools is already present

        echo '<p>Version ' . $this->getVersion('com_ra_tools') . ' of com_ra_tools found</p>';

        return true;
    }

}
