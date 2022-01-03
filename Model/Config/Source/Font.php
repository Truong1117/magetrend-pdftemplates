<?php
/**
 * MB "Vienas bitas" (Magetrend.com)
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */

namespace Magetrend\PdfTemplates\Model\Config\Source;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Font list source class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class Font implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    public $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    public $readFactory;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    public $repository;

    /**
     * @var \Magetrend\PdfTemplates\Helper\Data
     */
    public $moduleHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * Font constructor.
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
     * @param \Magento\Framework\View\Asset\Repository $repository
     * @param \Magetrend\PdfTemplates\Helper\Data $moduleHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        \Magento\Framework\View\Asset\Repository $repository,
        \Magetrend\PdfTemplates\Helper\Data $moduleHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->fileSystem = $fileSystem;
        $this->readFactory = $readFactory;
        $this->repository = $repository;
        $this->moduleHelper = $moduleHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->toArray();
        $optionArray = [];
        foreach ($options as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $fontList = $this->getFontList();
        if (empty($fontList)) {
            return [];
        }

        $opions = [];
        foreach ($fontList as $item) {
            $opions[$item['code']] = __($item['label']);
        }

        $opions = array_merge($opions, $this->getZendFonts());
        
        return $opions;
    }

    public function getZendFonts()
    {
        return [
            \Zend_Pdf_Font::FONT_COURIER => 'Courier',
            \Zend_Pdf_Font::FONT_COURIER_BOLD => 'Courier Bold',
            \Zend_Pdf_Font::FONT_COURIER_ITALIC => 'Courier Italic',
            \Zend_Pdf_Font::FONT_COURIER_BOLD_ITALIC => 'Courier Bold Italic',
            \Zend_Pdf_Font::FONT_HELVETICA => 'Helvetica',
            \Zend_Pdf_Font::FONT_HELVETICA_BOLD => 'Helvetica Bold',
            \Zend_Pdf_Font::FONT_HELVETICA_ITALIC => 'Helvetica Italic',
            \Zend_Pdf_Font::FONT_HELVETICA_BOLD_ITALIC => 'Helvetica Bold Italic',
            \Zend_Pdf_Font::FONT_SYMBOL => 'Symbol',
            \Zend_Pdf_Font::FONT_TIMES_ROMAN => 'Times Roman',
            \Zend_Pdf_Font::FONT_TIMES_BOLD => 'Times Bold',
            \Zend_Pdf_Font::FONT_TIMES_ITALIC => 'Times Italic',
            \Zend_Pdf_Font::FONT_TIMES_BOLD_ITALIC => 'Times Bold Italic',
        ];
    }

    public function getFontList()
    {
        $fontDir = rtrim($this->moduleHelper->getModuleViewDirectory('/adminhtml/web/fonts/pdf'), '/').'/';
        $fontDir2 = rtrim($this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath('mt/font'), '/').'/';
        return array_merge(
            $this->getFontListFromDirectory($fontDir),
            $this->getFontListFromDirectory($fontDir2, 'media')
        );
    }

    /**
     * Returns font list
     *
     * @return array
     */
    public function getFontListFromDirectory($fontDir, $type = 'default')
    {
        $list = [];
        $dir = $this->readFactory->create($fontDir);
        if (!$dir->isDirectory()) {
            return [];
        }

        $fileList = $dir->read();
        if (!empty($fileList)) {
            foreach ($fileList as $fileName) {
                $extension = explode('.', $fileName);
                if (in_array(strtolower(end($extension)), ['ttf'])) {
                    if ($type == 'media') {
                        $mediaUrl = rtrim($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/').'/';
                        $url = $mediaUrl.'mt/font/'.$fileName;
                    } else {
                        $url = $this->repository->getUrlWithParams(
                            'Magetrend_PdfTemplates::fonts/pdf/'.$fileName,
                            ['area' => 'adminhtml']
                        );
                    }


                    $list[] = [
                        'path' => $fontDir.$fileName,
                        'fileName' => $fileName,
                        'url' => $url,
                        'code' => str_replace('.ttf', 'PdfFont', $fileName),
                        'label' => ucfirst(str_replace('.ttf', '', $fileName)),
                        'type' => $type
                    ];
                }
            }
        }

        return $list;
    }
}
