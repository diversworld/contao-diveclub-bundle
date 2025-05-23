<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Session\Attribute;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Provides an array access adapter for a session attribute bag.
 */
class ArrayAttributeBag extends AttributeBag implements \ArrayAccess
{
    public const ATTRIBUTE_NAME = 'diversworld_contao_diveclub';
    public const ATTRIBUTE_KEY = '_diversworld_contao_diveclub_attributes';

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function &offsetGet($offset): mixed
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
