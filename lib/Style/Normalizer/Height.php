<?php
declare(strict_types=1);
/**
 * Height class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class Height
 */
class Height extends Normalizer
{
    public function normalize($ruleValue): array
    {
        if ($this->normalized === null && $ruleValue !== 'auto') {
            return ['height' => $this->getNumberValues($ruleValue)[0]];
        }
        if ($ruleValue === 'auto') {
            return $this->normalized = ['height' => 'auto'];
        }
        return $this->normalized;
    }
}
