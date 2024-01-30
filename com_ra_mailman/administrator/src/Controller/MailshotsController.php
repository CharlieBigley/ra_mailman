<?php

/**
 * @version    4.1.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 30/01/24 CB if showing mailshots for a single list, return to Mailshots view, not dashboard
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
 * Mailshots list controller class.
 *
 * @since  1.0.2
 */
class MailshotsController extends AdminController {

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    public function cancel2($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_mailman&view=mail_lsts');
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
     * @since   1.0.2
     */
    public function getModel($name = 'Mailshot', $prefix = 'Administrator', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

}
