<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Model;

use VladFlonta\WebApiLog\Model\Mail\Template\TransportBuilder;
use Magento\Contact\Model\ConfigInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Email
{
    private const EMAIL_SENDER_GENERAL = 'general';

    /**
     * @param ConfigInterface $contactsConfig
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ConfigInterface $contactsConfig,
        protected TransportBuilder $transportBuilder,
        protected StateInterface $inlineTranslation,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Send email message
     *
     * @param string $templateIdentifier
     * @param array $emailRecipients
     * @param array $templateVars
     * @param string $content
     * @param string $fileName
     * @param string $sendFrom
     * @param int $storeId
     * @return void
     * @throws NoSuchEntityException
     */
    public function sendMessageWithAttachment(
        string $templateIdentifier,
        array $emailRecipients,
        array $templateVars,
        string $content = '',
        string $fileName = '',
        string $sendFrom = self::EMAIL_SENDER_GENERAL,
        int $storeId = Store::DEFAULT_STORE_ID
    ): void {
        $templateOptions = [
            'area' => Area::AREA_FRONTEND,
            'store' => $this->storeManager->getStore()->getStoreId(),
        ];

        try {
            foreach ($emailRecipients as $email) {
                $transportBuilder = $this->transportBuilder
                    ->setTemplateIdentifier($templateIdentifier)
                    ->setTemplateOptions($templateOptions)
                    ->setTemplateVars($templateVars)
                    ->setFromByScope($sendFrom, $storeId)
                    ->addTo(trim($email));

                if ($fileName) {
                    $transportBuilder->addAttachment($content, $fileName);
                }

                $transportBuilder->getTransport()->sendMessage();
            }
        } catch (\Exception $e) {
            $this->logger->debug("Error while sending email: " . $e->getMessage());
        }
    }
}
