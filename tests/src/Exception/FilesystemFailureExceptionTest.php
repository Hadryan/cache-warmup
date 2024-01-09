<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/cache-warmup".
 *
 * Copyright (C) 2020-2024 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\CacheWarmup\Tests\Exception;

use EliasHaeussler\CacheWarmup as Src;
use PHPUnit\Framework;

/**
 * FilesystemFailureExceptionTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Exception\FilesystemFailureException::class)]
final class FilesystemFailureExceptionTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function forUnresolvableWorkingDirectoryReturnsExceptionForUnresolvableWorkingDirectory(): void
    {
        $actual = Src\Exception\FilesystemFailureException::forUnresolvableWorkingDirectory();

        self::assertSame(1691648735, $actual->getCode());
        self::assertSame('Unable to resolve the current working directory.', $actual->getMessage());
    }

    #[Framework\Attributes\Test]
    public function forUnexpectedFileStreamResultReturnsExceptionForUnexpectedFileStreamResult(): void
    {
        $actual = Src\Exception\FilesystemFailureException::forUnexpectedFileStreamResult('foo');

        self::assertSame(1691649034, $actual->getCode());
        self::assertSame('Unable to open a file stream for "foo".', $actual->getMessage());
    }

    #[Framework\Attributes\Test]
    public function forMissingFileReturnsExceptionForMissingFile(): void
    {
        $actual = Src\Exception\FilesystemFailureException::forMissingFile('foo');

        self::assertSame(1698427082, $actual->getCode());
        self::assertSame('The file "foo" does not exist or is not readable.', $actual->getMessage());
    }
}
