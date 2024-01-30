<?php

/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

/**
 * User_select list controller class.
 *
 * @since  1.0.3
 */
class User_selectController extends AdminController {

    protected $view_item = 'profile';
    protected $view_list = 'mail_lsts';

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_mailman&view=mail_lsts');
    }

    /**
     * Method to clone existing User_select
     *
     * @return  void
     *
     * @throws  Exception
     */
    public function duplicate() {
        // Check for request forgeries
        $this->checkToken();

        // Get id(s)
        $pks = $this->input->post->get('cid', array(), 'array');

        try {
            if (empty($pks)) {
                throw new \Exception(Text::_('COM_RA_MAILMAN_NO_ELEMENT_SELECTED'));
            }

            ArrayHelper::toInteger($pks);
            $model = $this->getModel();
            $model->duplicate($pks);
            $this->setMessage(Text::_('COM_RA_MAILMAN_ITEMS_SUCCESS_DUPLICATED'));
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        $this->setRedirect('index.php?option=com_ra_mailman&view=user_select');
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    Optional. Model name
     * @param   string  $prefix  Optional. Class prefix
     * @param   array   $config  Optional. Configuration array for model
     *
     * @return  object	The Model
     *
     * @since   1.0.3
     */
    public function getModel($name = 'Userselect', $prefix = 'Administrator', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function subscribeAll() {
        die('User_selectController');
        // Allow user to select multiple users, and add them all as Subscribers or Authors
//        Factory::getApplication()->enqueueMessage('Subscribe multiple:' . $list_id, 'comment');
        // Get the input
        $input = Factory::getApplication()->input;
        $primary_keys = $input->post->get('cid', array(), 'array');

        // Sanitize the input
        JArrayHelper::toInteger($primary_keys);

//      Get the model
        $model = $this->getModel();
        $response = $model->subscribeAll($primary_keys);
        if (!$response) {
            Factory::getApplication()->enqueueMessage($model->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_ra_mailman&view=user_select');
    }

}
