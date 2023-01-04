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

namespace EliasHaeussler\CacheWarmup\Tests\Unit\Command;

use EliasHaeussler\CacheWarmup\Command;
use EliasHaeussler\CacheWarmup\Exception;
use EliasHaeussler\CacheWarmup\Sitemap;
use EliasHaeussler\CacheWarmup\Tests;
use Generator;
use PHPUnit\Framework;
use Symfony\Component\Console;

/**
 * CacheWarmupCommandTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class CacheWarmupCommandTest extends Framework\TestCase
{
    use Tests\Unit\ClientMockTrait;

    private Console\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->client = $this->createClient();

        $command = new Command\CacheWarmupCommand($this->client);
        $application = new Console\Application();
        $application->add($command);

        $this->commandTester = new Console\Tester\CommandTester($command);
    }

    /**
     * @test
     */
    public function interactThrowsExceptionIfNeitherArgumentNorInteractiveInputProvidesSitemaps(): void
    {
        $this->expectException(Console\Exception\RuntimeException::class);
        $this->expectExceptionCode(1604258903);

        $this->commandTester->setInputs([null]);
        $this->commandTester->execute([]);
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfNoSitemapsAreGivenAndInteractiveModeIsDisabled(): void
    {
        $this->expectException(Console\Exception\RuntimeException::class);
        $this->expectExceptionCode(1604261236);

        $this->commandTester->execute([], ['interactive' => false]);
    }

    /**
     * @test
     */
    public function executeUsesSitemapUrlsFromInteractiveUserInputIfSitemapsArgumentIsNotGiven(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->setInputs([
            'https://www.example.com/sitemap.xml',
            null,
        ]);

        $this->commandTester->execute([], ['verbosity' => Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('* https://www.example.com/sitemap.xml', $output);
        self::assertStringContainsString('* https://www.example.com/', $output);
        self::assertStringContainsString('* https://www.example.com/foo', $output);
    }

    /**
     * @test
     */
    public function executeCrawlsUrlsFromGivenSitemaps(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute(
            [
                'sitemaps' => [
                    'https://www.example.com/sitemap.xml',
                ],
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE,
            ]
        );

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('* https://www.example.com/sitemap.xml', $output);
        self::assertStringContainsString('* https://www.example.com/', $output);
        self::assertStringContainsString('* https://www.example.com/foo', $output);
    }

    /**
     * @test
     */
    public function executeLimitsCrawlingIfLimitOptionIsSet(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute(
            [
                'sitemaps' => [
                    'https://www.example.com/sitemap.xml',
                ],
                '--limit' => 1,
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE,
            ]
        );

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('* https://www.example.com/sitemap.xml', $output);
        self::assertStringContainsString('* https://www.example.com/', $output);
        self::assertStringNotContainsString('* https://www.example.com/foo', $output);
    }

    /**
     * @test
     */
    public function executeCrawlsAdditionalUrls(): void
    {
        $this->commandTester->setInputs([null]);

        $this->commandTester->execute(
            [
                '--urls' => [
                    'https://www.example.com/',
                    'https://www.example.com/foo',
                ],
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE,
            ]
        );

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('* https://www.example.com/', $output);
        self::assertStringContainsString('* https://www.example.com/foo', $output);
    }

    /**
     * @test
     */
    public function executeHidesVerboseOutputIfVerbosityIsNormal(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Parsing sitemaps... Done', $output);
        self::assertStringContainsString('Crawling URLs... Done', $output);
        self::assertStringNotContainsString('* https://www.example.com/sitemap.xml', $output);
        self::assertStringNotContainsString('* https://www.example.com/', $output);
        self::assertStringNotContainsString('* https://www.example.com/foo', $output);
    }

    /**
     * @test
     */
    public function executeHidesVerboseOutputIfNoProgressOptionIsSet(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute(
            [
                'sitemaps' => [
                    'https://www.example.com/sitemap.xml',
                ],
                '--no-progress' => true,
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERBOSE,
            ]
        );

        $output = $this->commandTester->getDisplay();

        self::assertStringNotContainsString('100%', $output);
    }

    /**
     * @test
     */
    public function executeShowsVerboseOutputIfProgressOptionIsSet(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--progress' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('100%', $output);
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfGivenCrawlerClassDoesNotExist(): void
    {
        $this->expectException(Console\Exception\RuntimeException::class);
        $this->expectExceptionCode(1604261816);

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--crawler' => 'foo',
        ]);
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfGivenCrawlerClassIsNotValid(): void
    {
        $this->expectException(Console\Exception\RuntimeException::class);
        $this->expectExceptionCode(1604261885);

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--crawler' => self::class,
        ]);
    }

    /**
     * @test
     */
    public function executeUsesCustomCrawler(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--crawler' => Tests\Unit\Crawler\DummyCrawler::class,
        ]);

        $expected = [
            new Sitemap\Url('https://www.example.com/'),
            new Sitemap\Url('https://www.example.com/foo'),
        ];

        self::assertEquals($expected, Tests\Unit\Crawler\DummyCrawler::$crawledUrls);
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfCrawlerOptionsAreInvalid(): void
    {
        $this->expectException(Console\Exception\RuntimeException::class);
        $this->expectExceptionCode(1659120649);
        $this->expectExceptionMessage('The given crawler options are invalid. Please pass crawler options as JSON-encoded array.');

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--crawler-options' => 'foo',
        ]);
    }

    /**
     * @test
     *
     * @dataProvider executeUsesCrawlerOptionsDataProvider
     *
     * @param array{concurrency: int}|string $crawlerOptions
     */
    public function executeUsesCrawlerOptions(array|string $crawlerOptions): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');
        $this->commandTester->execute(
            [
                'sitemaps' => [
                    'https://www.example.com/sitemap.xml',
                ],
                '--crawler-options' => $crawlerOptions,
            ],
            [
                'verbosity' => Console\Output\OutputInterface::VERBOSITY_VERBOSE,
            ]
        );

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Using custom crawler options:', $output);
        self::assertStringContainsString('"concurrency": 3', $output);
    }

    /**
     * @test
     */
    public function executeShowsWarningIfCrawlerOptionsArePassedToNonConfigurableCrawler(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');
        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--crawler' => Tests\Unit\Crawler\DummyCrawler::class,
            '--crawler-options' => ['foo' => 'bar'],
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('You passed crawler options for a non-configurable crawler.', $output);
    }

    /**
     * @test
     */
    public function executeAppliesOutputToVerboseCrawler(): void
    {
        $this->mockSitemapRequest('valid_sitemap_3');

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--crawler' => Tests\Unit\Crawler\DummyVerboseCrawler::class,
        ]);

        self::assertSame($this->commandTester->getOutput(), Tests\Unit\Crawler\DummyVerboseCrawler::$output);
    }

    /**
     * @test
     *
     * @dataProvider executeFailsIfSitemapCannotBeCrawledDataProvider
     */
    public function executeFailsIfSitemapCannotBeCrawled(bool $allowFailures, int $expected): void
    {
        Tests\Unit\Crawler\DummyCrawler::$simulateFailure = true;

        $this->mockSitemapRequest('valid_sitemap_3');

        $exitCode = $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--allow-failures' => $allowFailures,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame($expected, $exitCode);
        self::assertStringContainsString('Failed to warm up caches for 1 URL.', $output);
    }

    /**
     * @test
     */
    public function executePrintsSitemapsThatCouldNotBeParsed(): void
    {
        $this->mockSitemapRequest('invalid_sitemap_1');

        $exitCode = $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
            '--allow-failures' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('The following sitemaps could not be parsed:', $output);
    }

    /**
     * @test
     */
    public function executeFailsIfSitemapCannotBeParsed(): void
    {
        $this->mockSitemapRequest('invalid_sitemap_1');

        $this->expectException(Exception\InvalidSitemapException::class);
        $this->expectExceptionCode(1660668799);
        $this->expectExceptionMessage('The sitemap "https://www.example.com/sitemap.xml" is invalid and cannot be parsed.');

        $this->commandTester->execute([
            'sitemaps' => [
                'https://www.example.com/sitemap.xml',
            ],
        ]);
    }

    /**
     * @return Generator<string, array{array{concurrency: int}|string}>
     */
    public function executeUsesCrawlerOptionsDataProvider(): Generator
    {
        yield 'array' => [['concurrency' => 3]];
        yield 'json string' => ['{"concurrency": 3}'];
    }

    /**
     * @return \Generator<string, array{bool, int}>
     */
    public function executeFailsIfSitemapCannotBeCrawledDataProvider(): Generator
    {
        yield 'with --allow-failures' => [true, 0];
        yield 'without --allow-failures' => [false, 1];
    }

    protected function tearDown(): void
    {
        Tests\Unit\Crawler\DummyCrawler::$crawledUrls = [];
        Tests\Unit\Crawler\DummyCrawler::$simulateFailure = false;
        Tests\Unit\Crawler\DummyVerboseCrawler::$output = null;
    }
}
