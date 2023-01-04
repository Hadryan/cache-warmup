<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/cache-warmup".
 *
 * Copyright (C) 2023 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\CacheWarmup\Tests\Unit\Exception;

use EliasHaeussler\CacheWarmup\Exception;
use PHPUnit\Framework;

/**
 * InvalidUrlExceptionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class InvalidUrlExceptionTest extends Framework\TestCase
{
    /**
     * @test
     */
    public function createReturnsExceptionForGivenUrl(): void
    {
        $actual = Exception\InvalidUrlException::create('foo');

        self::assertInstanceOf(Exception\InvalidUrlException::class, $actual);
        self::assertSame(1604055334, $actual->getCode());
        self::assertSame('The given URL "foo" is not valid.', $actual->getMessage());
    }

    /**
     * @test
     */
    public function forEmptyUrlReturnsExceptionIfUrlIsEmpty(): void
    {
        $actual = Exception\InvalidUrlException::forEmptyUrl();

        self::assertInstanceOf(Exception\InvalidUrlException::class, $actual);
        self::assertSame(1604055264, $actual->getCode());
        self::assertSame('The given URL must not be empty.', $actual->getMessage());
    }
}
