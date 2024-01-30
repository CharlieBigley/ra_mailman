<?php
/**
 * @version    4.0.13
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 21/07/23 CB use buildButton
 * 02/08/23 remove com_ra_mailman.admin
 * 02/01/23 CB correct spelling of \ra_tools\Site\Helpers\ToolsHelper;
 * 06/01/24 CB cancelSubscription
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


$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canOrder = $user->authorise('core.edit.state', 'com_ra_mailman');

$objHelper = new ToolsHelper;
?>

<form action="<?php echo Route::_('index.php?option=com_ra_mailman&view=subscriptions'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table table-striped" id="subscriptionList">
                    <thead>
                        <tr>
                            <?php
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Group', 'l.group_code', $listDirn, $listOrder) . '</th>';
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'List', 'l.name', $listDirn, $listOrder) . '</th>';

                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Subscriber', 'u.name', $listDirn, $listOrder) . '</th>';
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Method', 'm.name', $listDirn, $listOrder) . '</th>';
                            echo '<th  scope="col" class="w-1 text-center">' . HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder) . '</th>';

                            //echo '<th class="left">' . HTMLHelper::_('jgrid.published', $item->state, $i, 'subscriptions.', $canChange, 'cb') . '</th>';
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Last updated', 'a.modified', $listDirn, $listOrder) . '</th>';
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Expires', 'a.expiry_date', $listDirn, $listOrder) . '</th>';
                            echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'IP Address', 'a.ip_adress', $listDirn, $listOrder) . '</th>';
                            echo '<th>Audit</th>';
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
                    <tbody
                    <?php
                    foreach ($this->items as $i => $item) :
                        $canCreate = $user->authorise('core.create', 'com_ra_mailman');
                        $canEdit = $user->authorise('core.edit', 'com_ra_mailman');
                        $canCheckin = $user->authorise('core.manage', 'com_ra_mailman');
                        $canChange = $user->authorise('core.edit.state', 'com_ra_mailman');
                        ?>
                            <tr class="row<?php echo $i % 2; ?>" data-transition>

                            <?php
                            echo '<td>' . $item->group . '</td>';
                            echo '<td>' . $item->list . '</td>';
                            echo '<td>' . $item->subscriber . '</td>';
                            echo '<td>' . $item->Method . '</td>';
                            //echo '<td>' . $item->Status . '</td>';
                            echo '<td>' . HTMLHelper::_('jgrid.published', $item->state, $i, 'subscriptions.', $canChange, 'cb') . '</td>';

                            echo '<td>';
                            if (($item->modified != '') AND ($item->modified != '0000-00-00 00:00:00')) {
                                echo $item->modified;
                            }
                            echo '</td>';

                            echo '<td>';
                            if (($item->expiry_date != '') AND ($item->expiry_date != '0000-00-00 00:00:00')) {
                                echo $item->expiry_date;
                                $target = 'administrator/index.php?option=com_ra_mailman&task=subscriptions.forceRenewal';
                                $target .= '&list_id=' . $item->list_id . '&user_id=' . $item->user_id;
                                echo $objHelper->imageButton('X', $target);
                            }
                            echo '</td>';
                            echo '<td>' . $item->ip_address . '</td>';

                            echo '<td>';
                            if ($item->state == 1) {
                                $target = 'administrator/index.php?option=com_ra_mailman&task=subscriptions.showAudit&id=' . $item->id;
//                                $target .= '&user_id=' . $item->user_id . '&callback=subscriptions';
                                echo $objHelper->buildButton($target, 'Show', False, 'gray');
                            }
                            echo '</td>';
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