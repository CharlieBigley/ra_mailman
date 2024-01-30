<?php
/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 22/12/23 CB remove spurious character
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use \Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$objMailHelper = new Mailhelper;
$self = 'index.php?option=com_ra_mailman&view=user_select';
$self .= '&record_type=' . $this->record_type . '&list_id=' . $this->list_id;
?>

<form action="<?php echo Route::_($self); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table mintcake table-striped" id="userselectList">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">
                                <input type="checkbox" autocomplete="off" class="form-check-input" name="checkall-toggle" value=""
                                       title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Group', 'a.home_group', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Name', 'p.preferred_name', $listDirn, $listOrder); ?>
                            </th>

                            <?php
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Email', 'u.email', $listDirn, $listOrder) . '</th>';
                            echo '<th class="left">Subscriptions</th>';
                            echo '<th class="left">Access</th>';
                            echo '<th class="left">Method</th>';
                            echo '<th class="left">Action</th>';
                            ?>

                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($this->items as $i => $item) :
                            $action = '';
                            $access = '';
                            $method = '';
                            // Get the owner of this list - cannot change this from here
                            $owner_id = $objMailHelper->getOwner_id($this->list_id);
                            // See if this user is currently subscribed
                            $target = '/administrator/index.php?option=com_ra_mailman&task=mail_lst.';
                            if ($subscription = $objMailHelper->getSubscription($this->list_id, $item->id)) {
                                if ($subscription->state == 0) {
                                    $label = 'Re-instate';
                                    $access = '';
                                    $method = '';
                                    $colour = 'sunrise';
                                } else {
                                    // This user is subscribed to this list
                                    if ($this->record_type > $subscription->record_type) {
                                        $label = 'Upgrade';
                                        $colour = 'orange';
                                    } elseif ($this->record_type < $subscription->record_type) {
                                        $label = 'Downgrade';
                                        $colour = 'mud';
                                    } else {
                                        $label = 'Unsubscribe';
                                        $action = 'un';
                                        $colour = 'rosycheeks';
                                    }
                                    $access = $subscription->Access;
                                    $method = $subscription->Method;
                                    $check_visible = false;
                                }
                            } else {
                                // no subscription record found
                                $label = 'Subscribe';
                                $author = '';
                                $method = '';
                                $colour = 'sunset';
                                $check_visible = true;
                            }
                            $target .= $action . 'subscribe&list_id=' . $this->list_id . '&callback=user_select';
                            $target .= '&record_type=' . $this->record_type . '&user_id=' . $item->id;
                            if (JDEBUG) {
                                $action .= $item->id;
                            }
                            $canCreate = $user->authorise('core.create', 'com_ra_mailman');
                            $canEdit = $user->authorise('core.edit', 'com_ra_mailman');
                            $canCheckin = $user->authorise('core.manage', 'com_ra_mailman');
                            $canChange = $user->authorise('core.edit.state', 'com_ra_mailman');
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group='1' data-transition>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                <?php
                                echo '<td>' . $item->home_group . '</td>';
                                if ($item->preferred_name == '') {
                                    $name = $item->name;
                                } else {
                                    $name = $item->preferred_name;
                                }
                                echo '<td>' . $name . '</td>';
                                echo '<td>' . $this->escape($item->email) . '</td>';

                                // Count how many subscriptions this user has
                                $sql = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions WHERE user_id=' . $item->id;
                                $count = $this->objHelper->getValue($sql);
                                echo '<td>' . $count . '</td>';
                                echo '<td>' . $access . '</td>';
                                echo '<td>' . $method . '</td>';

                                echo '<td>';
                                if (($owner_id == $item->id)) {
                                    echo '<b>Owner</b>';
                                } else {
                                    echo $this->objHelper->buildButton($target, $label, false, $colour);
                                }
                                echo '</td>';

                                echo '</tr>';
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>