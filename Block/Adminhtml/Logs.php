<?php

/**
 * @author DrdLab Team
 * @package VladFlonta_WebApiLog
 */

namespace VladFlonta\WebApiLog\Block\Adminhtml;

class Logs extends \Magento\Backend\Block\Widget\Form\Container
{

    protected $_config;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \VladFlonta\WebApiLog\Model\Config $config,
        array $data = []
    ) {
        $this->_config = $config;

        parent::__construct($context, $data);
    }

    protected function _construct() {
        //Define form block
        $this->_objectId = 'id';
        $this->_blockGroup = 'VladFlonta_WebApiLog';
        $this->_controller = 'adminhtml';
        
        parent::_construct();

        $this->buttonList->update('save','label', __('Delete Logs'));
        $this->buttonList->update('save','data_attribute', [
            'mage-init' => ['button' => ['event' => 'save', 'target' => '#edit_form']],
        ]);

        $this->buttonList->add(
            'keep-button',
            [
                'label' => sprintf(__('Delete except last %d days'), $this->_config->getKeepDays()),
                'class' => 'keep',
                'on_click' => "jQuery('#keep_logs').val(1)",
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#edit_form']]
                ],
                'sort_order' => 20,
            ]
        );
    }
}
