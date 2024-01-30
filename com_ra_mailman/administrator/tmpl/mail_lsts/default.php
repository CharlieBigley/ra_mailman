<?php
/**
 * @version    [BUMP]
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 21/07/23 CB use buildButton
 * 17/10/23 always allow edit authors, even if none already present
 * 01/12/23 CB show info icon for display of authors / subscribers
 * 01/01/24 CB ensure read only if not in Group com_ra_mailman
 * 02/01/24 CB only show audit if canEdit
 * 16/01/24 CB always allow Authors to create/send mailshots
 * 27/01/24 CB use mailhelper / countMailshots
 * 29/01/24 CB selections
 * 30/01/24 CB remove attempt to show and change status (gives error in controller /submit)
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');

// Ensure display is read only if not in Group com_ra_mailman
$canUpdate = $this->canDo->get('core.edit');
$canCreate = $user->authorise('core.create', 'com_ra_mailman');
$canEdit = $user->authorise('core.edit', 'com_ra_mailman');
$objHelper = new ToolsHelper;
$objMailHelper = new Mailhelper;
?>

<form action="<?php echo Route::_('index.php?option=com_ra_mailman&view=mail_lsts'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table mintcake table-striped" id="mail_lstList">
                    <thead>
                        <tr>
                            <?php
                            echo '<th class="w-1 text-center">';
                            echo '<input type="checkbox" autocomplete="off" class="form-check-input" name="checkall-toggle" value="" title=""';
                            echo Text::_('JGLOBAL_CHECK_ALL');
                            echo '" onclick="Joomla.checkAll(this)"/>';
                            echo '</th>';

//                            echo '<th  scope="col" class="w-1 text-center">' . HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder) . '</th>';
                            ?>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Group', 'a.group_code', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'List name', 'a.name', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Owner', 'g.name', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Type', 'a.record_type', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Home', 'a.home_group_only', $listDirn, $listOrder); ?>
                            </th>
                            <?php
                            echo '<th class="left">';
                            echo 'Last sent';
                            echo '</th>';

                            echo '<th class="left">';
                            echo 'Authors';
                            echo '</th>';

                            echo '<th class="left">';
                            echo 'Subscribers';
                            echo '</th>';

                            echo '<th class="left">';
                            echo 'Mailshots';
                            echo '</th>';

                            if ($canEdit) {
                                echo '<th class="left">';
                                echo 'Audit';
                                echo '</th>';
                            }
                            ?>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="11">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($this->items as $i => $item) :
                            $canCheckin = $user->authorise('core.manage', 'com_ra_mailman');
                            $canChange = $user->authorise('core.edit.state', 'com_ra_mailman');
                            // Find latest date that mailshots sent
                            $mailshot_id = $objMailHelper->outstanding($item->id);
                            // see if the current user is an Author of this list
                            $isAuthor = $objMailHelper->isAuthor($item->id);

                            // Count number of Authors
                            $count_authors = $objMailHelper->countSubscribers($item->id, 'Y');
                            // Count number of subscribers
                            $count_subscribers = $objMailHelper->countSubscribers($item->id);
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group='1' data-transition>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                <?php
//                                echo '<td>' . HTMLHelper::_('jgrid.published', $item->state, $i, 'mail_lst.', $canChange, 'cb') . '</td>';
                                echo '<td>' . $item->group_code . '</td>';
                                ?>
                                <td>
                                    <?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
                                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'mail_lsts.', $canCheckin); ?>
                                    <?php endif; ?>
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_ra_mailman&task=mail_lst.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->name); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo $this->escape($item->name); ?>
                                    <?php
                                    endif;
                                    if ($item->group_primary != '') {
                                        echo '<i class="fas fa-star fa-fw"></i>';
                                    }
                                    echo '</td>';
                                    echo '<td>' . $item->owner . '</td>';
                                    echo '<td>' . $item->Type . '</td>';
                                    echo '<td>';
                                    if ($item->home_group_only == 1) {
                                        echo 'Yes';
                                    } else {
                                        echo 'No';
                                    }
                                    echo '</td>';

                                    echo '<td>'; // . $item->owner_id;
                                    if (($mailshot_id > 0)) {
                                        if (($canEdit) OR ($isAuthor)) {
                                            $target = "administrator/index.php?option=com_ra_mailman&task=mailshot.send&mailshot_id=" . $mailshot_id;
                                            echo $objHelper->buildButton($target, "Send", False, 'red');
                                        }
                                    }
                                    echo '</td>';

                                    echo '<td>';
                                    $target_info = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showSubscribers&list_id=' . $item->id;
                                    if ($count_authors > 0) {
                                        //echo $objHelper->buildLink($target_info . '&author=Y', $count_authors, False, "");
                                        echo $count_authors;
                                        echo $objHelper->imageButton('I', $target_info . '&author=Y');
                                    }
                                    if (($item->state == 1) AND ($canEdit)) {
                                        $target = 'administrator/index.php?option=com_ra_mailman&view=user_select&record_type=2&list_id=' . $item->id;
//                        echo $objHelper->buildLink($target, 'Select', False, "btn btn-small button-new");
                                        //             echo '<span class="icon-pencil-2"></span>';
                                        echo $objHelper->buildLink($target, 'edit');
                                    }
                                    echo '</td>';

                                    echo '<td>';
                                    if ($count_subscribers > 0) {
//                                        echo $objHelper->buildLink($target_info, $count_subscribers, False, ""); // . "-" . $user_id);;
                                        echo $count_subscribers;
                                        echo $objHelper->imageButton('I', $target_info);
                                    }
                                    if (($item->state == 1) AND ($canEdit)) {
                                        $target = '/administrator/index.php?option=com_ra_mailman&view=user_select&record_type=1&list_id=' . $item->id;
//                                    echo '<a href="' . $target . '"><span class="icon-pencil-2"></span></a>';
                                        //            echo $objHelper->buildIconlink($target, 'icon-pencil-2');
                                        echo $objHelper->buildLink($target, 'edit');
                                    }
                                    echo '</td>';
                                    // Mailshots
                                    echo '<td>';
                                    $count = $objMailHelper->countMailshots($item->id);
                                    $target_info = 'administrator/index.php?option=com_ra_mailman&view=mailshots&callback=mail_lsts&list_id=' . $item->id;
                                    if ($count > 0) {
                                        // show a link to browse the mailshots
                                        echo $objHelper->buildLink($target_info, $count, False);
                                    }
                                    if ($item->state == 1) {
                                        if (($canEdit) OR ($isAuthor)) {
                                            $target_edit = 'administrator/index.php?option=com_ra_mailman&view=mailshot&layout=edit&list_id=' . $item->id;
                                            if (($count == 0) or ($mailshot_id == 0)) {
                                                $target_edit .= '&id=0';
                                                $label = 'New';
                                                $colour = 'lightgreen';
                                            } else {
                                                // Set up the link to Edit
                                                $target_edit .= '&id=' . $mailshot_id;
                                                $label = 'Edit';
                                                $colour = 'darkgreen';
                                            }
                                            echo $objHelper->buildButton($target_edit, $label, False, $colour);
                                        }
                                    }

                                    echo '</td>';
                                    // Audit

                                    if ($canEdit) {
                                        echo '<td>';
                                        $target_audit = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.showAuditAll&list_id=' . $item->id;
                                        echo $objHelper->buildLink($target_audit, 'Show', False, 'gray');
                                        echo '</td>';
                                    }
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