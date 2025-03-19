<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Model\ResourceModel;

use VladFlonta\WebApiLog\Api\Data\WebApiErrorInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class WebApiError extends AbstractDb
{
    private const DB_TABLE = 'email_web_api_error';

    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    /**
     * WebApiError Resource Model Construct
     *
     * @param Context $context
     * @param MetadataPool $metadataPool
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        MetadataPool $metadataPool,
        string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::DB_TABLE, 'entity_id');
    }

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(WebApiErrorInterface::class)->getEntityConnection();
    }

    /**
     * Insert data
     *
     * @param array $data
     * @return void
     */
    public function insertData(array $data): void
    {
        $connection = $this->getConnection();
        $connection->insert(self::DB_TABLE, $data);
    }

    /**
     * Update data
     *
     * @param array $data
     * @param int $entityId
     * @return void
     */
    public function updateData(array $data, int $entityId): void
    {
        $connection = $this->getConnection();
        $where = ['entity_id = ?' => (int)$entityId];

        $connection->update(self::DB_TABLE, $data, $where);
    }

    /**
     * Delete recent errors
     *
     * @return void
     */
    public function deleteRecentErrors(): void
    {
        $connection = $this->getConnection();
        $connection->delete(self::DB_TABLE);
    }
}
