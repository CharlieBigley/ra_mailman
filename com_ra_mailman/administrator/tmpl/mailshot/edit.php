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
echo '<h4>' . $this->list_name . '</h4>';
$self = 'index.php?option=com_ra_mailman&view=mailshot&layout=edit';
$self .= '&id=' . (int) $this->item->id . '&list_id=' . (int) $this->list_id;
?>

<form
    action="<?php echo Route::_($self); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="mailshot-form" class="form-validate form-horizontal">


    <?php //echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Mailshot')); ?>
    <?php //echo HTMLHelper::_('uitab.addTab', 'myTab', 'event', Text::_('COM_RA_MAILMAN_TAB_EVENT', true)); ?>
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
                <legend><?php //echo 'Mailshot';          ?></legend>
                <?php
                echo $this->form->renderField('title');
                echo $this->form->renderField('body');
                echo $this->form->renderField('attached_file');

                echo $this->form->renderField('attachment');
                if (!$this->date_sent == '0000-00-00') {
                    echo $this->form->renderField('date_sent');
                }
                echo $this->form->renderField('mail_list_id');
                echo $this->form->renderField('id');
                echo $this->form->renderField('record_type');
                echo $this->form->renderField('created_by');
                echo $this->form->renderField('created');
                echo $this->form->renderField('modified_by');
                echo $this->form->renderField('modified');
                ?>

            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <input type="hidden" name="jform[id]" value="<?php //echo $this->item->id;         ?>" />
    <input type="hidden" name="jform[state]" value="<?php //echo $this->item->state;         ?>" />
    <input type="hidden" name="jform[ordering]" value="<?php //echo $this->item->ordering;         ?>" />
    <input type="hidden" name="jform[checked_out_time]" value="<?php //echo $this->item->checked_out_time;         ?>" />
    <input type="hidden" name="jform[organiser]" value="<?php //echo $this->item->organiser;         ?>" />
    <input type="hidden" name="jform[email]" value="<?php //echo $this->item->email;         ?>" />
    <input type="hidden" name="jform[contact_details]" value="<?php ///echo $this->item->contact_details;         ?>" />
    <input type="hidden" name="jform[url]" value="<?php //echo $this->item->url;         ?>" />
    <input type="hidden" name="jform[attachment]" value="<?php //echo $this->item->attachment;         ?>" />


    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
