<?php
/**
 * @package     VladFlonta\WebApiLog
 * @author      Vlad Flonta
 * @copyright   Copyright © 2018
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace VladFlonta\WebApiLog\Plugin\Rest;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Webapi\Controller\Rest;
use Psr\Log\LoggerInterface;
use VladFlonta\WebApiLog\Logger\Handler;
use VladFlonta\WebApiLog\Model\Config;
use VladFlonta\WebApiLog\Model\Service\Resolver;
use Magento\Framework\MessageQueue\PublisherInterface;

class Api
{
    /**
     * Rest constructor.
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Handler $apiLogger
     * @param RequestInterface $request
     * @param Resolver $serviceResolver
     * @param PublisherInterface $publisher
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Config $config,
        private readonly Handler $apiLogger,
        private readonly RequestInterface $request,
        private readonly Resolver $serviceResolver,
        private readonly PublisherInterface $publisher
    ) {
    }

    /**
     * @param \Magento\Webapi\Controller\Rest $subject
     * @param callable $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(
        \Magento\Webapi\Controller\Rest $subject,
        callable $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if (!$this->config->getEnable() || $this->serviceResolver->isExcluded()) {
            return $proceed($request);
        }
        try {
            $this->currentRequest = [
                'is_api' => true,
                'is_auth' => $this->isAuthorizationRequest($request->getPathInfo()),
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                    'version' => $request->getVersion(),
                    'headers' => [],
                    'body' => '',
                ],
                'response' => [
                    'headers' => [],
                    'body' => '',
                ],
                'start' => microtime(true),
                'uid' => uniqid(),
            ];
            $currentRequest = &$this->currentRequest['request'];
            foreach ($request->getHeaders()->toArray() as $key => $value) {
                switch($key) {
                    case "Authorization":
                        preg_match('/^(?<type>\S+)\s(?<data>\S+)/', $value, $info);
                        if (count($info) !== 5) {
                            $currentRequest['headers'][$key] = 'SHA256:'.hash('sha256', $value);
                        } else {
                            $currentRequest['headers'][$key] = $info['type'].' SHA256:'.hash('sha256', $info['data']);
                        }
                        break;
                    default:
                        $currentRequest['headers'][$key] = $value;
                }
            }
            $currentRequest['body'] = $this->currentRequest['is_auth'] ?
                'Request body is not available for authorization requests.' :
                $request->getContent();
        } catch (\Exception $exception) {
            $this->logger->debug(sprintf(
                'Exception when logging API request: %s (%s::%s)',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }

        return $proceed($request);
    }

    /**
     * @param \Magento\Framework\Webapi\Rest\Response $subject
     * @param $result
     * @return mixed
     */
    public function afterSendResponse(
        \Magento\Framework\Webapi\Rest\Response $subject,
        $result
    ) {
        if (!$this->config->getEnable() || $this->serviceResolver->isExcluded()) {
            return $result;
        }
        try {
            $this->currentRequest['response']['is_exception'] = $subject->isException();
            foreach ($subject->getHeaders()->toArray() as $key => $value) {
                $this->currentRequest['response']['headers'][$key] = $value;
            }
            $this->currentRequest['response']['body'] = $this->currentRequest['is_auth'] ?
                'Response body is not available for authorization requests.' :
                $subject->getBody();
            $this->currentRequest['end'] = microtime(true);
            $this->currentRequest['time'] = $this->currentRequest['end'] - $this->currentRequest['start'];
            $this->apiLogger->debug('', $this->currentRequest);
        } catch (\Exception $exception) {
            $this->logger->debug('Exception when logging API response: ' . $exception->getMessage());
        }

        return $result;
    }

    /**
     * Plugin after REST API dispatch - send email after exception
     *
     * @param Rest $subject
     * @param ResponseInterface $result
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function afterDispatch(
        Rest $subject,
        ResponseInterface $result,
        RequestInterface $request
    ): ResponseInterface {
        if (!$this->config->isEmailNotificationEnabled() || $this->serviceResolver->isExcluded()) {
            return $result;
        }

        $exceptions = $result->getException();

        if (!empty($exceptions)) {
            try {
                foreach ($exceptions as $exception) {
                    $errorData = [
                        'error_code' => method_exists($exception, 'getHttpCode') ? $exception->getHttpCode() : 500,
                        'error_content' => $exception->getMessage(),
                        'details' => $exception->getTraceAsString(),
                        'url' => $request->getRequestUri()
                    ];

                    $this->publisher->publish('webapi.error', json_encode($errorData));
                }
            } catch (Exception $e) {
                $this->logger->error(
                    __('Error occurred while trying to send web API error notification: ' . $e->getMessage(), $e)
                );
            }
        }

        return $result;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isAuthorizationRequest($path)
    {
        return preg_match('/integration\/(admin|customer)\/token/', $path) !== 0;
    }
}
