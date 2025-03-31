<?php

/**
 * @author DrdLab Team
 * @package VladFlonta_WebApiLog
 */

namespace VladFlonta\WebApiLog\Block\Adminhtml\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setDestElementId('resource-clear-form');
    }

    /**
     * Prepare form data
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'enctype' => 'multipart/form-data',
                'action' => $this->getUrl('*/*/clear'),
                'method' => 'post'
                ]
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Existing API logs')]);
        
        $fieldset->addField('keep_logs', 'hidden', ['name' => 'keep_logs']);

        $field = $fieldset->addField('tree', 'text', ['name' => 'resource_tree']);
        $renderer = $this->getLayout()->createBlock(
            'VladFlonta\WebApiLog\Block\Adminhtml\Tree'
        );
        $field->setRenderer($renderer);

        $form->setValues(['keep_logs' => 0]);
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
