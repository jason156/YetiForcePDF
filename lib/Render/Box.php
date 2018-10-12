<?php
declare(strict_types=1);
/**
 * Box class
 *
 * @package   YetiForcePDF\Render
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Render;

use \YetiForcePDF\Render\Coordinates\Coordinates;
use \YetiForcePDF\Render\Coordinates\Offset;
use \YetiForcePDF\Render\Dimensions\BoxDimensions;
use \YetiForcePDF\Html\Element;
use YetiForcePDF\Style\Style;

/**
 * Class Box
 */
class Box extends \YetiForcePDF\Base
{

	/**
	 * @var Box
	 */
	protected $parent;
	/**
	 * @var Box[]
	 */
	protected $children = [];
	/**
	 * @var Box
	 */
	protected $next;
	/**
	 * @var Box
	 */
	protected $previous;
	/*
	 * @var BoxDimensions
	 */
	protected $dimensions;
	/**
	 * @var Coordinates
	 */
	protected $coordinates;
	/**
	 * @var Offset
	 */
	protected $offset;
	/**
	 * @var string
	 */
	protected $text;
	/**
	 * @var bool
	 */
	protected $textNode = false;
	/**
	 * @var bool
	 */
	protected $root = false;

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
	 * Set parent
	 * @param \YetiForcePDF\Render\Box|null $parent
	 * @return $this
	 */
	public function setParent(Box $parent = null)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Get parent
	 * @return \YetiForcePDF\Render\Box
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set next
	 * @param \YetiForcePDF\Render\Box|null $next
	 * @return $this
	 */
	public function setNext(Box $next = null)
	{
		$this->next = $next;
		return $this;
	}

	/**
	 * Get next
	 * @return \YetiForcePDF\Render\Box
	 */
	public function getNext()
	{
		return $this->next;
	}

	/**
	 * Set previous
	 * @param \YetiForcePDF\Render\Box|null $previous
	 * @return $this
	 */
	public function setPrevious(Box $previous = null)
	{
		$this->previous = $previous;
		return $this;
	}

	/**
	 * Get previous
	 * @return \YetiForcePDF\Render\Box
	 */
	public function getPrevious()
	{
		return $this->previous;
	}

	/**
	 * Set root - is this root element?
	 * @param bool $isRoot
	 * @return $this
	 */
	public function setRoot(bool $isRoot)
	{
		$this->root = $isRoot;
		return $this;
	}

	/**
	 * Set text node status
	 * @param bool $isTextNode
	 * @return $this
	 */
	public function setTextNode(bool $isTextNode = false)
	{
		$this->textNode = $isTextNode;
		return $this;
	}

	/**
	 * Is this text node? or element
	 * @return bool
	 */
	public function isTextNode(): bool
	{
		return $this->textNode;
	}

	/**
	 * Is this root element?
	 * @return bool
	 */
	public function isRoot(): bool
	{
		return $this->root;
	}

	/**
	 * Set text
	 * @param string $text
	 * @return $this
	 */
	public function setText(string $text)
	{
		$this->text = $text;
		return $this;
	}

	/**
	 * Get text
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * Create and append text box (text node) element
	 * @param string   $text
	 * @param Box|null $insertBefore
	 * @return InlineBox|null
	 */
	public function createTextBox(string $text, Box $insertBefore = null)
	{
		if (!$this instanceof LineBox && !$this->isTextNode()) {
			$style = (new Style())->setDocument($this->document)->init();
			$style->setRule('display', 'inline');
			$box = (new InlineBox())
				->setDocument($this->document)
				->setStyle($style)
				->setTextNode(true)
				->setText($text)
				->init();
			if ($insertBefore) {
				$this->insertBefore($box, $insertBefore);
			} else {
				$this->appendChild($box);
			}
			$box->getDimensions()->setUpAvailableSpace();
			return $box;
		} elseif ($this instanceof LineBox) {
			$box = $this->getParent()->createTextBox($text);
			$this->getParent()->removeChild($box);
			if ($insertBefore) {
				$this->insertBefore($box, $insertBefore);
			} else {
				$this->appendChild($box);
			}
			$box->getDimensions()->setUpAvailableSpace();
			return $box;
		} else {
			throw new \InvalidArgumentException('Cannot create child element inside text node.');
		}
	}

