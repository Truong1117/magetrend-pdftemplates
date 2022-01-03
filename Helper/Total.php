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

namespace Magetrend\PdfTemplates\Helper;

class Total
{
    public $totalConfig;

    public $scopeConfig;

    public $moduleHelper;

    public $pdfConfig;

    public $pdfTotalFactory;

    public function __construct(
        \Magento\Sales\Model\Order\Pdf\Config $totalConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magetrend\PdfTemplates\Helper\Data $moduleHelper,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
    ) {
        $this->totalConfig = $totalConfig;
        $this->scopeConfig = $scopeConfig;
        $this->moduleHelper = $moduleHelper;
        $this->pdfConfig = $pdfConfig;
        $this->pdfTotalFactory = $pdfTotalFactory;
    }

    public function getOrderTotalData($attributes, $order, $source)
    {
        $collectedTotals = [];
        $totals = $this->getTotalsList();
        $totalsConfig = $this->pdfConfig->getTotals();
        $grandTotalModel = $totalsConfig['grand_total']['model'];
        $displayFullTaxSummary = $this->moduleHelper->isTaxSummaryEnabled($order->getStoreId());

        foreach ($totals as $total) {
            $total->setOrder($order)->setSource($source);
            if ($total->canDisplay()) {
                $subtotals = [];
                $isGrandTotal = $total instanceof $grandTotalModel;
                foreach ($total->getTotalsForDisplay() as $i => $totalData) {
                    if (!$this->canDisplay($totalData)) {
                        continue;
                    }
                    $totalData['is_grand_total'] = $isGrandTotal;
                    if (!isset($totalData['source_field'])) {
                        $totalData['source_field'] = $total['source_field'].'_0';
                    }
                    $subtotals[] = $totalData;
                }
                $collectedTotals[$total['source_field']] = $subtotals;
            }
        }

        $collectedTotals = $this->assignSourceField($collectedTotals, $order->getStoreId());

        $totals = [];
        foreach ($collectedTotals as $key => $subTotals) {
            foreach ($subTotals as $totalData) {

                $sourceField = $totalData['source_field'];
                if (isset($attributes['hide_row_'.$sourceField])
                    && ($attributes['hide_row_'.$sourceField] == 'true' || $attributes['hide_row_'.$sourceField] == 1)) {
                    continue;
                }

                if (isset($attributes['translate_'.$sourceField])) {
                    if (in_array($sourceField, ['tax_amount_1', 'grand_total_2'])) {

                        if (!isset($totalData['percent'])) {
                            continue;
                        }

                        $precision = 2;
                        if (number_format($totalData['percent'], 2) == number_format($totalData['percent'], 0)) {
                            $precision = 0;
                        }

                        $totalData['label'] = (string)__(
                            $attributes['translate_'.$sourceField],
                            number_format($totalData['percent'], $precision)
                        );

                    } else {
                        $totalData['label'] = $attributes['translate_'.$sourceField];
                    }
                } else {
                    if (in_array($sourceField, ['tax_amount_1', 'grand_total_2'])) {

                        if (!isset($totalData['percent'])) {
                            continue;
                        }

                        $precision = 2;
                        if (number_format($totalData['percent'], 2) == number_format($totalData['percent'], 0)) {
                            $precision = 0;
                        }
                        $totalData['label'] = (string)__(
                            $totalData['label'],
                            number_format($totalData['percent'], $precision)
                        );
                    } else {
                        $totalData['label'] = __($totalData['label']);
                    }
                }

                $totalData['label'] = str_replace('(%1%)', '', $totalData['label']);
                $totals[] = $totalData;
            }
        }

        $totals = $this->applyCustomSortOrder($totals, $order->getStoreId());
        usort($totals, [$this, 'sortTotalsList']);
        return $totals;
    }

