<?php
declare(strict_types=1);
/**
 * BackgroundImage class
 *
 * @package   YetiForcePDF\Style\Normalizer
 *
 * @copyright YetiForce Sp. z o.o
 * @license   MIT
 * @author    Rafal Pospiech <r.pospiech@yetiforce.com>
 */

namespace YetiForcePDF\Style\Normalizer;

/**
 * Class BackgroundImage
 */
class BackgroundImage extends Normalizer
{
    public function normalize($ruleValue): array
    {
        if ($this->normalized === null && $ruleValue !== 'transparent') {
            return $this->normalized = ['background-image' => $ruleValue];
        }
        if ($ruleValue === 'transparent') {
            return $this->normalized = ['background-image' => 'transparent'];
        }
        return $this->normalized;
    }
}
