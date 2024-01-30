<?php
/**
 * @version    4.0.8
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 14/11/23 CB pass menu_id to mail-lists list, add table-responsive
 * 21/11/23 CB show recipients from site (not Admin)
 * 22/12/23 CB prettify date sent
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

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('com_ra_tools', 'ramblers.css');

echo '<h2>Mailshots for ' . $this->group_code . ' ' . $this->list_name . '</h2>';
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
      name="adminForm" id="adminForm">
    <?php
    if (!empty($this->filterForm)) {
        echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
    }
    echo '<div class="table-responsive">' . PHP_EOL;
    echo '<table class="table mintcake table-striped" id="mailshotList">' . PHP_EOL;
    echo '<thead>' . PHP_EOL;
    echo '<tr>' . PHP_EOL;
    echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Sent<br>Started', 'a.date_sent', $listDirn, $listOrder) . '</th>';
    echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Title', 'a.title', $listDirn, $listOrder) . '</th>';
    echo '<th class="left">Details</th>';
    echo '<th class="left">Attach</th>';
    echo '<th class="left">Recipients</th>' . PHP_EOL;

    echo '</tr>' . PHP_EOL;
    echo '</thead>' . PHP_EOL;

    echo '<tbody>' . PHP_EOL;
    foreach ($this->items as $i => $item) {

        echo '<tr class="' . $i % 2 . '">';

        echo '<td style="vertical-align: top" class="date_sent">';
//        echo $item->date_sent . '</td>';
        echo HTMLHelper::_('date', $item->date_sent, 'h:i d/m/y');
        if (HTMLHelper::_('date', $item->date_sent, 'h:i d/m/y') != HTMLHelper::_('date', $item->processing_started, 'h:i d/m/y')) {
            echo '<br>' . HTMLHelper::_('date', $item->processing_started, 'h:i d/m/y');
        }
        echo '</td>' . PHP_EOL;
        echo '<td style="vertical-align: top">';
        $link = 'index.php?option=com_ra_mailman&task=mailshot.showMailshot&id=' . $item->id . '&tmpl=component';
        $link .= '&Itemid=' . $this->menu_id;
        echo $this->objHelper->buildLink($link, $item->title);
//              echo $item->title . '</a>';
        echo '</td>' . PHP_EOL;

        echo '<td class = "item-details">';
        /*
          if (strlen($item->full_details) > $this->max_chars) {
          $details = strip_tags($item->full_details);
          echo substr($item->full_details, 0, $this->max_chars);
          echo $objHelper->buildLink($link, 'Read more', True, 'readmore');
          } else {
          echo $item->full_details;
          }
         */
        if (strlen($item->body) > $this->max_chars) {
            echo strip_tags(substr($item->body, 0, $this->max_chars)) . ' ....';
            $details = strip_tags($item->body);
            echo substr($details, 0, $this->max_chars);
            echo $this->objHelper->buildLink($link, 'Read more', True, 'readmore');
        } else {
            echo rtrim($item->body) . PHP_EOL;
        }
        echo '</td>' . PHP_EOL;

        echo '<td style="vertical-align: top">' . $item->attachment . '</td>';

        echo '<td style="vertical-align: top">';
        $count = $this->objHelper->getValue('SELECT COUNT(id) FROM #__ra_mail_recipients WHERE mailshot_id=' . $item->id);
        if ($count > 0) {
            $target = 'index.php?option=com_ra_mailman&task=mailshot.showRecipients&list_id=' . $this->list_id . '&id=' . $item->id;
            $target .= '&Itemid=' . $this->menu_id;
            echo $this->objHelper->buildLink($target, $count);
        }
        echo '</td>' . PHP_EOL;

        echo '</tr>' . PHP_EOL;
    }
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

<?php
echo $this->pagination->getPagesLinks();
$back = 'index.php?option=com_ra_mailman&view=mail_lsts&Itemid=' . $this->menu_id;
echo $this->objHelper->backButton($back);
$wa->addInlineScript("
			jQuery(document).ready(function () {
				jQuery('.delete-button').click(deleteItem);
			});

			function deleteItem() {

				if (!confirm(\"" . Text::_('COM_RA_MAILMAN_DELETE_MESSAGE') . "\")) {
					return false;
				}
			}
		", [], [], ["jquery"]);
?>