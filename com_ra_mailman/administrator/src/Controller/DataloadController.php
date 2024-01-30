<?php

/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Processing to create the User records is done in the save function of the model
 *
 * 20/06/23 CB created from MailshotController
 * 01/01/24 CB comments added
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
//use Joomla\CMS\Language\Multilanguage;
//use Joomla\CMS\Language\Text;
//use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use \Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Dataload controller class.
 *
 * @since  1.0.2
 */
class DataloadController extends FormController {

    protected $view_item = 'dataload';
// Ensure control returns to Dashboard, not Mailshots
    protected $view_list = 'dashboard';

    public function apply($key = null, $urlVar = null) {
        die('controller/Apply');
    }

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    public function debug($key = null, $urlVar = null) {
        Factory::getApplication()->enqueueMessage('Debug: ', 'comment'); // list_id=' . $id . ', list_id=' . $list_id, 'comment');
    }

    /*
      public function edit($key = null, $urlVar = null) {
      $objApp = Factory::getApplication();
      $id = (int) $objApp->input->getCmd('id', '0');
      $list_id = (int) $objApp->input->getCmd('list_id', '');
      //        Factory::getApplication()->enqueueMessage('Edit: list_id=' . $id . ', list_id=' . $list_id, 'comment');
      $this->setRedirect('index.php?option=com_ra_mailman&view=mailshot&task=display&layout=edit&id=' . $id . '&list_id=' . $list_id);
      //        $return = parent::edit($key, $urlVar);
      }

      public function save($key = null, $urlVar = null) {

      //        if (JDEBUG) {
      //            JLog::add("[controller][mailshot] call to save the selected item", JLog::DEBUG, "com_ra_mailman");
      //        }
      $return = parent::save($key, $urlVar);

      return $return;
      }
     */

    public function process($key = null, $urlVar = null) {
        $app = Factory::getApplication();

        //       $app->enqueueMessage('Controller: process invoked ', 'info');

        $input = $app->input;
        // get the data from the HTTP POST request
        $data = $input->get('jform', array(), 'array');

        /*
          //  get array of all files information
          $files = $input->files->get('jform');
          //        print_r($files);
          // we are only interested in the first occurrence
          $file = $files['csv_file'];

          $data = JRequest::getVar( 'jform', null, 'post', 'array' );
          $data['csv_file'] = strtolower( $filename );
          JRequest::setVar('jform', $data );
         */

        // Save the form data in the session, using the same identifier as in the model
        $context = 'com_ra_mailman.edit.dataload.data';   // as used in the model
        $app->setUserState($context, $data);
        $back = '/administrator/index.php?option=com_ra_mailman&view=dataload';


        $return = parent::save($key, $urlVar);

        if (($return) and ($data['data_type'] == '1')) {
            // clear the data in the form
            $app->setUserState($context, null);
//            $this->setRedirect('/administrator/index.php?option=com_ra_mailman&view=profiles');
//        } else {
//            $this->setRedirect($back);
        }
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
        // This writes to the screen, so cannot redirect
        $objHelper = new ToolsHelper;
        $target = 'administrator/index.php?option=com_ra_mailman&view=dataload';
        echo $objHelper->backButton($target);
        die();
        return false;
        //       $target = "administrator/index.php?option=com_ra_tools&view=dashboard";
        //       echo $objHelper->buildLink($target, "Dashboard", false, "btn btn-small button-new");
    }

}