	/**
	 * Wrap each child element with clone of this box instance and push each element to parent box
	 */
	public function cutAndWrap()
	{
		if ($this instanceof InlineBox) {
			$parent = $this->getParent();
			$count = count($this->getChildren());
			foreach ($this->getChildren() as $child) {
				$child->cutAndWrap();
			}
			if ($count > 1) {
				foreach ($this->getChildren() as $index => $child) {
					$clone = clone $this;
					$clone->getDimensions()->setBox($clone);
					$clone->getCoordinates()->setBox($clone);
					$clone->getOffset()->setBox($clone);
					$clone->getStyle()->setBox($clone);
					if ($clone->getElement()) {
						$clone->getElement()->setBox($clone);
					}
					$clone->appendChild($child);
					$parent->insertBefore($clone, $this);
					if ($count > 1) {
						if ($index === 0) {
							$clone->getStyle()->clearFirstInline();
						} elseif ($index === $count - 1) {
							$clone->getStyle()->clearLastInline();
						} else {
							$clone->getStyle()->clearMiddleInline();
						}
					}
					$clone->measurePhaseOne();
					$clone->getDimensions()->setUpAvailableSpace();
				}
				$parent->removeChild($this);
				$parent->measurePhaseOne();
			}

		} else {
			foreach ($this->getChildren() as $childBox) {
				$childBox->cutAndWrap();
			}
		}
	}

	/**
	 * Convert text to words and wrap with InlineBox
	 */
	public function split()
	{
		$parent = $this->getParent();
		if (!$this instanceof LineBox && $this->isTextNode()) {
			$text = $this->getText();
			$words = explode(' ', $text);
			$count = count($words);
			foreach ($words as $index => $word) {
				if ($index !== $count - 1) {
					$word .= ' ';
				}
				$parent->createTextBox($word, $this);
			}
			$parent->removeChild($this);
			$parent->cutAndWrap();
		} else {
			foreach ($this->getChildren() as $box) {
				$box->split();
			}
			if ($parent) {
				$parent->cutAndWrap();
			}
		}
	}

	/**
	 * Append child box - line box can have only inline/block boxes - not line boxes!
	 * @param Box $box
	 * @return $this
	 */
	public function appendChild(Box $box)
	{
		if ($this instanceof LineBox && $box instanceof LineBox) {
			throw new \InvalidArgumentException('LineBox cannnot append another LineBox as child.');
		}
		$box->setParent($this);
		$childrenCount = count($this->children);
		if ($childrenCount > 0) {
			$previous = $this->children[$childrenCount - 1];
			$box->setPrevious($previous);
			$previous->setNext($box);
		} else {
			$box->setPrevious()->setNext();
		}
		$this->children[] = $box;
		$box->getDimensions()->setUpAvailableSpace();
		return $this;
	}

	/**
	 * Append children boxes
	 * @param Box $box
	 * @return $this
	 */
	public function appendChildren(array $boxes)
	{
		foreach ($boxes as $box) {
			$this->appendChild($box);
		}
		return $this;
	}

	/**
	 * Remove child
	 * @param $child
	 * @return Box
	 */
	public function removeChild(Box $child)
	{
		$oldChildren = $this->children; // copy children
		$this->children = [];
		foreach ($oldChildren as $currentChild) {
			if ($currentChild !== $child) {
				$this->appendChild($currentChild);
			}
		}
		if ($child->getPrevious()) {
			if ($child->getNext()) {
				$child->getPrevious()->setNext($child->getNext());
			} else {
				$child->getPrevious()->setNext();
			}
		}
		if ($child->getNext()) {
			if ($child->getPrevious()) {
				$child->getNext()->setPrevious($child->getPrevious());
			} else {
				$child->getNext()->setPrevious();
			}
		}
		$child->setParent()->setPrevious()->setNext();
		return $child;
	}

	/**
	 * Insert box before other box
	 * @param \YetiForcePDF\Render\Box $child
	 * @param \YetiForcePDF\Render\Box $before
	 * @return $this
	 */
	public function insertBefore(Box $child, Box $before)
	{
		$currentChildren = $this->children; // copy children
		$this->children = [];
		foreach ($currentChildren as $currentChild) {
			if ($currentChild === $before) {
				$this->appendChild($child);
				$this->appendChild($currentChild);
			} else {
				$this->appendChild($currentChild);
			}
		}
		return $this;
	}

	/**
	 * Get children
	 * @return Box[]
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * Get all children
	 * @param Box[] $allChildren
	 * @return Box[]
	 */
	public function getAllChildren(&$allChildren = [])
	{
		$allChildren[] = $this;
		foreach ($this->getChildren() as $child) {
			$child->getAllChildren($allChildren);
		}
		return $allChildren;
	}

