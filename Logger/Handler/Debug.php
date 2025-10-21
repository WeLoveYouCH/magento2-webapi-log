<?php
/**
 * @package     VladFlonta\WebApiLog
 * @author      Vlad Flonta
 * @copyright   Copyright © 2018
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace VladFlonta\WebApiLog\Logger\Handler;

use LogicException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use VladFlonta\WebApiLog\Model\Config;

class Debug extends \Magento\Framework\Logger\Handler\Debug
{
    /** @var string */
    private ?string $errorMessage;

    /** @var string */
    protected $fileName = '';

    /**
     * @param Config $config
     * @param DriverInterface $filesystem
     * @param Filesystem $fileSystem
     * @param null $filePath
     * @param null $fileName
     */
    public function __construct(
        Config $config,
        DriverInterface $filesystem,
        Filesystem $fileSystem,
        $filePath = null,
        $fileName = null
    ) {
        $filePath = $fileSystem
                ->getDirectoryRead(DirectoryList::LOG)
                ->getAbsolutePath() . $config->getSavePath();
        parent::__construct($filesystem, $filePath, '');
    }

    /**
     * @param array $record
     * @throws LocalizedException
     */
    protected function write(array $record): void
    {
        if (!isset($record['context']['is_api']) || !$record['context']['is_api']) {
            parent::write($record);
            return;
        }
        $result = preg_match('/\/V1\/([^?]*)/', $record['context']['request']['uri'], $matches);
        $url = sprintf(
            '%s/%s/%s.%x.log',
            $this->url,
            $result && count($matches) && $matches[1] ? trim($matches[1], '/') : 'default',
            $record['datetime']->format('Ymd_His'),
            crc32(serialize($record['context']))
        );

        if (!$url) {
            throw new LogicException('Missing stream url, the stream can not be opened.');
        }

        $logDir = $this->filesystem->getParentDirectory($url);
        if (!$this->filesystem->isDirectory($logDir)) {
            $this->filesystem->createDirectory($logDir);
        }

        $this->errorMessage = null;
        set_error_handler(array($this, 'customErrorHandler'));
        $stream = fopen($url, 'a');
        if ($this->filePermission !== null) {
            @chmod($url, $this->filePermission);
        }
        restore_error_handler();
        if (!is_resource($stream)) {
            throw new LocalizedException(
                __('The stream or file "%1" could not be opened: %2', $url, $this->errorMessage)
            );
        }

        if ($this->useLocking) {
            flock($stream, LOCK_EX);
        }

        $request = $record['context']['request'];
        $data = sprintf("%s %s HTTP %s\n\n", $request['method'], $request['uri'], $request['version']);
        foreach ($record['context']['request']['headers'] as $key => $value) {
            $data .= sprintf("%s: %s\n", $key, $value);
        }
        $data .= sprintf("\n%s\n\n", $request['body']);
        foreach ($record['context']['response']['headers'] as $key => $value) {
            $data .= sprintf("%s: %s\n", $key, $value);
        }
        $data .= sprintf("\n%s\n", $record['context']['response']['body']);

        fwrite($stream, $data);

        if ($this->useLocking) {
            flock($stream, LOCK_UN);
        }

        fclose($stream);
    }

    /**
     * @param $code
     * @param $msg
     */
    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
    }
}
