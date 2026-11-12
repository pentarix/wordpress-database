<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\PostQuery;

final class PostQueryTest extends PHPUnit\Framework\TestCase
{
    public function testExecutionRequiresWordPressToBeLoaded(): void
    {
        if (class_exists(WP_Query::class)) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PostQuery requires WordPress to be loaded.');

        PostQuery::make()->execute();
    }
}