	/**
	 * Do we have children?
	 * @return bool
	 */
	public function hasChildren()
	{
		return isset($this->children[0]); // faster than count
	}

	/**
	 * Get last child
	 * @return \YetiForcePDF\Render\Box|null
	 */
	public function getLastChild()
	{
		if ($count = count($this->children)) {
			return $this->children[$count - 1];
		}
	}

	/**
	 * Get dimensions
	 * @return BoxDimensions
	 */
	public function getDimensions()
	{
		return $this->dimensions;
	}

	/**
	 * Get coordinates
	 * @return Coordinates
	 */
	public function getCoordinates()
	{
		return $this->coordinates;
	}

	/**
	 * Shorthand for offset
	 * @return Offset
	 */
	public function getOffset(): Offset
	{
		return $this->offset;
	}

	/**
	 * Get parent width shorthand
	 * @return float
	 */
	protected function getParentWidth()
	{
		if ($parent = $this->getParent()) {
			return $parent->getDimensions()->getWidth();
		} else {
			// if there is no parent - root element get width from page width - margins
			return $this->document->getCurrentPage()->getDimensions()->getWidth();
		}
	}

	/**
	 * Get parent height shorthand
	 * @return float
	 */
	protected function getParentHeight()
	{
		if ($parent = $this->getParent()) {
			return $parent->getDimensions()->getHeight();
		} else {
			// if there is no parent - root element get width from page width - margins
			return $this->document->getCurrentPage()->getDimensions()->getHeight();
		}
	}

	/**
	 * Get parent inner width shorthand
	 * @return float
	 */
	protected function getParentInnerWidth()
	{
		if ($parent = $this->getParent()) {
			return $parent->getDimensions()->getInnerWidth();
		} else {
			// if there is no parent - root element get width from page width - margins
			return $this->document->getCurrentPage()->getDimensions()->getWidth();
		}
	}

	/**
	 * Get parent height shorthand
	 * @return float
	 */
	protected function getParentInnerHeight()
	{
		if ($parent = $this->getParent()) {
			return $parent->getDimensions()->getInnerHeight();
		} else {
			// if there is no parent - root element get width from page width - margins
			return $this->document->getCurrentPage()->getDimensions()->getHeight();
		}
	}

	/**
	 * Get percent height of the parent box
	 * @param string $width
	 * @return float|int|string
	 */
	public function getPercentWidth(string $width)
	{
		$percentPos = strpos($width, '%');
		if ($percentPos !== false) {
			$widthInPercent = substr($width, 0, $percentPos);
			$parentWidth = $this->getParentInnerWidth();
			if ($parentWidth) {
				return $parentWidth / 100 * (float)$widthInPercent;
			}
		} else {
			return (float)$width;
		}
	}

	/**
	 * Get percent height of the parent box
	 * @param string $height
	 * @return float|int|string
	 */
	public function getPercentHeight(string $height)
	{
		$percentPos = strpos($height, '%');
		if ($percentPos !== false) {
			$heightInPercent = substr($height, 0, $percentPos);
			$parentHeight = $this->getParentInnerHeight();
			if ($parentHeight) {
				return $parentHeight / 100 * $heightInPercent;
			}
		} else {
			return (float)$height;
		}
	}

	/**
	 * Take style specified dimensions instead of calculated one
	 * @return $this
	 */
	protected function takeStyleDimensions()
	{
		if (!$this instanceof LineBox) {
			if ($this->getStyle()->getRules('display') !== 'inline') {
				$dimensions = $this->getDimensions();
				$width = $this->getStyle()->getRules('width');
				if ($width !== 'auto') {
					$dimensions->setWidth($this->getPercentWidth((string)$width));
				}
				$height = $this->getStyle()->getRules('height');
				if ($height !== 'auto') {
					$dimensions->setHeight($this->getPercentHeight((string)$height));
				}
			}
		}
		return $this;
	}

