<?php

/**
 * @version    4.0.13
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 09/06/23 CB added subscribe/unsubscribe
 * 06/01/24 CB cancelSubscription
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;

//use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Subscription controller class.
 *
 * @since  1.0.4
 */
class SubscriptionController extends FormController {

    protected $view_list = 'subscriptions';

    public function cancelSubscription() {
        $id = Factory::getApplication()->input->getInt('id', '1');
        $objSubscription = new SubscriptionHelper;
        $objSubscription->cancelSubscription($id);
        $this->setRedirect('/administrator/index.php?option=com_ra_mailman&view=subscriptions');
    }

}
