<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Model\Consumer;

use VladFlonta\WebApiLog\Model\ResourceModel\WebApiError;
use Psr\Log\LoggerInterface;

class ErrorConsumer
{
    /**
     * @var WebApiError
     */
    protected WebApiError $errorResourceModel;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param WebApiError $errorResourceModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        WebApiError $errorResourceModel,
        LoggerInterface $logger,
    ) {
        $this->errorResourceModel = $errorResourceModel;
        $this->logger = $logger;
    }

    /**
     * Process consumer
     *
     * @param string $message
     * @return void
     */
    public function process(string $message): void
    {
        $connection = $this->errorResourceModel->getConnection();

        $data = json_decode($message, true);
        $errorCode = $data['error_code'];
        $errorContent = $data['error_content'];
        $url = $data['url'];

        try {
            $select = $connection->select()
                ->from('email_web_api_error', ['entity_id', 'count'])
                ->where('error_code = ?', $errorCode)
                ->where('error_content = ?', $errorContent)
                ->where('url = ?', $url);

            $existingError = $connection->fetchRow($select);

            if ($existingError) {
                $newCount = (int) $existingError['count'] + 1;
                $this->errorResourceModel->updateData(['count' => $newCount], (int)$existingError['entity_id']);
            } else {
                $this->errorResourceModel->insertData([
                    'error_code' => $errorCode,
                    'error_content' => $errorContent,
                    'details' => $data['details'] ?? null,
                    'url' => $url,
                    'count' => 1,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__('Error occurred while processing web API error: ' . $e->getMessage(), $e));
        }
    }
}
