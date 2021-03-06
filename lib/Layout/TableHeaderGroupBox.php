<?php
declare(strict_types=1);
/**
 * TableHeaderGroupBox class
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
 * Class TableHeaderGroupBox
 */
class TableHeaderGroupBox extends BlockBox
{
    /**
     * {@inheritdoc}
     */
    public function getInstructions(): string
    {
        return ''; // not renderable
    }
}