	/**
	 * Measure all children widths and some heights if we can
	 * @return $this
	 */
	public function measurePhaseOne()
	{
		// first measure current element if we can
		$dimensions = $this->getDimensions();
		$dimensions->setUpAvailableSpace();

		if ($this instanceof LineBox) {
			$lineWidth = 0;
			foreach ($this->getChildren() as $boxChild) {
				$boxChild->measurePhaseOne();
				$lineWidth += $boxChild->getDimensions()->getOuterWidth();
			}
			$dimensions->setWidth($lineWidth);
			return $this;
		}

		if ($this->isTextNode()) {
			$dimensions->setWidth($dimensions->getTextWidth($this->getText()));
			$dimensions->setHeight($dimensions->getTextHeight($this->getText()));
		} elseif (!$this->hasChildren() && $this->getStyle()->getRules('display') !== 'block') {
			// empty inline element so we can measure it :  0 + border + padding
			$style = $this->getStyle();
			$dimensions->setWidth($style->getHorizontalBordersWidth() + $style->getHorizontalPaddingsWidth());
			$dimensions->setHeight($style->getVerticalBordersWidth() + $style->getVerticalPaddingsWidth());
		} elseif ($this->getStyle()->getRules('display') === 'block') {
			if ($parent = $this->getParent()) {
				if ($parent->getDimensions()->getWidth() !== null) {
					$dimensions->setWidth($this->getParentInnerWidth() - $this->getStyle()->getHorizontalMarginsWidth());
				}
			} else {
				$dimensions->setWidth($this->getParentInnerWidth() - $this->getStyle()->getHorizontalMarginsWidth());
			}
			// but if element has specified width other than auto take it
			$this->takeStyleDimensions();
			// we can't measure height right now because it depends on child elements heights
		}
		// now we can measure children widths and some heights
		// this box might be inline / inline block box so we can measure width of its children
		$width = 0;
		foreach ($this->getChildren() as $boxChildren) {
			$boxChildren->measurePhaseOne();
			$width += $boxChildren->getDimensions()->getOuterWidth();
		}
		if ($this->getDimensions()->getWidth() === null) {
			$dimensions->setWidth($width);
			if ($this->getStyle()->getRules('display') !== 'inline') {
				$this->takeStyleDimensions();
			}
		}
		return $this;
	}

	/**
	 * Get new line box
	 * @return \YetiForcePDF\Render\LineBox
	 */
	public function getNewLineBox()
	{
		$newLineBox = (new LineBox())->setDocument($this->document)->init();
		$newLineBox->setParent($this);
		if ($this->getDimensions()->getWidth() !== null) {
			$newLineBox->getDimensions()->setWidth($this->getDimensions()->getInnerWidth())->setUpAvailableSpace();
		} else {
			$newLineBox->getDimensions()->setUpAvailableSpace();
		}
		return $newLineBox;
	}

	/**
	 * Close line box
	 * @param \YetiForcePDF\Render\LineBox|null $lineBox
	 * @param bool                              $createNew
	 * @return \YetiForcePDF\Render\LineBox
	 */
	public function closeLine(LineBox $lineBox, bool $createNew = true)
	{
		$this->appendChild($lineBox);
		if ($createNew) {
			return $this->getNewLineBox();
		}
		return null;
	}

	/**
	 * Get closest block element
	 * @return \YetiForcePDF\Render\Box
	 */
	public function getClosestBlock()
	{
		if ($this instanceof BlockBox && $this->getStyle()->getRules('display') === 'block') {
			return $this;
		}
		if ($parent = $this->getParent()) {
			if ($parent instanceof LineBox) {
				return $parent->getParent();
			} else {
				if ($parent->getStyle()->getRules('display') === 'block') {
					return $parent;
				} else {
					return $parent->getClosestBlock();
				}
			}
		}
	}

	/**
	 * Segregate elements
	 * @return $this
	 */
	public function segregate()
	{
		$lineBox = null;
		if ($this->getElement()->getDomElement()->hasChildNodes()) {
			foreach ($this->getElement()->getDOMElement()->childNodes as $childNode) {
				// create Html Element from dom node (and later add to proper child box)
				$childElement = (new Element())
					->setDocument($this->document)
					->setDOMElement($childNode)
					->init();
				// create style and later add to proper box
				$childElementStyle = $childElement->parseStyle();
				// make render box from the dom element
				if ($childElementStyle->getRules('display') === 'block') {
					if ($lineBox !== null) { // faster than count()
						// finish line and add to current children boxes as line box
						$lineBox = $this->closeLine($lineBox, false);
					}
					$box = (new BlockBox())
						->setDocument($this->document)
						->setElement($childElement)
						->init();
					$box->setStyle($box->getElement()->parseStyle());
					$this->appendChild($box);
					$box->segregate();
					continue;
				}
				// inline boxes
				if ($childElementStyle->getRules('display') === 'inline') {
					$box = (new InlineBox())
						->setDocument($this->document)
						->setElement($childElement)
						->init();
				} else {
					$box = (new InlineBlockBox())
						->setDocument($this->document)
						->setElement($childElement)
						->init();
				}
				if ($childNode instanceof \DOMText) {
					$box->setTextNode(true)->setText($childNode->textContent);
				}
				$box->setStyle($box->getElement()->parseStyle());
				if ($lineBox === null) {
					if ($this->getStyle()->getRules('display') !== 'inline') {
						$lineBox = $this->getNewLineBox();
					} else {
						$lineBox = $this->getClosestBlock()->getNewLineBox();
					}
				}
				$lineBox->appendChild($box);
				$box->segregate();
			}
		}
		if ($lineBox !== null) {
			$this->closeLine($lineBox, false);
		}
		return $this;
	}

