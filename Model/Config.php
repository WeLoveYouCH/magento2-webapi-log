<?php
/**
 * @package     VladFlonta\WebApiLog
 * @author      Vlad Flonta
 * @copyright   Copyright © 2022
 * @license     https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace VladFlonta\WebApiLog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_WEBAPI_LOGGER = 'webapi/logger/';
    const XML_PATH_NOTIFICATION_ENABLED = 'webapi/email_notifier/enable';
    const XML_PATH_EMAIL_RECIPIENTS = 'webapi/email_notifier/recipients';
    const XML_PATH_EMAIL_TEMPLATE = 'webapi/email_notifier/email_template';

    /** @var ScopeConfigInterface */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     */
    public function getExcludeServices(): array
    {
        return array_filter(explode(
            ',',
            $this->scopeConfig->getValue(self::XML_PATH_WEBAPI_LOGGER.'exclude_services', 'store') ?? ''
        ));
    }

    /**
     * @return string
     */
    public function getSavePath(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_WEBAPI_LOGGER.'save_path', 'store');
    }

    /**
     * @return int
     */
    public function getKeepDays(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_WEBAPI_LOGGER.'keep_days', 'store');
    }

    /**
     * @return int
     */
    public function getFolderDepth(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_WEBAPI_LOGGER.'folder_depth', 'store');
    }

    /**
     * @return boolean
     */
    public function getEnable(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_WEBAPI_LOGGER.'enable', 'store');
    }

    /**
     * Check if email notification is enabled
     *
     * @return bool
     */
    public function isEmailNotificationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NOTIFICATION_ENABLED);
    }

    /**
     * Get email recipients configuration
     *
     * @return array
     */
    public function getEmailRecipients(): array
    {
        return explode(PHP_EOL, $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENTS));
    }

    /**
     * Get email template configuration
     *
     * @return string
     */
    public function getEmailTemplate(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE);
    }

    /**
     * @return boolean
     */
    public function isIntegrationNameEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_WEBAPI_LOGGER.'enable_integration_name', 'store');
    }
}
