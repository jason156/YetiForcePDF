<?php
declare(strict_types=1);
/**
 * BorderLeftWidth class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class BorderLeftWidth
 */
class BorderLeftWidth extends Normalizer
{
    public function normalize($ruleValue): array
    {
        if ($this->normalized === null) {
            return $this->normalized = ['border-left-width' => $this->getNumberValues($ruleValue)[0]];
        }
        return $this->normalized;
    }
}
