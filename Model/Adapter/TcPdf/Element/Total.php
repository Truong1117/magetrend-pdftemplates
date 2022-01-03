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

namespace Magetrend\PdfTemplates\Model\Adapter\TcPdf\Element;

use Magetrend\PdfTemplates\Model\Config\Source\Direction;

/**
 * Draw pdf element totals
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class Total extends \Magetrend\PdfTemplates\Model\Adapter\TcPdf\Element
{
    public $configClassName = 'Magetrend\PdfTemplates\Model\Pdf\Config\Total';

    public $columnConfig = [
        'label' => [
            'width' => 'table_column_label_width',
        ],

        'value' => [
            'width' => 'table_column_value_width',
        ],
    ];

    private $lines = [];

    public $startFromItem = 0;

    public $lastPageFooterHeight = null;

    public $lastItemY = 0;

    public function draw($pdf, $elemetData, $source, $template, $elements, $currentPage)
    {
        parent::draw($pdf, $elemetData, $source, $template, $elements, $currentPage);
        $attributes = $this->getAttributes();
        $topY = $this->removePx($attributes['top']);
        $order = $this->getOrder();
        if (empty($attributes)) {
            return $this;
        }

        if ($this->getSource() instanceof \Magento\Quote\Model\Quote) {
            $totalData = $this->totalHelper->getQuoteTotalData($attributes, $source);
        } else {
            $totalData = $this->totalHelper->getOrderTotalData($attributes, $order, $source);
        }

        $i = 1;
        $rowCount = count($totalData);
        foreach ($totalData as $total) {
            $isGrandTotal = $total['is_grand_total'];
            $rowHeight = $this->getRowHeight($total);
            $this->drawRowBackground($topY, $rowHeight, $isGrandTotal);
            $this->drawInsideBorders($topY, $rowHeight, $i == 1, $i==$rowCount);
            $this->drawLabel($total, $topY, $isGrandTotal);
            $this->drawValue($total, $topY, $isGrandTotal);
            $topY += $rowHeight;
            $this->lastItemY = $topY;
            $i++;
        }

        return $this;
    }

    public function getRowHeight($totalData)
    {
        $attributes = $this->getAttributes();
        $fontSize = $this->removePx($attributes['table_row_font_size']);
        $lineHeight = $this->removePx($attributes['table_row_line_height']);
        $fontCode = $attributes['table_row_font'];
        $color = $attributes['table_row_font_color'];
        $rowHeight = $this->removePx($attributes['table_row_height']);

        $padding = $this->getRowPadding('label');
        $columnWidth = $this->toPoint(
            $this->removePx($attributes['table_column_label_width']) - $padding[3] - $padding[1]
        );

        $label = $this->splitStringToLines(
            $totalData['label'],
            $columnWidth,
            $fontCode,
            $this->toPoint($fontSize)
        );

        $rowHeight = $rowHeight + ((count($label)-1) * $lineHeight);
        return $rowHeight;
    }

    public function drawInsideBorders($topY, $rowHeight, $isFirst, $isLast)
    {
        $attributes = $this->getAttributes();
        $i = 1;
        $columnCount = count($this->columnConfig);
        foreach ($this->columnConfig as $key => $column) {
            $topBorderSize = $attributes['table_border_inside_top_size'];
            $bottomSize = $attributes['table_border_inside_bottom_size'];
            if ($i == 1) {
                $leftBorderSize = 0;
            } else {
                $leftBorderSize = $attributes['table_border_inside_left_size'];
            }

            if ($i == $columnCount) {
                $rightBorderSize = 0;
            } else {
                $rightBorderSize = $attributes['table_border_inside_right_size'];
            }

            $this->drawBordersSolid(
                $topY,
                $this->getColumnLeft($key),
                $attributes[$column['width']],
                $rowHeight,
                [$topBorderSize, $rightBorderSize, $bottomSize, $leftBorderSize],
                $attributes['table_border_inside_color']
            );
            $i++;
        }
    }

    public function getColumnLeft($columnName)
    {
        $attributes = $this->getAttributes();
        if ($columnName == 'label') {
            if (isset($attributes['direction']) && $attributes['direction'] == Direction::RTL) {
                return $this->removePx($attributes['left'])
                    + $this->removePx($attributes['table_column_value_width']);
            } else {
                return $this->removePx($attributes['left']);
            }
        }
        if ($columnName == 'value') {
            if (isset($attributes['direction']) && $attributes['direction'] == Direction::RTL) {
                return $this->removePx($attributes['left']);
            } else {
                return $this->removePx($attributes['left'])
                    + $this->removePx($attributes['table_column_label_width']);
            }
        }
    }

    public function drawBox()
    {
        $y1 = $this->invertY($this->toPoint());
        $x1 = $this->toPoint($attributes['left']);

        $x2 = $x1 + $this->toPoint($attributes['width']);
        $y2 = $y1 - $this->toPoint($attributes['height']);

        if (isset($attributes['border_size'])) {
            $this->pdf->setLineWidth($this->toPoint($attributes['border_size']));
        }

        if (isset($attributes['border_color'])) {
            $this->pdf->setLineColor($this->getPdfColor($attributes['border_color']));
        }

        if (isset($attributes['border_style']) && $attributes['border_style'] == 'dashed') {
            $borderSize = $this->toPoint($attributes['border_size']);
            $this->pdf->setLineDashingPattern([$borderSize*2, 0, $borderSize, $borderSize], 0);
        }

        $this->pdf->drawRectangle($x1, $y1, $x2, $y2);
    }

    public function drawLabel($totalData, $y1, $isGrandTotal)
    {
        $attributes = $this->getAttributes();
        $direction = isset($attributes['direction'])?$attributes['direction']:Direction::LTR;

        if ($isGrandTotal) {
            $fontSize = $this->removePx($attributes['table_row_grand_total_font_size']);
            $lineHeight = $this->removePx($attributes['table_row_grand_total_line_height']);
            $fontCode = $attributes['table_row_grand_total_font'];
            $color = $attributes['table_row_grand_total_font_color'];
        } else {
            $fontSize = $this->removePx($attributes['table_row_font_size']);
            $lineHeight = $this->removePx($attributes['table_row_line_height']);
            $fontCode = $attributes['table_row_font'];
            $color = $attributes['table_row_font_color'];
        }

        $padding = $this->getRowPadding('label');
        $x1 = $this->getColumnLeft('label');
        $columnWidth = $this->getColumnWidth('label');

        if ($direction == Direction::RTL) {
            $columnWidth = $columnWidth - $padding[1];
        } else {
            $x1 += $padding[3];
        }

        $columnWidth = $this->toPoint($columnWidth);
        $y1 += $padding[0] + $this->removePx($attributes['table_border_inside_top_size']);

        $this->pdf->SetFont($this->getPdfFont($fontCode), '', $this->toPoint($fontSize));
        $this->setTextColor($color);

        $label = $this->splitStringToLines(
            $totalData['label'],
            $columnWidth,
            $fontCode,
            $this->toPoint($fontSize)
        );

        $this->drawTextLines(
            $label,
            $this->toPoint($x1),
            $this->toPoint($y1),
            $this->toPoint($lineHeight),
            $this->toPoint($fontSize),
            null,
            [],
            $direction,
            $columnWidth
        );
    }

    public function drawValue($totalData, $y1, $isGrandTotal)
    {
        $attributes = $this->getAttributes();
        $align = isset($attributes['value_align'])?$attributes['value_align']:'left';
        $direction = isset($attributes['direction'])?$attributes['direction']:Direction::LTR;

        if ($isGrandTotal) {
            $fontSize = $this->removePx($attributes['table_row_grand_total_font_size']);
            $lineHeight = $this->removePx($attributes['table_row_grand_total_line_height']);
            $fontCode = $attributes['table_row_grand_total_font'];
            $color = $attributes['table_row_grand_total_font_color'];
        } else {
            $fontSize = $this->removePx($attributes['table_row_font_size']);
            $lineHeight = $this->removePx($attributes['table_row_line_height']);
            $fontCode = $attributes['table_row_font'];
            $color = $attributes['table_row_font_color'];
        }

        $padding = $this->getRowPadding('value');
        $x1 = $this->getColumnLeft('value');
        $columnWidth = $this->getColumnWidth('value');

        if ($direction == Direction::RTL) {
            $columnWidth = $columnWidth - $padding[3];
        } else {
            if ($align == 'right') {
                $x1 -= $padding[1];
            } else {
                $x1 += $padding[3];
            }

        }

        $y1 += $padding[0] + $this->removePx($attributes['table_border_inside_top_size']);

        $columnWidth = $this->toPoint($columnWidth);

        $this->pdf->SetFont($this->getPdfFont($fontCode), '', $this->toPoint($fontSize));
        $this->setTextColor($color);

        $value = $this->splitStringToLines(
            str_replace('&nbsp;', ' ', $totalData['amount']),
            $columnWidth,
            $fontCode,
            $this->toPoint($fontSize)
        );

        $this->drawTextLines(
            $value,
            $this->toPoint($x1),
            $this->toPoint($y1),
            $this->toPoint($lineHeight),
            $this->toPoint($fontSize),
            $align,
            [],
            $direction,
            $columnWidth
        );
    }

    public function drawRowBackground($topY, $height, $isGrandTotal)
    {
        $this->resetLines();
        $attributes = $this->getAttributes();
        $tableWidth = $attributes['table_width'];
        if ($isGrandTotal) {
            $rowColor = $attributes['table_row_2_background'];
        } else {
            $rowColor = $attributes['table_row_1_background'];
        }

        if ($rowColor == 'rgba(0, 0, 0, 0)') {
            return;
        }

        $top = $this->getTopPosition(
            $topY,
            $attributes['left']
        );

        $bottom = $this->getBottomPosition(
            $topY,
            $attributes['left'],
            $tableWidth,
            $height
        );

        $fillColor = $this->getPdfColor($rowColor);
        $this->pdf->Rect(
            $top['x'],
            $top['y'],
            $bottom['x'] - $top['x'],
            $bottom['y'] - $top['y'],
            'DF',
            ['width' => 0],
            $fillColor
        );
    }

    public function getColumnTextLeft($columnName)
    {
        $attributes = $this->getAttributes();
        $left = $this->getColumnLeft($columnName);
        if (isset($attributes['table_header_padding_left'])) {
            $left+= $this->toPoint($attributes['table_header_padding_left']);
        }

        return $left;
    }

    public function getColumnWidth($columnName)
    {
        $attributes = $this->getAttributes();
        return $this->removePx($attributes['table_column_'.$columnName.'_width']);
    }

    public function getInvoiceItems()
    {
        return $this->invoiceItems;
    }

    /**
     * Returns row cell padding options
     *
     * @return array
     */
    public function getRowPadding($columnName = 'label')
    {
        $attributes = $this->getAttributes();
        return [
            $this->removePx($attributes['table_column_'.$columnName.'_padding_top']),
            $this->removePx($attributes['table_column_'.$columnName.'_padding_right']),
            $this->removePx($attributes['table_column_'.$columnName.'_padding_bottom']),
            $this->removePx($attributes['table_column_'.$columnName.'_padding_left'])
        ];
    }


    /**
     * Returns last item bottom Y line
     *
     * @return int
     */
    public function getLastItemY()
    {
        return $this->lastItemY;
    }

    /**
     * Sort totals list
     *
     * @param  array $a
     * @param  array $b
     * @return int
     */
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

    /**
     * Returns information about element position and sizes
     *
     * @param $elementData
     * @return array
     */
    public function getInfo($elementData)
    {
        $attributes = $this->getAttributes();
        $info = [
            'design_top' => $this->removePx($elementData['attributes']['top']),
            'design_left' => $this->removePx($elementData['attributes']['left']),
            'design_width' => $this->removePx($elementData['attributes']['table_width']),
            'design_height' => $this->removePx($elementData['attributes']['table_height']),
            'pdf_top' => $this->removePx($attributes['top']),
            'pdf_left' => $this->removePx($attributes['left']),
            'pdf_width' => $this->removePx($attributes['table_width']),
            'pdf_height' => ($this->getLastItemY() - $attributes['top']),
            'pdf_bottom_line' => $this->getLastItemY()
        ];

        if (isset($attributes['depends_on']) && !empty($attributes['depends_on'])) {
            $info['depends'] = $attributes['depends_on'];
        }

        return $info;
    }

    public function applyCustomSortOrder($totals)
    {
        $customSortOrder = $this->moduleHelper->getTotalsSorting();
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

    public function canDisplay($totalData)
    {
        return true;
    }
}
