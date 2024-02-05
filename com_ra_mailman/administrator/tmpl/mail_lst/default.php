<?php
/**
 * 4.0.0
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

if ($this->item->group_primary == '') {
    echo '<p><b>This is not the Primary List</b></p>';
} else {
    echo '<p><b>This is the Primary List and can be used for official communications<i class="fas fa-star fa-fw"></i></b></p>';
}
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_mailman&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="mail_lst-form" class="form-validate form-horizontal">

    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
                <legend><?php echo Text::_('Mailing list'); ?></legend>

                <?php echo $this->form->renderField('group_code'); ?>
                <?php echo $this->form->renderField('name'); ?>
                <?php echo $this->form->renderField('owner_id'); ?>
                <?php echo $this->form->renderField('record_type'); ?>
                <?php echo $this->form->renderField('home_group_only'); ?>
                <?php echo $this->form->renderField('chat_list'); ?>
                <?php echo $this->form->renderField('footer'); ?>
                <?php echo $this->form->renderField('state'); ?>

                <?php echo $this->form->renderField('created_by'); ?>
                <?php echo $this->form->renderField('created'); ?>
                <?php echo $this->form->renderField('modified_by'); ?>
                <?php echo $this->form->renderField('modified'); ?>
                <?php echo $this->form->renderField('group_primary'); ?>
                <?php echo $this->form->renderField('id'); ?>
            </fieldset>
        </div>
    </div>
    <input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
    <input type="hidden" name="jform[state]" value="<?php //echo $this->item->state;      ?>" />
    <input type="hidden" name="jform[ordering]" value="<?php //echo $this->item->ordering;      ?>" />
    <input type="hidden" name="jform[checked_out_time]" value="<?php //echo $this->item->checked_out_time;      ?>" />
    <input type="hidden" name="jform[contact_details]" value="<?php //echo $this->item->contact_details;      ?>" />
    <input type="hidden" name="jform[url]" value="<?php //echo $this->item->url;      ?>" />
    <input type="hidden" name="jform[url_description]" value="<?php //echo $this->item->url_description;      ?>" />
    <input type="hidden" name="jform[attachment]" value="<?php //echo $this->item->attachment;      ?>" />
    <input type="hidden" name="jform[attachment_description]" value="<?php //echo $this->item->attachment_description;      ?>" />

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
