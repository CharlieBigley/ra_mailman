<?php
/**
 * @version    4.0.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
echo '<h2>' . $this->list_name . '</h2>';
$self = 'index.php?option=com_ra_mailman&view=mailshot';
$self .= '&id=' . (int) $this->item->id . '&list_id=' . (int) $this->list_id;
?>

<form
    action="<?php echo Route::_($self); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="mailshot-form" class="form-validate form-horizontal">
    <div class="control-group">
        <div class="controls">
            <a class="btn btn-danger"
               href="<?php echo Route::_('index.php?option=com_ra_mailman&task=mailshot.cancel'); ?>"
               title="<?php echo Text::_('JCANCEL'); ?>">
                <span class="fas fa-times" aria-hidden="true"></span>
                <?php echo Text::_('JCANCEL'); ?>
            </a>
            <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('mailshot.save')">
                <span class="fas fa-check" aria-hidden="true"></span>
                <?php echo Text::_('JSAVE'); ?>
            </button>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
                <legend><?php //echo 'Mailshot';                ?></legend>
                <?php
                echo $this->form->renderField('title');
                echo $this->form->renderField('body');
                echo $this->form->renderField('attached_file');

                echo $this->form->renderField('attachment');
                //               if (!$this->date_sent == '0000-00-00') {
                //                   echo $this->form->renderField('date_sent');
                //               }
                echo $this->form->renderField('mail_list_id');
                echo $this->form->renderField('id');
//                                echo $this->form->renderField('state');
                echo $this->form->renderField('created_by');
                echo $this->form->renderField('created');
                echo $this->form->renderField('modified_by');
                echo $this->form->renderField('modified');
                ?>

            </fieldset>
        </div>
    </div>





    <input type="hidden" name="jform[id]" value="<?php //echo $this->item->id;               ?>" />
    <input type="hidden" name="jform[state]" value="<?php //echo $this->item->state;               ?>" />

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
