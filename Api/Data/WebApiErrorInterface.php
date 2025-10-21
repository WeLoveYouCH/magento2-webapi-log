<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Api\Data;

interface WebApiErrorInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ERROR_CODE = 'error_code';
    public const ERROR_CONTENT = 'error_content';
    public const DETAILS = 'details';
    public const URL = 'url';
    public const COUNT = 'count';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get error entity Id
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string;

    /**
     * Set error code
     *
     * @param string $errorCode
     */
    public function setErrorCode(string $errorCode);

    /**
     * Get error content
     *
     * @return string
     */
    public function getErrorContent(): string;

    /**
     * Set error content
     *
     * @param string $errorContent
     */
    public function setErrorContent(string $errorContent);

    /**
     * Get details
     *
     * @return string|null
     */
    public function getDetails(): ?string;

    /**
     * Set details
     *
     * @param string|null $details
     */
    public function setDetails(?string $details);

    /**
     * Get URL
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set URL
     *
     * @param string $url
     */
    public function setUrl(string $url);

    /**
     * Get count
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Set count
     *
     * @param int $count
     */
    public function setCount(int $count);

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
