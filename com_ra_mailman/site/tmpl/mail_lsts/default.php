<?php
/**
 * @version    4.0.13
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 14/11/23 CB pass menu_id to mailshots list
 * 21/11/23 CB only count mailshots that have been sent
 * 16/01/24 CB show button for owner to display subscribers
 * 27/01/24 CB use mailhelper / countMailshots
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\User\UserFactoryInterface;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$objMailHelper = new Mailhelper;
$objHelper = new ToolsHelper;
$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canCreate = $user->authorise('core.create', 'com_ra_mailman') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'mail_lstform.xml');
$canEdit = $user->authorise('core.edit', 'com_ra_mailman') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'mail_lstform.xml');
$canCheckin = $user->authorise('core.manage', 'com_ra_mailman');
$canChange = $user->authorise('core.edit.state', 'com_ra_mailman');
$canDelete = $user->authorise('core.delete', 'com_ra_mailman');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
      name="adminForm" id="adminForm">
          <?php
          if (!empty($this->filterForm)) {
              echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
          }
          ?>
    <div class="table-responsive">
        <table class="table table-striped mintcake" id="mail_lstList">
            <?php
            echo '<thead>';
            echo '<tr>';

            echo '<th class="left">';
            echo HTMLHelper::_('searchtools.sort', 'Group', 'a.group_code', $listDirn, $listOrder);
            echo '</th>';

            echo '<th class="left">';
            echo HTMLHelper::_('searchtools.sort', 'Name', 'a.name', $listDirn, $listOrder);
            echo '</th>';

            echo '<th class="left">';
            echo HTMLHelper::_('searchtools.sort', 'Owner', 'a.owner_id', $listDirn, $listOrder);
            echo '</th>';

            echo '<th class="left">';
            echo HTMLHelper::_('searchtools.sort', 'Type', 'a.record_type', $listDirn, $listOrder);
            echo '</th>';

            echo '<th class="left">';
            echo HTMLHelper::_('searchtools.sort', 'Home group only', 'a.home_group_only', $listDirn, $listOrder);
            echo '</th>';

            echo '<th class="left">';
            echo 'Subscribers';
            echo '</th>';

            echo '<th class="left">';
            echo 'Mailshots';
            echo '</th>';

            echo '<th class="left">';
            echo 'Last sent';
            echo '</th>';

            echo '<th class="left">';
            echo 'Actions';
            echo '</th>';

            echo '</tr>';
            echo '</thead>';
            ?>
            <tfoot>
                <tr>
                    <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                        <div class="pagination">
                            <?php echo $this->pagination->getPagesLinks(); ?>
                        </div>
                    </td>
                </tr>
            </tfoot>
            <tbody>
                <?php
                foreach ($this->items as $i => $item) {
                    // See if unsent mailshot is present
                    $last_sent = $objMailHelper->lastSent($item->id);
                    $mailshot_id = $objMailHelper->outstanding($item->id);
                    $unsent_mailshot = false;
                    $isAuthor = $objMailHelper->isAuthor($item->id);
                    if (($mailshot_id > 0) AND ($isAuthor)) {
                        $unsent_mailshot = true;
                    } else {
                        $unsent_mailshot = false;
                    }

                    echo '<tr class="row' . $i % 2 . '">';
                    echo '<td>' . $item->group_code . '</td>';
                    echo '<td>' . $item->name . '</td>';
                    echo '<td>' . $item->owner . '</td>';
                    echo '<td>' . $item->list_type . '</td>';     // Open or Closed
                    echo '<td>' . $item->public . '</td>';        // Open to other groups?
                    // Find the number of subscribers to this list
                    // If subs are present for a non-existentuser, they will be counted here
                    echo '<td>';
                    $sql = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions ';
                    $sql .= 'WHERE state=1 AND list_id=' . $item->id;
                    $count = $objHelper->getValue($sql);
                    // Allow the owner of the list to see who the subscribers are
                    if ($count > 0) {
                        echo $count;
                        if ($item->owner_id == $this->user_id) {
                            $target = 'index.php?option=com_ra_mailman&task=mail_lst.showSubscribers&list_id=' . $item->id . '&Itemid=' . $this->menu_id;
                            echo $objHelper->imageButton('I', $target);
                        }
                    }
                    echo '</td>';

                    echo '<td>';
//                    $sql = 'SELECT COUNT(a.id) FROM #__ra_mail_shots AS a ';
//                    $sql .= 'LEFT JOIN `#__ra_mail_lists` AS `m` ON m.id = a.mail_list_id ';
//                    $sql .= 'WHERE a.mail_list_id=' . $item->id;
//                    $sql .= ' AND a.date_sent IS NOT NULL';
//                    $count = $objHelper->getValue($sql);
                    $count = $objMailHelper->countMailshots($item->id, True);
                    if ($count > 0) {
                        echo $count;
                        $target = 'index.php?option=com_ra_mailman&view=mailshots&list_id=' . $item->id . '&Itemid=' . $this->menu_id;
                        echo $objHelper->imageButton('I', $target);
                    }
                    if ($unsent_mailshot == true) {
                        $target = 'index.php?option=com_ra_mailman&task=mailshot.send&mailshot_id=' . $mailshot_id . '&menu_id=' . $this->menu_id;
                        echo $objHelper->buildButton($target, 'Send', False, 'red');
                    }
                    echo '</td>';

                    echo '<td>';
                    echo $last_sent;

                    echo '</td>';
                    /*
                      echo '<td>';
                      if ($draft > 0) {
                      echo 'Y';
                      }
                      echo '<td>'*
                     */
                    echo '<td>';
                    // Actions are determined by a function in the View itself
                    $actions = $this->defineActions($item->id, $item->list_type, $unsent_mailshot, $mailshot_id);

                    echo $actions;
                    echo '</td>';

                    echo '</tr>';
                    echo '';
                }  // end foreach
                ?>
            </tbody>
        </table>
    </div>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="filter_order" value=""/>
    <input type="hidden" name="filter_order_Dir" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
