<?php
declare(strict_types=1);
/**
 * StringObject class
 *
 * @package   YetiPDF\Objects\Basic
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiPDF\Objects\Basic;

/**
 * Class StringObject
 */
class StringObject extends \YetiPDF\Objects\PdfObject
{
	/**
	 * Basic object type (integer, string, boolean, dictionary etc..)
	 * @var string
	 */
	protected $basicType = 'string';
	/**
	 * Object name
	 * @var string
	 */
	protected $name = 'String';

	/**
	 * {@inheritdoc}
	 */
	public function render(): string
	{
		return '';
	}
}