    public function getQuoteTotalData($attributes, $quote)
    {
        $quote->collectTotals();
        $totals = $quote->getTotals();
        $totalData = [];

        if (empty($totals)) {
            return [];
        }

        $currencyCode = $this->moduleHelper->getCurrencyCode($quote->getStoreId());
        foreach ($totals as $total) {
            $total->setQuote($quote)->setSource($quote);
            if ($total->getValue() == 0) {
                continue;
            }

            $sourceField = $total->getCode().'_0';
            if (isset($attributes['hide_row_'.$sourceField])
                && ($attributes['hide_row_'.$sourceField] == 'true' || $attributes['hide_row_'.$sourceField] == 1)) {
                continue;
            }

            if (isset($attributes['translate_'.$sourceField])) {
                $label = $attributes['translate_'.$sourceField];
            } else {
                $label = (string)__($total->getTitle());
            }

            $totalData[] = [
                'amount' => $this->moduleHelper->formatPrice($currencyCode, $total->getValue()),
                'label' => $label,
                'code' => $total->getCode(),
                'is_grand_total' => ($total->getCode() == 'grand_total'?1:0),
                'source_field' => $sourceField
            ];
        }

        $totalData = $this->applyCustomSortOrder($totalData);
        usort($totalData, [$this, 'sortTotalsList']);

        return $totalData;
    }


    public function getTotalsList()
    {
        $totals = $this->pdfConfig->getTotals();
        $totalModels = [];
        foreach ($totals as $totalInfo) {
            $class = empty($totalInfo['model']) ? null : $totalInfo['model'];
            $totalModel = $this->pdfTotalFactory->create($class);
            $totalModel->setData($totalInfo);
            $totalModels[] = $totalModel;
        }

        return $totalModels;
    }

    public function getAvailableTotals($store = null, $type = null, $sort = true)
    {
        $totals = $this->getTotalsConfig($store, $type, $sort);
        if ($sort) {
            $totals = $this->applyCustomSortOrder($totals, $store);
            usort($totals, [$this, 'sortTotalsList']);
        }
        return $totals;
    }

    public function getTotalsConfig($store = null, $type = null, $sort = true)
    {
        $config = $this->totalConfig->getTotals();
        if (empty($config)) {
            return [];
        }

        $dummyValues = $this->getDummyValues();
        $totals = [];
        foreach ($config as $key => $total) {
            if (!isset($dummyValues[$key])) {
                $total['dummy_value'] = '$0.00';
            } else {
                $total['dummy_value'] = $dummyValues[$key];
            }

            $total['source_field'] = $total['source_field'].'_0';
            $totals[$total['source_field']] = $total;
        }

        if ($type != 'creditmemo' && $type != 'all') {
            if (isset($totals['adjustment_negative_0'])) {
                unset($totals['adjustment_negative_0']);
            }

            if (isset($totals['adjustment_positive_0'])) {
                unset($totals['adjustment_positive_0']);
            }
        }

        $totals = $this->addAdditionalSubtotal($totals, $store);
        $totals = $this->addAdditionalShipping($totals, $store);
        $totals = $this->addAdditionalTax($totals, $store);
        $totals = $this->addAdditionalGrandTotal($totals, $store);
        $totals = $this->unsetWeeeAmount($totals, $store);
        return $totals;

    }

    public function unsetWeeeAmount($totals, $store = null)
    {
        $isFTPEnabled = $this->scopeConfig->getValue(
            'tax/wee/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$isFTPEnabled) {
            unset($totals['weee_amount_0']);
        }

        return $totals;
    }

