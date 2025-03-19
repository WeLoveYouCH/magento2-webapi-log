<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Model;

use VladFlonta\WebApiLog\Model\ResourceModel\WebApiError as WebApiErrorResourceModel;
use Magento\Framework\Model\AbstractModel;

class WebApiError extends AbstractModel
{
    /**
     * Construct web api error model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(WebApiErrorResourceModel::class);
    }
}
