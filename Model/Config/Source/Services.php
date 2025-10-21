<?php
/**
 * @package     VladFlonta\WebApiLog
 * @author      Vlad Flonta
 * @copyright   Copyright © 2022
 * @license     https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace VladFlonta\WebApiLog\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Webapi\Model\Config;
use Magento\Webapi\Model\Config\Converter;

class Services implements ArrayInterface
{
    /** @var Config */
    protected Config $config;

    /** @var array */
    protected array $options;

    /**
     * Services constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Get options in "value-label" format
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->getOptionsArray($this->getOptions());
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getOptions();
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        if (!isset($this->options)) {
            $this->options = [];
            $this->options[(string)__('-- None --')] = '';
            $options = $this->config->getServices()[Converter::KEY_ROUTES];
            foreach ($options as $route => $methods) {
                $label = trim(ucwords(preg_replace('/^\/([^\/]+)\/([^\/]+).*/', '$1 $2', $route)));
                $this->options[$label][$route] = trim($route, '/');
            }
        }

        return $this->options;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getOptionsArray(array $options): array
    {
        $optionArray = [];
        foreach ($options as $label => $value) {
            $optionArray[] = [
                'value' => is_array($value) ? $this->getOptionsArray($value) : $value,
                'label' => $label,
            ];
        }
        return $optionArray;
    }
}
