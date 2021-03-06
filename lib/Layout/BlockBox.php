<?php
declare(strict_types=1);
/**
 * BlockBox class
 *
 * @package   YetiForcePDF\Layout
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Layout;

use \YetiForcePDF\Style\Style;
use \YetiForcePDF\Html\Element;
use \YetiForcePDF\Layout\Coordinates\Coordinates;
use \YetiForcePDF\Layout\Coordinates\Offset;
use \YetiForcePDF\Layout\Dimensions\BoxDimensions;
use \YetiForcePDF\Math;

/**
 * Class BlockBox
 */
class BlockBox extends ElementBox implements BoxInterface, AppendChildInterface, BuildTreeInterface
{

    /**
     * @var \YetiForcePDF\Layout\LineBox
     */
    protected $currentLineBox;
    /**
     * @var LineBox[]
     */
    protected $sourceLines = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->dimensions = (new BoxDimensions())
            ->setDocument($this->document)
            ->setBox($this)
            ->init();
        $this->coordinates = (new Coordinates())
            ->setDocument($this->document)
            ->setBox($this)
            ->init();
        $this->offset = (new Offset())
            ->setDocument($this->document)
            ->setBox($this)
            ->init();
        return $this;
    }

    /**
     * Get element
     * @return Element
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Set element
     * @param Element $element
     * @return $this
     */
    public function setElement(Element $element)
    {
        $this->element = $element;
        $element->setBox($this);
        return $this;
    }

    /**
     * Get new line box
     * @param Box $before [optional]
     * @return \YetiForcePDF\Layout\LineBox
     */
    public function getNewLineBox($before = null)
    {
        $this->currentLineBox = (new LineBox())
            ->setDocument($this->document)
            ->setParent($this)
            ->init();
        if ($before !== null) {
            $this->insertBefore($this->currentLineBox, $before);
        } else {
            $this->appendChild($this->currentLineBox);
        }
        $style = (new Style())
            ->setDocument($this->document)
            ->setBox($this->currentLineBox);
        $this->currentLineBox->setStyle($style);
        $this->currentLineBox->getStyle()->init();
        return $this->currentLineBox;
    }

    /**
     * Close line box
     * @param \YetiForcePDF\Layout\LineBox|null $lineBox
     * @param bool $createNew
     * @return \YetiForcePDF\Layout\LineBox
     */
    public function closeLine()
    {
        $this->saveSourceLine($this->currentLineBox);
        $this->currentLineBox = null;
        return $this->currentLineBox;
    }

    /**
     * Get current linebox
     * @return \YetiForcePDF\Layout\LineBox
     */
    public function getCurrentLineBox()
    {
        return $this->currentLineBox;
    }

    /**
     * {@inheritdoc}
     */
    public function appendBlockBox($childDomElement, $element, $style, $parentBlock)
    {
        if ($this->getCurrentLineBox()) {
            $this->closeLine();
        }
        $box = (new BlockBox())
            ->setDocument($this->document)
            ->setParent($this)
            ->setElement($element)
            ->setStyle($style)
            ->init();
        $this->appendChild($box);
        $box->getStyle()->init();
        $box->buildTree($box);
        return $box;
    }

    /**
     * {@inheritdoc}
     */
    public function appendTableWrapperBlockBox($childDomElement, $element, $style, $parentBlock)
    {
        if ($this->getCurrentLineBox()) {
            $this->closeLine();
        }
        $box = (new TableWrapperBlockBox())
            ->setDocument($this->document)
            ->setParent($this)
            ->setElement($element)
            ->setStyle($style)
            ->init();
        $this->appendChild($box);
        $box->getStyle()->init()->setRule('display', 'block');
        // we wan't to build tree from here - we will build it from TableBox
        return $box;
    }

    /**
     * {@inheritdoc}
     */
    public function appendInlineBlockBox($childDomElement, $element, $style, $parentBlock)
    {
        if ($this->getCurrentLineBox()) {
            $currentLineBox = $this->getCurrentLineBox();
        } else {
            $currentLineBox = $this->getNewLineBox();
        }
        return $currentLineBox->appendInlineBlock($childDomElement, $element, $style, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function appendInlineBox($childDomElement, $element, $style, $parentBlock)
    {
        if ($this->getCurrentLineBox()) {
            $currentLineBox = $this->getCurrentLineBox();
        } else {
            $currentLineBox = $this->getNewLineBox();
        }
        return $currentLineBox->appendInline($childDomElement, $element, $style, $this);
    }

    /**
     * Measure width of this block
     * @return $this
     */
    public function measureWidth()
    {
        $dimensions = $this->getDimensions();
        $parent = $this->getParent();
        if ($parent) {
            if ($parent->getDimensions()->getWidth() !== null) {
                $dimensions->setWidth(Math::sub($parent->getDimensions()->getInnerWidth(), $this->getStyle()->getHorizontalMarginsWidth()));
                $this->applyStyleWidth();
                foreach ($this->getChildren() as $child) {
                    $child->measureWidth();
                }
                $this->divideLines();
                return $this;
            }
            // if parent doesn't have a width specified
            foreach ($this->getChildren() as $child) {
                $child->measureWidth();
            }
            $this->divideLines();
            $maxWidth = '0';
            foreach ($this->getChildren() as $child) {
                $maxWidth = Math::max($maxWidth, $child->getDimensions()->getOuterWidth());
            }
            $style = $this->getStyle();
            $maxWidth = Math::add($maxWidth, $style->getHorizontalBordersWidth(), $style->getHorizontalPaddingsWidth());
            $maxWidth = Math::sub($maxWidth, $style->getHorizontalMarginsWidth());
            $dimensions->setWidth($maxWidth);
            $this->applyStyleWidth();
            return $this;
        }
        $dimensions->setWidth($this->document->getCurrentPage()->getDimensions()->getWidth());
        $this->applyStyleWidth();
        foreach ($this->getChildren() as $child) {
            $child->measureWidth();
        }
        $this->divideLines();
        return $this;
    }

    /**
     * Group sibling line boxes into two dimensional array
     * @return array
     */
    public function groupLines()
    {
        $lineGroups = [];
        $currentGroup = 0;
        foreach ($this->getChildren() as $child) {
            if ($child instanceof LineBox) {
                $lineGroups[$currentGroup][] = $child;
            } else {
                if (isset($lineGroups[$currentGroup])) {
                    $currentGroup++;
                }
            }
        }
        return $lineGroups;
    }

    /**
     * Merge line groups into one line (reverse divide - reorganize)
     * @return LineBox[]
     */
    public function mergeLineGroups(array $lineGroups)
    {
        $lines = [];
        foreach ($lineGroups as $index => $lines) {
            if (isset($lines[0])) {
                $currentLine = $this->getNewLineBox($lines[0]);
                foreach ($lines as $line) {
                    foreach ($line->getChildren() as $child) {
                        $child->setForMeasurement(true);
                        $currentLine->appendChild($line->removeChild($child));
                    }
                    $this->removeChild($line);
                }
                $lines[] = $currentLine;
            }
        }
        return $lines;
    }

    /**
     * Hide empty lines
     * @return $this
     */
    protected function hideEmptyLines()
    {
        foreach ($this->getChildren() as $child) {
            if ($child instanceof LineBox) {
                if ($child->isEmpty() && !$child->getStyle()->haveSpacing()) {
                    $child->setRenderable(false);
                } else {
                    $child->setRenderable();
                }
            }
        }
        return $this;
    }

    /**
     * Save source lines before any dividing process (to get maximal width of the block for tables later)
     * @return $this
     */
    protected function saveSourceLine($line)
    {
        $this->sourceLines[] = clone $line;
        return $this;
    }

    /**
     * Get initial lines before any process was applied
     * @return LineBox[]
     */
    public function getSourceLines()
    {
        return $this->sourceLines;
    }

    /**
     * Divide lines
     * @return $this
     */
    public function divideLines()
    {
        $this->mergeLineGroups($this->groupLines());
        foreach ($this->getChildren() as $child) {
            if ($child instanceof LineBox) {
                $lines = $child->divide();
                foreach ($lines as $line) {
                    $this->insertBefore($line, $child);
                    $line->getStyle()->init();
                    if (!$this instanceof InlineBlockBox) {
                        $line->removeWhiteSpaces();
                    }
                    $line->measureWidth();
                }
                $this->removeChild($child);
            }
        }
        $this->hideEmptyLines();
        return $this;
    }


    /**
     * Measure height
     * @return $this
     */
    public function measureHeight()
    {
        $this->applyStyleHeight();
        foreach ($this->getChildren() as $child) {
            $child->measureHeight();
        }
        $height = '0';
        foreach ($this->getChildren() as $child) {
            $height = Math::add($height, $child->getDimensions()->getOuterHeight());
        }
        $style = $this->getStyle();
        $height = Math::add($height, $style->getVerticalPaddingsWidth(), $style->getVerticalBordersWidth());
        $this->getDimensions()->setHeight($height);
        $this->applyStyleHeight();
        return $this;
    }

    /**
     * Offset elements
     * @return $this
     */
    public function measureOffset()
    {
        $top = $this->document->getCurrentPage()->getCoordinates()->getY();
        $left = $this->document->getCurrentPage()->getCoordinates()->getX();
        $marginTop = $this->getStyle()->getRules('margin-top');
        if ($parent = $this->getParent()) {
            $parentStyle = $parent->getStyle();
            $top = $parentStyle->getOffsetTop();
            $left = $parentStyle->getOffsetLeft();
            if ($previous = $this->getPrevious()) {
                $top = Math::add($previous->getOffset()->getTop(), $previous->getDimensions()->getHeight());
                if ($previous->getStyle()->getRules('display') === 'block') {
                    $marginTop = Math::comp($marginTop, $previous->getStyle()->getRules('margin-bottom')) > 0 ? $marginTop : $previous->getStyle()->getRules('margin-bottom');
                } elseif (!$previous instanceof LineBox) {
                    $marginTop = Math::add($marginTop, $previous->getStyle()->getRules('margin-bottom'));
                }
            }
        }
        $top = Math::add($top, $marginTop);
        $left = Math::add($left, $this->getStyle()->getRules('margin-left'));
        $this->getOffset()->setTop($top);
        $this->getOffset()->setLeft($left);
        foreach ($this->getChildren() as $child) {
            $child->measureOffset();
        }
        return $this;
    }

    /**
     * Position
     * @return $this
     */
    public function measurePosition()
    {
        $x = $this->document->getCurrentPage()->getCoordinates()->getX();
        $y = $this->document->getCurrentPage()->getCoordinates()->getY();
        if ($parent = $this->getParent()) {
            $x = Math::add($parent->getCoordinates()->getX(), $this->getOffset()->getLeft());
            $y = Math::add($parent->getCoordinates()->getY(), $this->getOffset()->getTop());
        }
        $this->getCoordinates()->setX($x);
        $this->getCoordinates()->setY($y);
        foreach ($this->getChildren() as $child) {
            $child->measurePosition();
        }
        return $this;
    }

    /**
     * Layout elements
     * @return $this
     */
    public function layout()
    {
        $this->measureWidth();
        $this->measureHeight();
        $this->measureOffset();
        $this->alignText();
        $this->measurePosition();
        return $this;
    }

    /**
     * Add background color instructions
     * @param array $element
     * @param $pdfX
     * @param $pdfY
     * @param $width
     * @param $height
     * @return array
     */
    public function addBackgroundColorInstructions(array $element, $pdfX, $pdfY, $width, $height)
    {
        if ($this->getStyle()->getRules('display') === 'none') {
            return $element;
        }
        $rules = $this->style->getRules();
        if ($rules['background-color'] !== 'transparent') {
            $bgColor = [
                'q',
                "1 0 0 1 $pdfX $pdfY cm",
                "{$rules['background-color'][0]} {$rules['background-color'][1]} {$rules['background-color'][2]} rg",
                "0 0 $width $height re",
                'f',
                'Q'
            ];
            $element = array_merge($element, $bgColor);
        }
        return $element;
    }

    /**
     * Add background color instructions
     * @param array $element
     * @param $pdfX
     * @param $pdfY
     * @param $width
     * @param $height
     * @return array
     */
    public function addBackgroundImageInstructions(array $element, $pdfX, $pdfY, $width, $height)
    {
        if ($this->getStyle()->getBackgroundImageStream() === null) {
            return $element;
        }
        $rules = $this->style->getRules();
        if ($rules['background-image'] !== 'transparent') {
            $bgColor = [
                'q',
                "1 0 0 1 $pdfX $pdfY cm",
                "$width 0 0 $height 0 0 cm",
                '/' . $this->getStyle()->getBackgroundImageStream()->getImageName() . ' Do',
                'Q',
            ];
            $element = array_merge($element, $bgColor);
        }
        return $element;
    }

    /**
     * Divide content into pages
     * @return $this
     */
    public function divideIntoPages()
    {
        $pageCuts = [];
        $allChildren = [];
        $this->getAllChildren($allChildren);
        foreach ($allChildren as $child) {
            if ($child->getStyle()->getRules('page-break-after') === 'always') {
                $pageCuts[] = $child;
            }
        }
        foreach ($pageCuts as $pageCut) {
            $cutAt = Math::add($pageCut->getCoordinates()->getY(), $pageCut->getDimensions()->getHeight());
            $cutAt = Math::add($cutAt, $pageCut->getStyle()->getRules('margin-bottom'));
            $this->document->getCurrentPage()->cutAt($cutAt);
        }
        return $this;
    }

    /**
     * Get element PDF instructions to use in content stream
     * @return string
     */
    public function getInstructions(): string
    {
        if ($this->getStyle()->getRules('display') === 'none') {
            return '';
        }
        $coordinates = $this->getCoordinates();
        $pdfX = $coordinates->getPdfX();
        $pdfY = $coordinates->getPdfY();
        $dimensions = $this->getDimensions();
        $width = $dimensions->getWidth();
        $height = $dimensions->getHeight();
        $element = [];
        $element = $this->addBackgroundColorInstructions($element, $pdfX, $pdfY, $width, $height);
        $element = $this->addBackgroundImageInstructions($element, $pdfX, $pdfY, $width, $height);
        $element = $this->addBorderInstructions($element, $pdfX, $pdfY, $width, $height);
        return implode("\n", $element);
    }
}
