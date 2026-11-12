<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\CommentQuery;

final class CommentQueryTest extends PHPUnit\Framework\TestCase
{
    public function testExecutionRequiresWordPressToBeLoaded(): void
    {
        if (class_exists(WP_Comment_Query::class)) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('CommentQuery requires WordPress to be loaded.');

        CommentQuery::make()->execute();
    }

    public function testPaginationRejectsNonPositivePageSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The per-page value must be greater than zero.');

        CommentQuery::make()->paginate(0);
    }

    public function testPaginationRejectsNonPositivePageNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The page number must be greater than zero.');

        CommentQuery::make()->paginate(10, 0);
    }
}
