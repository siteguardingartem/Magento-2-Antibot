<?php

namespace Siteguarding\Antibot\Controller\Adminhtml\Settings;

use Siteguarding\Antibot\Helper\SettingsWriter;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;


class Index extends Action
{

    protected $settings_writer;

    public function __construct(Context $context, SettingsWriter $settings_writer)
    {
        parent::__construct($context);
        $this->settings_writer = $settings_writer;
    }

    public function execute()
    {

        $params = $this->getRequest()->getParams();

        if (isset($params['allowed_bots']) && isset($params['allowed_ip']) && isset($params['blocked_ip'])) {
            $this->settings_writer->write_config($params);
        }

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();

    }
}
