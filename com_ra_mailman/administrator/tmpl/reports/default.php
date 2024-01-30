<?php
/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
// use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$objHelper = new ToolsHelper;
$objTable = new ToolsTable();
$objTable->add_header("Report,Action");
?>

<form action="<?php echo JRoute::_('index.php?option=com_ra_tools&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
//        $objTable->width = 30;
//        $objTable->add_column("Report", "R");
//        $objTable->add_column("Action", "L");


        $objTable->add_item("Users without a Profile");
        $objTable->add_item($objHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.duffUsers", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Blocked users");
        $objTable->add_item($objHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.blockedUsers", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Subscriptions due");
        $objTable->add_item($objHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.showDue", "Go", False, 'red'));
        $objTable->generate_line();

//        $objTable->add_item("Logfile");
//        $objTable->add_item($objHelper->buildLink("administrator/index.php?option=com_ra_tools&task=reports.showLogfile&offset=1", "Go", False, 'btn btn-small button-new'));
//        $objTable->generate_line();



        $objTable->generate_table();
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $objHelper->backButton($target);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>
<?php
echo "<!-- End of code from ' . __file . ' -->" . PHP_EOL;
