<?php

/**
 * @author DrdLab Team
 * @package VladFlonta_WebApiLog
 */

namespace VladFlonta\WebApiLog\Block\Adminhtml;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\Data\Tree\Node;
use Magento\Store\Model\Store;

class Tree extends \Magento\Backend\Block\Template implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{

    const LIMIT = 1000;

    /**
     * @var string
     */
    protected $_template = 'VladFlonta_WebApiLog::tree.phtml';

    protected $_rootPath;
    protected $_rootFolder;
    protected $_jsonSerializer;
    protected $_config;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \VladFlonta\WebApiLog\Model\Config $config,
        \Magento\Framework\Filesystem $fileSystem,
        \VladFlonta\WebApiLog\ViewModel\JsonSerializer $jsonSerializer,
        array $data = []
    ) {
        $filePath = $fileSystem
        ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::LOG)
        ->getAbsolutePath();

        $this->_rootPath = $filePath;
        $this->_rootFolder = $config->getSavePath();
        $this->_jsonSerializer = $jsonSerializer;
        $this->_config = $config;

        parent::__construct($context, $data);
    }

    public function getElement()
    {
        $element = $this->_element;
        return $this->_element;
    }
    
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }
    
    /**
     * Get Json Representation of Resource Tree
     *
     * @return array
     */
    public function getTree()
    {
        $arrNodes = array($this->readNodes($this->_rootFolder));

        return $this->mapResources($arrNodes, array());
    }

    private function readNodes($folder, $depth = 1)
    {
        $currentPath = $this->_rootPath . DIRECTORY_SEPARATOR . $folder;
        
        $exp = explode("/", $folder);
        $title = $exp[count($exp) - 1];
        $node = array (
            "id" => $folder,
            "title" => ucfirst($title),
            "sortOrder" => 0,
            "children" => array()
        );

        if($depth < $this->_config->getFolderDepth()) {
            $files = array();
            $count = 0;
            if ($dh = opendir($currentPath)) {
                while (($file = readdir($dh)) !== false) {
                    if($file != "." && $file != "..") {
                        if($count++ > self::LIMIT) {
                            break;
                        }
                        $files[] = $file;
                    }
                }
                closedir($dh);
            }
            
            sort($files);

            foreach ($files as $key => $value) {
                if(is_dir($this->_rootPath . DIRECTORY_SEPARATOR . $folder. DIRECTORY_SEPARATOR . $value)){
                    $node["children"][] = $this->readNodes($folder. DIRECTORY_SEPARATOR . $value, ($depth+1));
                }
            }
        } else {
            $files = glob($currentPath . DIRECTORY_SEPARATOR ."*");
            $node["title"] = $node["title"] . " (" . count($files) . ")";
        }

        return $node;
    }

    /**
     *
     * @param array $resources
     * @param array $selectedResources
     * @return array
     */
    private function mapResources(array $resources, array $selectedResources = [])
    {
        $output = [];
        foreach ($resources as $resource) {
            $item = [];
            $item['id'] = $resource['id'];
            $item['li_attr']['data-id'] = $resource['id'];
            $item['text'] = __($resource['title']);
            $item['children'] = [];
            $item['state']['selected'] = in_array($item['id'], $selectedResources) ?? false;
            if (isset($resource['children'])) {
                $item['state']['opened'] = true;
                $item['children'] = $this->mapResources($resource['children'], $selectedResources);
            }
            $output[] = $item;
        }
        return $output;
    }

    public function getJsonSerializer() {
        return $this->_jsonSerializer;
    }
    
}