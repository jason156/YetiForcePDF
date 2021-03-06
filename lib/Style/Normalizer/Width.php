<?php
declare(strict_types=1);
/**
 * Width class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class Width
 */
class Width extends Normalizer
{
    public function normalize($ruleValue): array
    {
        if ($this->normalized === null && $ruleValue !== 'auto') {
            return $this->normalized = ['width' => $this->getNumberValues($ruleValue)[0]];
        }
        if ($ruleValue === 'auto') {
            return $this->normalized = ['width' => 'auto'];
        }
        return $this->normalized;
    }
}
