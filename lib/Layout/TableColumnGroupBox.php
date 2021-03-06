<?php
declare(strict_types=1);
/**
 * TableColumnGroupBox class
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

/**
 * Class TableColumnGroupBox
 */
class TableColumnGroupBox extends InlineBlockBox
{
    /**
     * {@inheritdoc}
     */
    public function getInstructions(): string
    {
        return ''; // not renderable
    }
}
