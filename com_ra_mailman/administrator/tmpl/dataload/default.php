<?php

/**
 * @package     com_ra_mailman
 * @version     1.0.2
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie Bigley <webmaster@bigley.me.uk>
 *              Actual processing is carried out in site/helpers/UserHelper.php
 * 05/12/22 CB Created from com ramblers
 */
use \Joomla\CMS\HTML\HTMLHelper;

// No direct access
defined('_JEXEC') or die;
?>
<form action="<?php echo JRoute::_('index.php?option=com_ra_mailman&layout=edit'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
    <div class="row-fluid">
        <div id="j-main-container" class="span10">
            <fieldset class="adminform">
                <?php
//                echo 'input<br>';
//                $input = JFactory::getApplication()->input;
//                $files = $input->files->get('jform');
//                print_r($files);
//                echo 'xxxxx';
                echo '<div class="control-group"><div class="control-label">';
                echo $this->form->getLabel('csv_file');
                echo '</div>' . PHP_EOL;
                echo '<div class="controls">';
                echo $this->form->getInput('csv_file');
                echo '</div></div>' . PHP_EOL;

                echo '<div class="control-group"><div class="control-label">';
                echo $this->form->getLabel('data_type');
                echo '</div>' . PHP_EOL;
                echo '<div class="controls">';
                echo $this->form->getInput('data_type');
                echo '</div></div>' . PHP_EOL;

                echo $this->form->renderField('mail_list') . PHP_EOL;
                echo $this->form->renderField('processing') . PHP_EOL;
                ?>

            </fieldset>
        </div>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
    <div id="validation-form-failed" data-backend-detail="dataload" data-message="<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>">
    </div>
</form>

