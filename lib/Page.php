<?php
declare(strict_types=1);
/**
 * Page class
 *
 * @package   YetiPDF\Document
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiPDF;

/**
 * Class Page
 */
class Page extends \YetiPDF\Objects\Basic\DictionaryObject
{
	/**
	 * {@inheritdoc}
	 */
	protected $dictionaryType = 'Page';
	/**
	 * Page resources
	 * @var \YetiPDF\Objects\Resource[]
	 */
	protected $resources = [];
	/**
	 * Page content streams
	 * @var \YetiPDF\Objects\Basic\StreamObject
	 */
	protected $contentStream;
	/**
	 * Portrait page orientation
	 */
	const ORIENTATION_PORTRAIT = 'P';
	/**
	 * Landscape page orientation
	 */
	const ORIENTATION_LANDSCAPE = 'L';
	/**
	 * Current page format
	 * @var string $format
	 */
	protected $format = 'A4';
	/**
	 * Current page orientation
	 * @var string $orientation
	 */
	protected $orientation = 'P';

	/**
	 * Set page format
	 * @param string $format
	 * @return \YetiPDF\Page
	 */
	public function setFormat(string $format): \YetiPDF\Page
	{
		$this->format = $format;
		return $this;
	}

	/**
	 * Set page orientation
	 * @param string $orientation
	 * @return \YetiPDF\Page
	 */
	public function setOrientation(string $orientation): \YetiPDF\Page
	{
		$this->orientation = $orientation;
		return $this;
	}

	/**
	 * Add page resource
	 * @param \YetiPDF\Objects\PdfObject $resource
	 * @return \YetiPDF\Page
	 */
	public function addResource(\YetiPDF\Objects\PdfObject $resource): \YetiPDF\Page
	{
		$this->resources[] = $resource;
		return $this;
	}

	/**
	 * Add content stream
	 * @param \YetiPDF\Objects\Basic\StreamObject $stream
	 * @return \YetiPDF\Page
	 */
	public function setContentStream(\YetiPDF\Objects\Basic\StreamObject $stream): \YetiPDF\Page
	{
		$this->contentStream = $stream;
		return $this;
	}

	/**
	 * Render page resources
	 * @return string
	 */
	public function renderResources(): string
	{
		$rendered = '/Resources <<';
		foreach ($this->resources as $resource) {
			$rendered .= "\n/" . $resource->getResourceType() . ' ' . $resource->getReference() . "\n";
		}
		return $rendered . ">>";
	}


	/**
	 * {@inheritdoc}
	 */
	public function render(): string
	{
		return implode("\n", [
			$this->getRawId() . " obj",
			"<<",
			"/Type /Page",
			"/Parent " . $this->parent->getReference(),
			$this->renderResources(),
			"/Contents " . $this->contentStream->getReference(),
			">>",
			"endobj"
		]);
	}

}