<?php

/**
 * @version    4.0.9
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 18/10/23 CB take files from images/com_ra_mailman
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Filter\OutputFilter;
use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Mail_lst model.
 *
 * @since  1.0.6
 */
class DataloadModel extends AdminModel {

    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  1.0.6
     */
    protected $text_prefix = 'RA Mailman';

    /**
     * @var    string  Alias to manage history control
     *
     * @since  1.0.6
     */
    public $typeAlias = 'com_ra_mailman.dataload';

    /**
     * @var    null  Item data
     *
     * @since  1.0.6
     */
    protected $item = null;

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   1.0.6
     */
    public function getTable($type = 'Mail_lst', $prefix = 'Administrator', $config = array()) {
        //       die('Model getting table ' . $type);
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   1.0.6
     */
    public function getForm($data = array(), $loadData = true) {
        // Initialise variables.
        $app = Factory::getApplication();

        // Get the form.
        $form = $this->loadForm(
                'com_ra_mailman.dataload',
                'dataload',
                array(
                    'control' => 'jform',
                    'load_data' => $loadData
                )
        );



        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.6
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_ra_mailman.edit.dataload.data', array());

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
        }

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array $data The form data
     *
     * @return  bool
     *
     * @throws  Exception
     * @since   4.0.0
     */
    public function save($data) {
        // This overloads the Joomla updating
        //         // Check for request forgeries.
//        Session::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $user = Factory::getApplication()->getIdentity();

        // Check the user can create new items in this section
        $authorised = $user->authorise('core.create', 'com_ra_mailman');

        if ($authorised !== true) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // See https://docs.joomla.org/Basic_form_guide
        $context = 'com_ra_mailman.default.dataload.data';
        $app = Factory::getApplication();

//        $objHelper = new ToolsHelper;
        $input = Factory::getApplication()->input;

        // get the data from the HTTP POST request
        $data = $input->get('jform', array(), 'array');
        $form = $this->getForm($data, false);

        // This is validate() from the FormModel class, not the Form class
        // FormModel::validate() calls both Form::filter() and Form::validate() methods

        $validData = $this->validate($form, $data);

        // Save the form data in the session, using the same identifier as in the model
        $app->setUserState($context, $data);
        if ($validData === false) {
            $errors = $this->getErrors();

            foreach ($errors as $error) {
                if ($error instanceof \Exception) {
                    $app->enqueueMessage($error->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($error, 'warning');
                }
            }
            $this->setRedirect(Route::_($back, false));
        }

        $method_id = $data['data_type'];
        $processing = $data['processing'];
        $list_id = $data['mail_list'];

        // see https://www.techfry.com/joomla/how-to-handle-files-in-joomla
        //  get array of all files information
        $files = $input->files->get('jform');
//        print_r($files);
        // we are only interested in the first occurrence
        $file = $files['csv_file'];

        $filename = File::makeSafe($file['name']);
        if ($filename == '') {
            Factory::getApplication()->enqueueMessage('Please select csv file to be processed', 'Error');

            return false;
        }
        // Save the filename in State
        $app->setUserState($context . '.file', $filename);
        $error = $file['error'];
        if ($error > 0) {
            Factory::getApplication()->enqueueMessage("Error:" . $error . ' for file ' . $filename, 'Error');
            return false;
        }
        $file_type = $file['type'];
        if (($file_type != 'text/plain') AND ($file_type != 'text/csv')) {
            Factory::getApplication()->enqueueMessage('File ' . $filename . ' is not valid CSV file (' . $file_type . ')', 'Error');
            return false;
        }
//        echo '<h4>' . $filename . '</h4>';
//        return false;
//        echo 'tmp_path=' . $file['tmp_name'] . '<br>';
        // Set up the source and destination of the file

        $working_file = JPATH_SITE . "/images/com_ra_mailman/" . $filename;

        if (File::upload($file['tmp_name'], $working_file)) {
            echo "Size of $filename is " . $file['size'] . ' bytes<br>';
        } else {
            echo 'File could not be uploaded';
            return false;
        }

        $objUserHelper = new UserHelper;
        $objUserHelper->method_id = $method_id;
        $objUserHelper->list_id = $list_id;
        $objUserHelper->processing = $processing;
        $objUserHelper->filename = $working_file;


//        $objUserHelper->purgeTestData();   // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<



        $response = $objUserHelper->processFile();
        return $response;
    }

}