	/**
	 * Split lines into more lines if elements want fit
	 * @return $this
	 */
	public function splitLines()
	{
		if (!$this instanceof LineBox && !$this instanceof InlineBox) {
			// we are block box so we can operate on our children and divide lines into more lines
			foreach ($this->getChildren() as $child) {
				if ($child instanceof LineBox) {
					if (!$child->elementsFit()) {
						$newLines = $child->divide();
						foreach ($newLines as $newLine) {
							// insert new line before old one
							$this->insertBefore($newLine, $child);
						}
						// remove old line
						$this->removeChild($child);
					}
				} else {
					$child->splitLines();
				}
			}
		}
		return $this;
	}

	/**
	 * Remove white space characters (empty lines) around blockBox elements
	 * @return $this
	 */
	public function clearCharsAroundBlocks()
	{

		return $this;
	}

	/**
	 * Measure missing heights
	 * @return $this
	 */
	public function measureBoxPhaseTwo()
	{
		$height = 0;
		foreach ($this->getChildren() as $child) {
			$child->measurePhaseTwo();
			$height += $child->getDimensions()->getOuterHeight();
		}
		$dimensions = $this->getDimensions();
		$style = $this->getStyle();
		if ($dimensions->getHeight() === null) {
			if ($style->getRules('display') !== 'inline') {
				$dimensions->setHeight($height + $style->getVerticalBordersWidth() + $style->getVerticalPaddingsWidth());
			} else {
				$dimensions->setHeight($height);
			}
		}
		return $this;
	}

	/**
	 * Measure phase two - measure missing heights
	 * @return $this
	 */
	public function measurePhaseTwo()
	{
		if ($this instanceof LineBox) {
			$height = 0;
			$lineHeight = 0;
			foreach ($this->getChildren() as $child) {
				$child->measureBoxPhaseTwo();
				$height = max($height, $child->getDimensions()->getOuterHeight());
				$lineHeight = max($lineHeight, $child->getStyle()->getRules('line-height'));
			}
			if ($this->getDimensions()->getHeight() === null) {
				if ($height > $lineHeight) {
					$this->getDimensions()->setHeight($height);
				} else {
					$this->getDimensions()->setHeight($lineHeight);
				}
			}
		} else {
			$this->measureBoxPhaseTwo();
		}
		return $this;
	}

	/**
	 * Get offset from top relative to parent element
	 * @return float
	 */
	protected function getParentOffsetTop()
	{
		if ($parent = $this->getParent()) {
			$top = 0;
			if ($parent instanceof LineBox) {
				if ($this->getStyle()->getRules('display') !== 'inline') {
					$top = $this->getStyle()->getRules('margin-top');
				} else {
					$verticalAlign = $this->getStyle()->getRules('vertical-align');
					if ($verticalAlign === 'middle') {
						$top = $parent->getDimensions()->getHeight() / 2 - $this->getDimensions()->getHeight() / 2;
					} elseif ($verticalAlign === 'top') {
						$top = 0;
					} elseif ($verticalAlign === 'bottom') {
						$top = $parent->getDimensions()->getHeight() - $this->getDimensions()->getHeight();
					} elseif ($verticalAlign === 'baseline') {
						$top = $parent->getDimensions()->getHeight() / 2 - $this->getDimensions()->getHeight() / 2;
					}
				}
			} else {
				if ($previous = $this->getPrevious()) {
					$top = $previous->getOffset()->getTop();
					$top += $previous->getDimensions()->getHeight();
					if (!$previous instanceof LineBox) {
						$top += $previous->getStyle()->getRules('margin-bottom');
					}
				} else {
					$parentRules = $parent->getStyle()->getRules();
					if ($parentRules['display'] !== 'inline') {
						$top += $parentRules['border-top-width'] + $parentRules['padding-top'];
					}
				}
				if (!$this instanceof LineBox) {
					$top += $this->getStyle()->getRules('margin-top');
				}
			}
			return $top;
		}
		return $this->document->getCurrentPage()->getCoordinates()->getY();
	}

