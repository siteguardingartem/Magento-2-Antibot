<?php

namespace Siteguarding\Antibot\Controller\Adminhtml\Panel;

use Siteguarding\Antibot\Helper\AutoPrepend;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Siteguarding\Antibot\Helper\Helper;

class Index extends Action
{
    protected $helper;

    protected $auto_prepend;

    public function __construct(Context $context, AutoPrepend $auto_prepend, Helper $helper)
    {
        parent::__construct($context);
        $this->auto_prepend = $auto_prepend;
        $this->helper = $helper;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['status']) && !empty($params['status'])) {
            switch ($params['status']) {
                case 'enable':
                    $this->auto_prepend->setAutoPrepends(true);
                    break;
                case 'disable':
                    $this->auto_prepend->setAutoPrepends(false);
                    break;
            }
        }

        $this->_view->loadLayout();
        $this->_view->getLayout()->getBlock('antibot_block_adminhtml_panel_index')->assign('auto_prepend', $this->helper->get_auto_prepend_file());
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();

    }
}
