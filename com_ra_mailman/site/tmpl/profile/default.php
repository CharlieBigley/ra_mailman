<?php
/**
 * @version    4.0.7
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 13/11/23 CB show group_code last
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;

//$objUserHelper = new UserHelper;
//$objUserHelper->purgeTestdata();


$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_ra_mailman', JPATH_SITE);

$user = Factory::getApplication()->getIdentity();

// $this->title is derived from the menu
echo '<h2>' . $this->title . '</h2>';
if ($this->user->id == 0) {   // Sellf registering
    $params = ComponentHelper::getParams('com_ra_mailman');
    $invitation_message = $params->get('invitation_message', 'Welcome');
    echo "<h4>$invitation_message</h4>";
} else {
    echo '<p>After creating the User, you can select the lists to which (s)he should be subscribed</p>';
}
?>
<div class="profile-edit front-end-edit">

    <form id="form-profile"
          action="<?php echo Route::_('index.php?option=com_ra_mailman&task=profile.save'); ?>"
          method="post" class="form-validate form-horizontal" enctype="multipart/form-data">

        <input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />

        <?php
        echo $this->form->renderField('preferred_name');
        echo $this->form->renderField('email');
        echo $this->form->renderField('group_code');
        //echo $this->form->renderField('mode');
        ?>
        <div class="control-group">
            <div class="controls">
                <a class="btn btn-danger"
                   href="<?php echo Route::_('index.php?option=com_ra_mailman&task=profile.cancel'); ?>"
                   title="<?php echo Text::_('JCANCEL'); ?>">
                    <span class="fas fa-times" aria-hidden="true"></span>
                    <?php echo Text::_('JCANCEL'); ?>
                </a>
                <button type="submit" class="validate btn btn-primary">
                    <span class="fas fa-check" aria-hidden="true"></span>
                    <?php echo Text::_('JSUBMIT'); ?>
                </button>

            </div>
        </div>

        <input type="hidden" name="option" value="com_ra_mailman"/>
        <input type="hidden" name="task"
               value="profile.save"/>
               <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

