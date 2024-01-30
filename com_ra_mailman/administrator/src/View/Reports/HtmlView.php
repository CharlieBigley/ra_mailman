<?php

/*
 * 19/06/23 CB Created from com_ra_tools
 */

namespace Ramblers\Component\ra_mailman\Administrator\View\Reports;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

//use Ramblers\Component\ra_tools\Administrator\Helpers\ToolsHelper;
class HtmlView extends BaseHtmlView {

    protected $params;

    public function display($tpl = null) {
        $app = Factory::getApplication();
        $this->user = Factory::getUser();

        $this->params = ComponentHelper::getParams('com_ra_mailman');
        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar() {
        // Suppress menu side panel
        Factory::getApplication()->input->set('hidemainmenu', true);

        ToolBarHelper::title('Mailman reports');

        // this button display but does nothing
        //ToolbarHelper::cancel('reports.cancel', 'JTOOLBAR_CANCEL');
    }

}
