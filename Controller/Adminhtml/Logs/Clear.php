<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace VladFlonta\WebApiLog\Controller\Adminhtml\Logs;

use Exception;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use VladFlonta\WebApiLog\Model\Config;

class Clear extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'VladFlonta_WebApiLog::logs';

    protected string $_rootPath;
    protected string $_rootFolder;

    protected Config $_config;

    /**
     * @param Context $context
     * @param Config $config
     * @param Filesystem $fileSystem
     */
    public function __construct(
        Context $context,
        Config $config,
        Filesystem $fileSystem
    ) {
        parent::__construct($context);

        $filePath = $fileSystem
        ->getDirectoryRead(DirectoryList::LOG)
        ->getAbsolutePath();

        $this->_rootPath = $filePath;
        $this->_rootFolder = $config->getSavePath();
        $this->_config = $config;
    }

    /**
     * Role form submit action to save or create new role
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $resource = $this->getRequest()->getParam('resource', false);

        try {
            foreach ($resource as $folder) {
                if(is_dir($this->_rootPath . DIRECTORY_SEPARATOR . $folder)) {
                    $this->removeDir($this->_rootPath . DIRECTORY_SEPARATOR . $folder, $this->getRequest()->getParam('keep_logs', false));
                }
            }
            $this->messageManager->addSuccessMessage(__('Logs cleared.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting log files.') . " - " . $e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }

    protected function removeDir(string $dir, bool $keepLogs = false) {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        $startDate = strtotime("-" . $this->_config->getKeepDays() ." day");
        foreach($files as $file) {
            if(!$keepLogs || filectime($file->getPathname()) < $startDate) {
                if ($file->isDir()) {
                    try {
                        rmdir($file->getPathname());
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Directory not empty') === false) {
                            throw $e;
                        }
                    }
                } else {
                    unlink($file->getPathname());
                }
            }
        }

        if(!$keepLogs || filectime($dir) < $startDate) {
            try {
                rmdir($dir);
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Directory not empty') === false) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Preparing layout for output
     */
    protected function _initAction(): Clear
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('VladFlonta_WebApiLog::logs');
        $this->_addBreadcrumb(__('System'), __('System'));
        $this->_addBreadcrumb(__('API Logs'), __('API Logs'));
        $this->_addBreadcrumb(__('Logs'), __('Logs'));
        return $this;
    }

    /**
     * Parse request value from string
     *
     * @param string $paramName
     * @return array
     */
    private function parseRequestVariable(string $paramName): array
    {
        $value = $this->getRequest()->getParam($paramName, '');
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        parse_str($value, $value);
        return array_keys($value);
    }
}
