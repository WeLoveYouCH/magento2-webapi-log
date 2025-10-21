<?php

/**
 * @author DrdLab Team
 * @package VladFlonta_WebApiLog
 */

namespace VladFlonta\WebApiLog\Controller\Adminhtml\Logs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 */
class Index extends Action
{
    const MENU_ID = 'VladFlonta_WebApiLog::logs';

    /**
     * Page result factory
     *
     * @var PageFactory
     */
    public PageFactory $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    /**
     * Load the page defined in view/adminhtml/layout/webapilog_logs_index.xml
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Web API Rest Logs'));

        return $resultPage;
    }

    /**
     * Check Autherization
     *
     * @return boolean
     */
    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('VladFlonta_WebApiLog::logs');
    }
}