    public function addAdditionalShipping($totals, $store = null)
    {
        $includeTaxInOShipping = $this->scopeConfig->getValue(
            'tax/sales_display/shipping',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if ($includeTaxInOShipping != 3) {
            return $totals;
        }

        $shipping = $totals['shipping_amount_0'];
        unset($totals['shipping_amount_0']);

        $totals['shipping_amount_1'] = $shipping;
        $totals['shipping_amount_1']['title'] = 'Shipping (Excl. Tax):';
        $totals['shipping_amount_1']['source_field'] = 'shipping_amount_1';

        $totals['shipping_amount_2'] = $shipping;
        $totals['shipping_amount_2']['title'] = 'Shipping (Incl. Tax):';
        $totals['shipping_amount_2']['source_field'] = 'shipping_amount_2';

        return $totals;
    }

    public function addAdditionalTax($totals, $store = null)
    {
        $includeTaxInOrderTotal = $this->scopeConfig->getValue(
            'tax/sales_display/grandtotal',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $displayFullTaxSummary = $this->scopeConfig->getValue(
            'tax/sales_display/full_summary',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );


        if ($includeTaxInOrderTotal) {
            if (isset($totals['tax_amount_0'])) {
                unset($totals['tax_amount_0']);
            }

            return $totals;
        }

        if ($displayFullTaxSummary) {
            $taxAmount = $totals['tax_amount_0'];
            unset($totals['tax_amount_0']);
            $totals['tax_amount_1'] = $taxAmount;
            $totals['tax_amount_1']['title'] = 'Tax (%1%) :';
            $totals['tax_amount_1']['source_field'] = 'tax_amount_1';
        }
        return $totals;
    }

    public function addAdditionalSubtotal($totals, $store = null)
    {
        $includeTaxInOSubtotal = $this->scopeConfig->getValue(
            'tax/sales_display/subtotal',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if ($includeTaxInOSubtotal != 3) {
            return $totals;
        }

        $subtotal = $totals['subtotal_0'];
        unset($totals['subtotal_0']);

        $totals['subtotal_1'] = $subtotal;
        $totals['subtotal_1']['title'] = 'Subtotal (Excl. Tax):';
        $totals['subtotal_1']['source_field'] = 'subtotal_1';

        $totals['subtotal_2'] = $subtotal;
        $totals['subtotal_2']['title'] = 'Subtotal (Incl. Tax):';
        $totals['subtotal_2']['source_field'] = 'subtotal_2';

        return $totals;
    }

    public function addAdditionalGrandTotal($totals, $store = null)
    {
        $includeTaxInOrderTotal = $this->scopeConfig->getValue(
            'tax/sales_display/grandtotal',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $displayFullTaxSummary = $this->scopeConfig->getValue(
            'tax/sales_display/full_summary',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$includeTaxInOrderTotal) {
            return $totals;
        }

        $grandTotal = $totals['grand_total_0'];
        unset($totals['grand_total_0']);



        $totals['grand_total_1'] = $grandTotal;
        $totals['grand_total_1']['title'] = 'Grand Total (Excl. Tax) :';
        $totals['grand_total_1']['source_field'] = 'grand_total_1';


        if ($displayFullTaxSummary) {
            $totals['grand_total_2']['title'] = 'Tax (%1%) :';
            $totals['grand_total_2']['source_field'] = 'grand_total_2';
            $totals['grand_total_2']['dummy_value'] = '$0.00';
        } else {
            $totals['grand_total_3']['title'] = 'Tax :';
            $totals['grand_total_3']['source_field'] = 'grand_total_3';
            $totals['grand_total_3']['dummy_value'] = '$0.00';
        }

        $totals['grand_total_4'] = $grandTotal;
        $totals['grand_total_4']['title'] = 'Grand Total (Incl. Tax) :';
        $totals['grand_total_4']['source_field'] = 'grand_total_4';

        return $totals;
    }

    public function getDummyValues()
    {
        return [
            'subtotal' => '$216.60',
            'tax_amount' => '$72.20',
            'shipping_amount' => '$0.00',
            'grand_total' => '$288.80',
        ];
    }

    public function applyCustomSortOrder($totals, $store = null)
    {
        $customSortOrder = $this->moduleHelper->getTotalsSorting($store);
        if (empty($totals) || empty($customSortOrder)) {
            return $totals;
        }

        $i = 1;
        foreach ($totals as $key => $total) {
            if (isset($totals[$key]['sort_order'])) {
                continue;
            }

            $totals[$key]['sort_order'] = $i++;
        }

        foreach ($totals as $key => $total) {
            if (!isset($total['source_field'])) {
                continue;
            }

            $sourceField = $total['source_field'];
            if (!isset($customSortOrder[$sourceField])) {
                continue;
            }

            $totals[$key]['sort_order'] = $customSortOrder[$sourceField];
        }

        return $totals;
    }

    public function sortTotalsList($a, $b)
    {
        if (!isset($a['sort_order']) || !isset($b['sort_order'])) {
            return 0;
        }

        if ($a['sort_order'] == $b['sort_order']) {
            return 0;
        }

        return $a['sort_order'] > $b['sort_order'] ? 1 : -1;
    }

    public function assignSourceField($collectedTotals, $storeId)
    {
        if (empty($collectedTotals)) {
            return $collectedTotals;
        }

        $displayFullTaxSummary = $this->scopeConfig->getValue(
            'tax/sales_display/full_summary',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $includeTaxInOrderTotal = $this->scopeConfig->getValue(
            'tax/sales_display/grandtotal',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $collectedTotals = $this->assignSubtotalSource($collectedTotals);
        $collectedTotals = $this->assignShippingSource($collectedTotals);
        $collectedTotals = $this->assignTaxSource($collectedTotals, $displayFullTaxSummary, $includeTaxInOrderTotal);
        $collectedTotals = $this->assignGrandTotalSource($collectedTotals, $displayFullTaxSummary, $includeTaxInOrderTotal);

        return $collectedTotals;
    }

    public function assignSubtotalSource($collectedTotals)
    {
        if (!isset($collectedTotals['subtotal'])) {
            return $collectedTotals;
        }

        $lineCount = count($collectedTotals['subtotal']);
        if ($lineCount == 1) {
            $collectedTotals['subtotal'][0]['source_field'] = 'subtotal_0';
            return $collectedTotals;
        }

        $collectedTotals['subtotal'][0]['source_field'] = 'subtotal_1';
        $collectedTotals['subtotal'][1]['source_field'] = 'subtotal_2';

        return $collectedTotals;

    }

    public function assignShippingSource($collectedTotals)
    {
        if (!isset($collectedTotals['shipping_amount'])) {
            return $collectedTotals;
        }

        $lineCount = count($collectedTotals['shipping_amount']);
        if ($lineCount == 1) {
            $collectedTotals['shipping_amount'][0]['source_field'] = 'shipping_amount_0';
            return $collectedTotals;
        }

        $collectedTotals['shipping_amount'][0]['source_field'] = 'shipping_amount_1';
        $collectedTotals['shipping_amount'][1]['source_field'] = 'shipping_amount_2';

        return $collectedTotals;

    }

    public function assignTaxSource($collectedTotals, $displayFullTaxSummary, $includeTaxInOrderTotal)
    {
        if (!isset($collectedTotals['tax_amount'])) {
            return $collectedTotals;
        }

        if ($includeTaxInOrderTotal) {
            unset($collectedTotals['tax_amount']);
            return $collectedTotals;
        }

        $lineCount = count($collectedTotals['tax_amount']);
        if ($lineCount == 1) {
            $collectedTotals['tax_amount'][0]['source_field'] = 'tax_amount_0';
            return $collectedTotals;
        }

        foreach ($collectedTotals['tax_amount'] as $i => $row) {
            if (!isset($row['percent'])) {
                unset($collectedTotals['tax_amount'][$i]);
                continue;
            }

            $sourceField = 'tax_amount_1';
            $collectedTotals['tax_amount'][$i]['source_field'] = $sourceField;
        }

        return $collectedTotals;
    }

    public function assignGrandTotalSource($collectedTotals, $displayFullTaxSummary, $includeTaxInOrderTotal)
    {
        if (!isset($collectedTotals['grand_total'])) {
            return $collectedTotals;
        }

        $lineCount = count($collectedTotals['grand_total']);

        if ($lineCount == 1) {
            $collectedTotals['grand_total'][0]['source_field'] = 'grand_total_0';
            return $collectedTotals;
        }

        foreach ($collectedTotals['grand_total'] as $i => $total) {
            if ($i == 0) {
                $sourceField = 'grand_total_1';
            } elseif ($i == $lineCount - 1) {
                $sourceField = 'grand_total_4';
            } else {
                if ($displayFullTaxSummary) {
                    $sourceField = 'grand_total_2';
                } else {
                    $sourceField = 'grand_total_3';
                }
            }

            $collectedTotals['grand_total'][$i]['source_field'] = $sourceField;
        }

        return $collectedTotals;
    }


    public function canDisplay($totalData)
    {
        return true;
    }
}