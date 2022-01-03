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

namespace Magetrend\PdfTemplates\Block\Adminhtml\Mteditor\Element;

/**
 * Pdf element config class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class Total extends \Magetrend\PdfTemplates\Block\Adminhtml\Mteditor\Element
{
    public function getTotalConfig()
    {
        $template = $this->coreRegistry->registry('current_pdf_template');
        if ($template && $template->getId()) {
            $storeId = $template->getStoreId();
        }

        $totals = $this->totalHelper->getAvailableTotals($storeId, $template->getType());
        return $totals;
    }
}
