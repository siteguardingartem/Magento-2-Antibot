<?php

namespace Siteguarding\Antibot\Block\Adminhtml\Panel;

use Magento\Backend\Model\Url;
use Magento\Framework\Data\Form\FormKey;
use Siteguarding\Antibot\Helper\LogReader;

class Index extends \Magento\Backend\Block\Widget\Container
{
    public $url_builder;

    public $log_reader;

    public function __construct(\Magento\Backend\Block\Widget\Context $context, Url $url_builder, FormKey $form_key, LogReader $log_reader, array $data = [])
    {
        parent::__construct($context, $data);
        $this->url_builder = $url_builder;
        $this->form_key = $form_key;
        $this->log_reader = $log_reader;
    }

    public function get_dashboard_url()
    {
        $url = $this->url_builder->getUrl('antibot/panel/index');

        return $url;
    }

    public function update_cancel_time()
    {
        return $this->form_key->getFormKey();
    }

    public function get_logs($type)
    {
        return $this->log_reader->get_logs($type);
    }

}
