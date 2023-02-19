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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\CacheWarmup\Formatter;

use EliasHaeussler\CacheWarmup\Result;
use Symfony\Component\Console;

use function array_map;
use function is_array;
use function json_encode;

/**
 * JsonFormatter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @phpstan-type JsonArray array{
 *     parserResult?: array{
 *         success?: array{
 *             sitemaps: list<string>,
 *             urls: list<string>,
 *         },
 *         failure?: array{
 *             sitemaps: list<string>,
 *         },
 *     },
 *     cacheWarmupResult?: array{
 *         success?: list<string>,
 *         failure?: list<string>,
 *     },
 *     messages?: array<value-of<MessageSeverity>, list<string>>
 * }
 */
final class JsonFormatter implements Formatter
{
    /**
     * @phpstan-var JsonArray
     */
    private array $json = [];

    public function __construct(
        private readonly Console\Style\SymfonyStyle $io,
    ) {
    }

    public function formatParserResult(Result\ParserResult $successful, Result\ParserResult $failed): void
    {
        if ($this->io->isVeryVerbose()) {
            $this->json['parserResult'] = [
                'success' => [
                    'sitemaps' => array_map('strval', $successful->getSitemaps()),
                    'urls' => array_map('strval', $successful->getUrls()),
                ],
            ];
        }

        if ([] !== ($failedSitemaps = $failed->getSitemaps())) {
            if (!is_array($this->json['parserResult'] ?? null)) {
                $this->json['parserResult'] = [];
            }

            $this->json['parserResult']['failure'] = [
                'sitemaps' => array_map('strval', $failedSitemaps),
            ];
        }
    }

    public function formatCacheWarmupResult(Result\CacheWarmupResult $result): void
    {
        $this->json['cacheWarmupResult'] = [];

        if ([] !== ($successfulUrls = $result->getSuccessful())) {
            $this->json['cacheWarmupResult']['success'] = array_map('strval', $successfulUrls);
        }

        if ([] !== ($failedUrls = $result->getFailed())) {
            $this->json['cacheWarmupResult']['failure'] = array_map('strval', $failedUrls);
        }
    }

    public function logMessage(string $message, MessageSeverity $severity = MessageSeverity::Info): void
    {
        if (!is_array($this->json['messages'] ?? null)) {
            $this->json['messages'] = [];
        }

        if (!is_array($this->json['messages'][$severity->value] ?? null)) {
            $this->json['messages'][$severity->value] = [];
        }

        $this->json['messages'][$severity->value][] = $message;
    }

    public function isVerbose(): bool
    {
        return false;
    }

    /**
     * @phpstan-return JsonArray
     */
    public function getJson(): array
    {
        return $this->json;
    }

    public static function getType(): string
    {
        return 'json';
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        // Early return if no JSON data was added
        if ([] === $this->json) {
            return;
        }

        // Early return if output is quiet
        if ($this->io->isQuiet()) {
            return;
        }

        $flags = JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        // Pretty-print JSON on verbose output
        if ($this->io->isVerbose()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $this->io->writeln(json_encode($this->json, $flags));
    }
}
