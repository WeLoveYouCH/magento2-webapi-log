<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace VladFlonta\WebApiLog\ViewModel;

use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class JsonSerializer implements ArgumentInterface
{
    /**
     * @var JsonHexTag
     */
    private JsonHexTag $serializer;

    /**
     * @param JsonHexTag $serializer
     */
    public function __construct(JsonHexTag $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Returns serialized version of data
     *
     * @param array $data
     * @return string
     */
    public function serialize(array $data): string
    {
        return $this->serializer->serialize($data);
    }
}
