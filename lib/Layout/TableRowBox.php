<?php
declare(strict_types=1);
/**
 * TableRowBox class
 *
 * @package   YetiForcePDF\Layout
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Layout;

use \YetiForcePDF\Math;
use \YetiForcePDF\Style\Style;
use \YetiForcePDF\Html\Element;

/**
 * Class TableRowBox
 */
class TableRowBox extends BlockBox
{

    /**
     * We shouldn't append block box here
     */
    public function appendBlockBox($childDomElement, $element, $style, $parentBlock)
    {
    }

    /**
     * We shouldn't append table wrapper here
     */
    public function appendTableWrapperBlockBox($childDomElement, $element, $style, $parentBlock)
    {
    }

    /**
     * We shouldn't append inline block box here
     */
    public function appendInlineBlockBox($childDomElement, $element, $style, $parentBlock)
    {
    }

    /**
     * We shouldn't append inline box here
     */
    public function appendInlineBox($childDomElement, $element, $style, $parentBlock)
    {
    }

    /**
     * Create column box
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function createColumnBox()
    {
        $style = (new \YetiForcePDF\Style\Style())
            ->setDocument($this->document)
            ->parseInline();
        $box = (new TableColumnBox())
            ->setDocument($this->document)
            ->setParent($this)
            ->setStyle($style)
            ->init();
        $this->appendChild($box);
        $box->getStyle()->init();
        return $box;
    }

    /**
     * Span columns
     * @return $this
     */
    public function spanColumns()
    {
        $colSpans = [];
        foreach ($this->getChildren() as $columnIndex => $column) {
            if ($column->getColSpan() > 1) {
                $spanCount = $column->getColSpan() - 1;
                $spans = [$column];
                $currentColumn = $column;
                for ($i = 0; $i < $spanCount; $i++) {
                    $currentColumn = $currentColumn->getNext();
                    $spans[] = $currentColumn;
                }
                $colSpans[] = $spans;
            }
        }
        foreach ($colSpans as $columns) {
            $columns = array_reverse($columns);
            $source = array_pop($columns);
            $columns = array_reverse($columns);
            $spannedWidth = '0';
            foreach ($columns as $column) {
                $spannedWidth = Math::add($spannedWidth, $column->getDimensions()->getWidth());
            }
            $tableAuto = $this->getParent()->getParent()->getParent()->getStyle()->getRules('width') === 'auto';
            $separate = $column->getStyle()->getRules('border-collapse') === 'separate';
            if ($separate && $tableAuto) {
                $spannedWidth = Math::add($spannedWidth, Math::mul((string)(count($columns)), $column->getStyle()->getRules('border-spacing')));
            }
            if ($separate && $column->getNext() === null && !$tableAuto) {
                $spannedWidth = Math::sub($spannedWidth, $column->getStyle()->getRules('border-spacing'));
            }
            foreach ($columns as $column) {
                $column->getParent()->removeChild($column);
            }
            if ($source->getNext() === null) {
                $cell = $source->getFirstChild();
                $cellStyle = $cell->getStyle();
                $cellStyle->setRule('border-right-width', $cellStyle->getRules('border-left-width'));
            }
            $sourceDmns = $source->getDimensions();
            $sourceDmns->setWidth(Math::add($sourceDmns->getWidth(), $spannedWidth));
            $cell = $source->getFirstChild();
            $cell->getDimensions()->setWidth($sourceDmns->getInnerWidth());
        }
        return $this;
    }

    /**
     * Append table cell box element
     * @param \DOMElement $childDomElement
     * @param Element $element
     * @param Style $style
     * @param \YetiForcePDF\Layout\BlockBox $parentBlock
     * @return $this
     */
    public function appendTableCellBox($childDomElement, $element, $style, $parentBlock)
    {
        $colSpan = 1;
        $style->setRule('display', 'block');
        $attributeColSpan = $childDomElement->getAttribute('colspan');
        if ($attributeColSpan) {
            $colSpan = (int)$attributeColSpan;
        }
        $rowSpan = 1;
        $attributeRowSpan = $childDomElement->getAttribute('rowspan');
        if ($attributeRowSpan) {
            $rowSpan = (int)$attributeRowSpan;
        }
        $clearStyle = (new \YetiForcePDF\Style\Style())
            ->setDocument($this->document)
            ->parseInline();
        $column = (new TableColumnBox())
            ->setDocument($this->document)
            ->setParent($this)
            ->setStyle($clearStyle)
            ->init();
        $column->setColSpan($colSpan)->setRowSpan($rowSpan);
        $this->appendChild($column);
        $column->getStyle()->init()->setRule('display', 'block');
        $box = (new TableCellBox())
            ->setDocument($this->document)
            ->setParent($column)
            ->setElement($element)
            ->setStyle($style)
            ->init();
        $column->appendChild($box);
        $box->getStyle()->init();
        $colSpan--;
        for ($i = 0; $i < $colSpan; $i++) {
            $clearStyle = (new \YetiForcePDF\Style\Style())
                ->setDocument($this->document)
                ->parseInline();
            $column = (new TableColumnBox())
                ->setDocument($this->document)
                ->setParent($this)
                ->setStyle($clearStyle)
                ->init();
            $column->setColSpan(-1);
            $this->appendChild($column);
            $column->getStyle()->init()->setRule('display', 'block');
            $spanBox = (new TableCellBox())
                ->setDocument($this->document)
                ->setParent($column)
                ->setStyle(clone $style)
                ->setElement(clone $element)
                ->setSpanned(true)
                ->init();
            $column->appendChild($spanBox);
        }
        $box->buildTree($box);
        return $box;
    }


}
