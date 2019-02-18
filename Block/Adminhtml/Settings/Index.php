<?php

namespace Siteguarding\Antibot\Block\Adminhtml\Settings;

use Magento\Backend\Model\Url;
use Magento\Framework\Data\Form\FormKey;
use Siteguarding\Antibot\Helper\SettingsReader;

class Index extends \Magento\Backend\Block\Widget\Container
{
    protected $url_builder;

    protected $settings_reader;

    public function __construct(\Magento\Backend\Block\Widget\Context $context, Url $url_builder, FormKey $form_key,SettingsReader $settings_reader,  array $data = [])
    {
        parent::__construct($context, $data);
        $this->url_builder = $url_builder;
        $this->form_key = $form_key;
        $this->settings_reader = $settings_reader;
    }
    
    public function get_dashboard_url()
    {
        $url = $this->url_builder->getUrl('antibot/settings/index');

        return $url;
    }
    
    public function update_cancel_time()
    {
        return $this->form_key->getFormKey();
    }

    public function get_config()
    {
        return $this->settings_reader->get_config();
    }
}
