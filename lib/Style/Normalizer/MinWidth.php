<?php
declare(strict_types=1);
/**
 * MinWidth class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class MinWidth
 */
class MinWidth extends Normalizer
{
    public function normalize($ruleValue): array
    {
        if ($this->normalized === null) {
            return $this->normalized = ['min-width' => $this->getNumberValues($ruleValue)[0]];
        }
        return $this->normalized;
    }
}
