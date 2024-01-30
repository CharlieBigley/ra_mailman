<?php
/**
 * @version    4.0.12
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 22/12/23 CB prettify date sent
 * 02/01/24 CB only show subscribers if canEdit
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
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canEdit = $user->authorise('core.edit', 'com_ra_mailman');
$objHelper = new ToolsHelper;
$self = 'index.php?option=com_ra_mailman&view=mailshots&list_id=' . $this->list_id;
?>

<form action="<?php echo Route::_($self); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <?php
                echo '<table class="table mintcake table-striped" id="mailshotList">' . PHP_EOL;
                echo '<thead>' . PHP_EOL;
                echo '<tr>' . PHP_EOL;
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Sent<br>Started', 'a.date_sent', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Title', 'a.title', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">Details</th>';
                echo '<th class="left">Attach</th>';
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Mail list', 'mail_list.name', $listDirn, $listOrder) . '</th>';

                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Last updated', 'a.modified', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">Recipients</th>' . PHP_EOL;

                echo '</tr>' . PHP_EOL;
                echo '</thead>' . PHP_EOL;
                echo '<tbody>' . PHP_EOL;
                foreach ($this->items as $i => $item) :
//                    $canCreate = $this->user->authorise('core.create', 'com_ra_mailman');
//                    $canEdit = $this->user->authorise('core.edit', 'com_ra_mailman');
//                    $canCheckin = $this->user->authorise('core.manage', 'com_ra_mailman');
//                    $canChange = $this->user->authorise('core.edit.state', 'com_ra_mailman');
                    echo '<tr class="' . $i % 2 . '">';

                    echo '<td>' . HTMLHelper::_('date', $item->date_sent, 'h:i d/m/y');
                    if (HTMLHelper::_('date', $item->date_sent, 'h:i d/m/y') != HTMLHelper::_('date', $item->processing_started, 'h:i d/m/y')) {
                        echo '<br>' . HTMLHelper::_('date', $item->processing_started, 'h:i d/m/y');
                    }
                    echo '</td>' . PHP_EOL;
                    echo '<td>';
                    $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showMailshot&id=' . $item->id . '&tmpl=component';
                    echo $objHelper->buildLink($target, $item->title);
//                    echo $item->title . '</a>';
                    echo '</td>' . PHP_EOL;
                    echo '<td class = "item-details">';
                    if (strlen($item->body) > 516) {
                        echo strip_tags(substr($item->body, 0, 516)) . ' ....';
//        $link = '';
//        echo $this->objHelper->buildLink($link, 'Read more', true, 'readmore') . PHP_EOL;
                    } else {
                        echo strip_tags(rtrim($item->body)) . PHP_EOL;
                    }
                    echo '</td>';
                    echo '<td>' . $item->attachment . '</td>';
                    echo '<td>' . $item->list_name . '</td>';

                    if ($item->modified != '0000-00-00') {
                        echo '<td>' . $item->modified . '</td>';
                    } else {
                        echo '<td></td>';
                    }

                    echo '<td>';
                    $count = $this->objHelper->getValue('SELECT COUNT(id) FROM #__ra_mail_recipients WHERE mailshot_id=' . $item->id);
                    if ($count > 0) {
                        if ($canEdit) {
                            $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showRecipients&list_id=' . $this->list_id . '&id=' . $item->id;
                            echo $this->objHelper->buildLink($target, $count);
                        } else {
                            echo $count;
                        }
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