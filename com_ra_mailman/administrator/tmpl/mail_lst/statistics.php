<?php

/**
 * @version    4.0.14
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
//use Joomla\CMS\Helper\ContentHelper;
//use \Joomla\CMS\Language\Text;
use \Ramblers\Component\Ra_mailman\Site\Helpers\MailHelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

$objHelper = new ToolsHelper;
$objMailHelper = new MailHelper;

$description = $objMailHelper->getDescription($this->list_id);
echo '<h2>List ' . $description . '</h2>';

echo 'There are other records present for this list:<br>';

echo '<ul>';
$count = $objMailHelper->countMailshots($this->list_id);
echo '<li>' . $count . ' mailshots</li>';

$sql = 'SELECT COUNT(*) ';
$sql .= 'FROM  `#__ra_mail_recipients` AS mr ';
$sql .= 'INNER JOIN `#__ra_mail_shots` as ms ON ms.id = mr.mailshot_id ';
$sql .= 'WHERE ms.mail_list_id=' . $this->list_id;
$count = $objHelper->getValue($sql . ' AND state=1');
echo '<li>Details of the ' . $count . ' recipients</li>';

$sql = 'SELECT COUNT(*) ';
$sql .= 'FROM  `#__ra_mail_subscriptions` ';
$sql .= 'WHERE list_id=' . $this->list_id;
$count = $objHelper->getValue($sql . ' AND state=1');
echo '<li>' . $count . ' active subscribers</li>';

$sql = 'SELECT COUNT(*) ';
$sql .= 'FROM  `#__ra_mail_subscriptions` ';
$sql .= 'WHERE list_id=' . $this->list_id;
$count = $objHelper->getValue($sql . ' AND state=0');
echo '<li>' . $count . ' inactive subscribers</li>';

$sql = 'SELECT COUNT(*) ';
$sql .= 'FROM  `#__ra_mail_subscriptions_audit` AS a ';
$sql .= 'INNER JOIN `#__ra_mail_subscriptions` as ms ON ms.id = a.object_id ';
$sql .= 'WHERE ms.list_id=' . $this->list_id;
//echo $sql;
//return;
$count = $objHelper->getValue($sql);
echo '<li>' . $count . ' records detailing how and when they subscribed.</li>';

echo '</ul>';
echo 'If you delete this Mail list, all these associated records will also be irrevocably lost. If the numbers are significant, ';
echo 'there may be implications if contention arises about appropriate application of GPDR and full details ';
echo 'of the audit trails cannot be produced.<br>';
echo '<br>';
echo 'You may decide to ensure that a backup of the database is taken before the records are deleted, ';
echo 'and this is kept securely for possible evidentiary purposes.<br>';

$target = 'administrator/index.php?option=com_ra_mailman&task=mail_lst.purge&list_id=' . $this->list_id;
echo $objHelper->buildButton($target, 'Confirm delete', False, 'red');


