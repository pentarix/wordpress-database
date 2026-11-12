<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\TermQuery;
use Pentarix\WordPressDatabase\WordPress\TermQueryArg;

final class TermQueryIntegrationTest extends WP_UnitTestCase
{
    public function testExecuteReturnsTheNativeWordPressQueryObject(): void
    {
        self::factory()->term->create([
            'taxonomy' => 'post_tag',
            'name' => 'First tag',
        ]);

        $query = TermQuery::make(
            TermQueryArg::make([
                'taxonomy' => 'post_tag',
                'hide_empty' => false,
            ])
        )->execute();

        self::assertInstanceOf(WP_Term_Query::class, $query);
        self::assertNotEmpty($query->terms);
    }

    public function testGetFirstAndCountPreserveNativeTermResults(): void
    {
        $termIds = [
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Alpha']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Bravo']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Charlie']),
        ];

        $query = TermQuery::make(
            TermQueryArg::make([
                'taxonomy' => 'post_tag',
                'hide_empty' => false,
                'orderby' => 'term_id',
                'order' => 'ASC',
                'number' => 2,
                'fields' => 'ids',
            ])
        );

        self::assertSame(array_slice($termIds, 0, 2), $query->get());
        self::assertSame($termIds[0], $query->first());
        self::assertSame(3, $query->count());
    }

    public function testGetPreservesNativeCountFieldResults(): void
    {
        self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Count one']);
        self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Count two']);

        $query = TermQuery::make([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'fields' => 'count',
        ]);

        self::assertSame(2, (int) $query->get());
    }

    public function testPaginateUsesNativeNumberAndOffsetArguments(): void
    {
        $termIds = [
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Page one']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Page two']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Page three']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Page four']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Page five']),
        ];

        $query = TermQuery::make(
            TermQueryArg::make([
                'taxonomy' => 'post_tag',
                'hide_empty' => false,
                'hierarchical' => false,
                'orderby' => 'term_id',
                'order' => 'ASC',
                'number' => 20,
            ])
        )->paginate(2, 2);

        self::assertInstanceOf(WP_Term_Query::class, $query);
        self::assertSame(2, (int) $query->query_vars['number']);
        self::assertSame(2, (int) $query->query_vars['offset']);
        self::assertSame(array_slice($termIds, 2, 2), $query->terms);
    }

    public function testPaginateAddsToAnExistingOffset(): void
    {
        $termIds = [
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Offset one']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Offset two']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Offset three']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Offset four']),
            self::factory()->term->create(['taxonomy' => 'post_tag', 'name' => 'Offset five']),
        ];

        $query = TermQuery::make([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'hierarchical' => false,
            'orderby' => 'term_id',
            'order' => 'ASC',
            'offset' => 1,
        ])->paginate(2, 2);

        self::assertSame(3, (int) $query->query_vars['offset']);
        self::assertSame(array_slice($termIds, 3, 2), $query->terms);
    }

    public function testEmptyArgumentsExecuteUsingWordPressDefaults(): void
    {
        $query = TermQuery::make()->execute();

        self::assertInstanceOf(WP_Term_Query::class, $query);
        self::assertIsArray($query->terms);
    }
}
