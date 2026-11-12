<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Metadata;
use Pentarix\WordPressDatabase\WordPress\Option;
use Pentarix\WordPressDatabase\WordPress\Transient;
use PHPUnit\Framework\TestCase;

final class DataApiTest extends TestCase
{
    public function testOptionRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('get_option')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Option::get('example');
    }

    public function testMetadataRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('get_metadata')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Metadata::get('post', 1);
    }

    public function testTransientRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('get_transient')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Transient::get('example');
    }
}
