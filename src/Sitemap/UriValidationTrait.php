<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/cache-warmup".
 *
 * Copyright (C) 2022 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\CacheWarmup\Sitemap;

use EliasHaeussler\CacheWarmup\Exception;
use Psr\Http\Message;

/**
 * UriValidationTrait.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait UriValidationTrait
{
    abstract protected function getUri(): Message\UriInterface;

    /**
     * @throws Exception\InvalidUrlException
     */
    protected function validateUri(): void
    {
        $url = (string) $this->getUri();

        if ('' === trim($url)) {
            throw Exception\InvalidUrlException::forEmptyUrl();
        }

        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw Exception\InvalidUrlException::create($url);
        }
    }
}
