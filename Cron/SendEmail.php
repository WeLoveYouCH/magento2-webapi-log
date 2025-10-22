<?php
// phpcs:ignoreFile
declare(strict_types=1);

namespace VladFlonta\WebApiLog\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use VladFlonta\WebApiLog\Model\Config;
use Magento\Store\Model\Store;
use VladFlonta\WebApiLog\Model\Email;
use VladFlonta\WebApiLog\Model\ResourceModel\WebApiError;
use VladFlonta\WebApiLog\Model\ResourceModel\WebApiError\CollectionFactory;

class SendEmail
{
    private Email $mail;
    private CollectionFactory $collectionFactory;
    private LoggerInterface $logger;
    private Config $config;
    private WebApiError $webApiError;

    /**
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param Email $mail
     * @param Config $config
     * @param WebApiError $webApiError
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        Email $mail,
        Config $config,
        WebApiError $webApiError
    ) {
        $this->mail = $mail;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->config = $config;
        $this->webApiError = $webApiError;
    }

    /**
     * Execute Cron
     */
    public function execute(): void
    {
        try {
            $collection = $this->collectionFactory->create();
            $errors = $collection->getItems();

            if (empty($errors)) {
                $this->logger->info('No errors found in the last 15 minutes.');
                return;
            }

            $data = [];
            foreach ($errors as $error) {
                $data[] = [
                    'Error code' => $error->getErrorCode(),
                    'Error content' => $error->getErrorContent(),
                    'Details' => $error->getDetails(),
                    'Url' => $error->getUrl(),
                    'Count' => $error->getCount(),
                    'Updated at' => $error->getUpdatedAt(),
                ];
            }

            $templateId = $this->config->getEmailTemplate();
            $toEmails = $this->config->getEmailRecipients();
            $variables = ['errors_json' => json_encode($data)];
            $csvContent = $this->generateCsvContent($data);


            $this->mail->sendMessageWithAttachment(
                $templateId,
                [$toEmails],
                $variables,
                base64_encode($csvContent),
                'web_api_errors.csv',
                'general',
                Store::DEFAULT_STORE_ID
            );
            $this->webApiError->deleteRecentErrors();
        } catch (Exception $e) {
            $this->logger->error(__('Error occurred while sending web API error email: ' . $e->getMessage(), $e));

            return;
        }
    }

    /**
     * Generate csv content
     *
     * @param array $data
     * @return string
     */
    private function generateCsvContent(array $data): string
    {
        $tenMBs = 10 * 1024 * 1024;
        $handle = fopen("php://temp/maxmemory:$tenMBs", 'w');

        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
        }

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent;
    }
}
