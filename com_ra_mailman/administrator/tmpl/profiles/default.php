<?php
/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 29/08/23 CB remove back button
 * 06/10/23 CB no link for select list if profile not present
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
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('com_ra_tools', 'ramblers.css');

$objHelper = new ToolsHelper;
$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canOrder = $user->authorise('core.edit.state', 'com_ra_mailman');

if (!empty($saveOrder)) {
    $saveOrderingUrl = 'index.php?option=com_ra_mailman&task=profiles.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_ra_mailman&view=profiles'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table table-striped" id="profileList">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">
                                <input type="checkbox" autocomplete="off" class="form-check-input" name="checkall-toggle" value=""
                                       title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
                            </th>

                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Name', 'p.preferred_name', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Home group', 'p.home_group', $listDirn, $listOrder); ?>
                            </th>
                            <?php
                            echo "<th class='left'>";
                            echo HTMLHelper::_('searchtools.sort', 'Email', 'u.email', $listDirn, $listOrder);
                            echo '</th>';

                            echo '<th>Lists</th>';

                            echo '<th>Mailshots</th>';

                            echo "<th class='left'>";
                            echo HTMLHelper::_('searchtools.sort', 'Registered', 'u.registerDate', $listDirn, $listOrder);
                            echo '</th>';

                            echo "<th class='left'>";
                            echo HTMLHelper::_('searchtools.sort', 'Last visit', 'u.lastvisitDate', $listDirn, $listOrder);
                            echo '</th>';

                            echo "<th class='left'>";
                            echo HTMLHelper::_('searchtools.sort', 'id', 'u.id', $listDirn, $listOrder);
                            echo '</th>';
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
                    <tbody <?php if (!empty($saveOrder)) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
                        <?php
                        foreach ($this->items as $i => $item) {
                            $ordering = ($listOrder == 'a.ordering');
                            $canCreate = $user->authorise('core.create', 'com_ra_mailman');
                            $canEdit = $user->authorise('core.edit', 'com_ra_mailman');
                            $canCheckin = $user->authorise('core.manage', 'com_ra_mailman');
                            $canChange = $user->authorise('core.edit.state', 'com_ra_mailman');
                            echo '<tr class="row' . $i % 2 . '" data-draggable-group=\'1\' data-transition>';

                            echo '<td class="text-center">';
                            echo HTMLHelper::_('grid.id', $i, $item->user_id);
                            echo '</td>';

                            echo '<td>';
//                          If no profile is present, take name from User record
                            if ($item->preferred_name == '') {
                                $display_name = $this->escape($item->name);
                            } else {
                                $display_name = $this->escape($item->preferred_name);
                            }

                            if (($canEdit) AND ($item->block == 0)) {
                                echo '<a href="';
                                if ($item->id == 0) {
                                    $target = 'index.php?option=com_ra_mailman&task=profile.create&id=' . (int) $item->user_id;
                                } else {
                                    $target = 'index.php?option=com_ra_mailman&task=profile.edit&id=' . (int) $item->user_id;
                                }
                                echo Route::_($target);
                                echo '">';
                                echo $display_name;
                                echo '</a>';
                            } else {
                                echo $display_name;
                            }
                            echo '</td>';

                            echo '<td>' . $this->escape($item->home_group) . '</td>';
                            echo '<td>' . $item->email . '</td>';

                            // Count number of lists user is subscribed to
                            echo '<td>';
                            $sql = 'SELECT COUNT(id) FROM #__ra_mail_subscriptions WHERE user_id=';
                            $count = $objHelper->getValue($sql . $item->user_id);
                            if ($count > 0) {
                                $target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showUserLists&user_id=' . $item->user_id;
                                echo '<a>' . $objHelper->buildLink($target, $count) . '</a>';
                            }
                            if ($item->home_group != '') {
                                $target = 'administrator/index.php?option=com_ra_mailman&view=list_select&user_id=' . $item->user_id;
                                echo '<span class="icon-pencil-2"></span>';
                                echo '<a>' . $objHelper->buildLink($target, 'edit') . '</a>';
                            }
                            echo '</td>' . PHP_EOL;

                            // Count number of mailshots user has been sent
                            echo '<td>';
                            $sql = 'SELECT COUNT(id) FROM #__ra_mail_recipients WHERE user_id=';
                            $count = $objHelper->getValue($sql . $item->user_id);
                            if ($count > 0) {
                                $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showIndividualMailshots&user_id=' . $item->user_id;
                                echo '<a>' . $objHelper->buildLink($target, $count) . '</a>';
                            }
                            echo '</td>' . PHP_EOL;

                            echo '<td>' . HTMLHelper::_('date', $item->registerDate, 'd-m-Y') . '</td>';
                            echo '<td>';
                            if ($item->lastvisitDate != '0000-00-00 00:00:00') {
                                echo HTMLHelper::_('date', $item->lastvisitDate, 'd-m-Y');
                            }
                            echo '</td>' . PHP_EOL;

                            echo '<td>' . $item->user_id . '</td>';
                            echo '</tr>';
                        }
                        ?>
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