	/**
	 * Get offset from left relative to parent element
	 * @return float
	 */
	protected function getParentOffsetLeft()
	{
		if ($parent = $this->getParent()) {
			$left = 0;
			if ($parent instanceof LineBox) {
				if ($previous = $this->getPrevious()) {
					$left += $previous->getOffset()->getLeft();
					$left += $previous->getDimensions()->getWidth();
					$left += $previous->getStyle()->getRules('margin-right');
					$left += $this->getStyle()->getRules('margin-left');
				}
			} else {
				$rules = $parent->getStyle()->getRules();
				$left = $rules['padding-left'] + $rules['border-left-width'];
				if (!$this instanceof LineBox) {
					$left += $this->getStyle()->getRules('margin-left');
				}
			}
			return $left;
		}
		return $this->document->getCurrentPage()->getCoordinates()->getX();
	}

	/**
	 * Measure relative offsets
	 * @return $this
	 */
	public function measureOffsets()
	{
		$offset = $this->getOffset();
		$offset->setTop($this->getParentOffsetTop());
		$offset->setLeft($this->getParentOffsetLeft());
		foreach ($this->getChildren() as $child) {
			$child->measureOffsets();
		}
		return $this;
	}

	/**
	 * Fix offsets inside lines where text-align !== 'left'
	 * @return $this
	 */
	public function alignText()
	{
		if ($this instanceof LineBox) {
			$textAlign = $this->getParent()->getStyle()->getRules('text-align');
			if ($textAlign === 'right') {
				$offset = $this->getDimensions()->getWidth() - $this->getChildrenWidth();
				foreach ($this->getChildren() as $childBox) {
					$childBox->getOffset()->setLeft($childBox->getOffset()->getLeft() + $offset);
				}
			} elseif ($textAlign === 'center') {
				$offset = $this->getDimensions()->getWidth() / 2 - $this->getChildrenWidth() / 2;
				foreach ($this->getChildren() as $childBox) {
					$childBox->getOffset()->setLeft($childBox->getOffset()->getLeft() + $offset);
				}
			}
		} else {
			foreach ($this->getChildren() as $child) {
				$child->alignText();
			}
		}
		return $this;
	}

	/**
	 * Measure coordinates
	 * @return $this
	 */
	public function measureCoordinates()
	{
		$x = 0;
		$y = 0;
		if ($parent = $this->getParent()) {
			$x = $parent->getCoordinates()->getX();
			$y = $parent->getCoordinates()->getY();
		}
		$coordinates = $this->getCoordinates();
		$offset = $this->getOffset();
		$coordinates->setX($x + $offset->getLeft());
		$coordinates->setY($y + $offset->getTop());
		foreach ($this->getChildren() as $child) {
			$child->measureCoordinates();
		}
		return $this;
	}

	/**
	 * Reflow elements and create render tree basing on dom tree
	 * @return $this
	 */
	public function reflow()
	{
		// first phase is to convert all elements to boxes and put it inside line boxes if needed
		// later if we have widths specified we will split elements in line boxes making dividing line
		$this->segregate();
		// we have all boxes created and segregated, now we can measure widths of this elements
		$this->measurePhaseOne();
		// we have all elements widths but not for percentage width in all elements - now we can calculate percent widths
		$this->takeStyleDimensions();
		// split long text into inline elements per word
		$this->split();
		//$this->cutAndWrap();
		// all boxes that can be measured were measured whoa!
		// now we can split lineBoxes into more lines basing on its width and children widths
		$this->splitLines();
		// clear empty lines (with just white space) around blocksBoxes
		$this->clearCharsAroundBlocks();
		// we have all elements in place, it's time to measure heights for those dependent on children heights
		$this->measurePhaseTwo();
		// after heights are calculated we can calculate percent heights
		$this->takeStyleDimensions();
		// now measure relative offsets to the parent elements
		$this->measureOffsets();
		// offsets are set but easier will be to correct text-align now than in offset measure phase
		$this->alignText();
		// and absolute coordinates inside document
		$this->measureCoordinates();
		// done!
		return $this;
	}

}
