<?php

declare(strict_types=1);

use Pentarix\\WordPressDatabase\\WordPress\\TermQuery;

final class TermQueryTest extends PHPUnit\\Framework\\TestCase
{
    public function testExecutionRequiresWordPressToBeLoaded(): void
    {
        if (class_exists(WP_Term_Query::class)) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TermQuery requires WordPress to be loaded.');

        TermQuery::make()->execute();
    }

    public function testPaginationRejectsNonPositivePageSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The per-page value must be greater than zero.');

        TermQuery::make()->paginate(0);
    }

    public function testPaginationRejectsNonPositivePageNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The page number must be greater than zero.');

        TermQuery::make()->paginate(10, 0);
    }
}
