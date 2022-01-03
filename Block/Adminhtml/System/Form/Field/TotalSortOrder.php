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

namespace Magetrend\PdfTemplates\Block\Adminhtml\System\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;

/**
 * PDF - Customer Field class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class TotalSortOrder extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    public $sourceField;

    /**
     * Prepare to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {

        $this->addColumn(
            'source_field',
            [
                'label' => __('Source Field'),
                'renderer' => $this->getSourceFieldRenderer()
            ]
        );


        $this->addColumn(
            'sort_order',
            [
                'label' => __('Sort Order')
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    public function getSourceFieldRenderer()
    {
        if (!$this->sourceField) {
            $storeId = $this->_request->getParam('store', 0);
            $this->sourceField = $this->getLayout()->createBlock(
                '\Magetrend\PdfTemplates\Block\Adminhtml\System\Form\Field\SourceField',
                '',
                ['data' => ['is_render_to_js_template' => true, 'store_id' => $storeId]]
            );
        }

        return $this->sourceField;
    }

    /**
     * Prepare existing row data object.
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];
        $sourceField = $row->getData('source_field');

        $key = 'option_' . $this->getSourceFieldRenderer()->calcOptionHash($sourceField);
        $options[$key] = 'selected="selected"';

        $row->setData('option_extra_attrs', $options);
    }
}