<?php

declare(strict_types=1);

use Pentarix\\WordPressDatabase\\WordPress\\PostQuery;
use Pentarix\\WordPressDatabase\\WordPress\\PostQueryArg;

final class PostQueryIntegrationTest extends WP_UnitTestCase
{
    public function testExecuteReturnsTheNativeWordPressQueryObject(): void
    {
        self::factory()->post->create();

        $query = PostQuery::make(
            PostQueryArg::make()
                ->postType('post')
                ->postStatus('publish')
        )->execute();

        self::assertInstanceOf(WP_Query::class, $query);
        self::assertCount(1, $query->posts);
    }

    public function testGetFirstAndCountUseWordPressQueryResults(): void
    {
        $postIds = [
            self::factory()->post->create(),
            self::factory()->post->create(),
            self::factory()->post->create(),
        ];

        $query = PostQuery::make(
            PostQueryArg::make()
                ->postType('post')
                ->postStatus('publish')
                ->orderBy('ID')
                ->order('ASC')
                ->postsPerPage(2)
                ->set('no_found_rows', true)
        );

        self::assertSame(
            array_slice($postIds, 0, 2),
            array_map('intval', wp_list_pluck($query->get(), 'ID'))
        );
        self::assertSame($postIds[0], (int) $query->first()->ID);
        self::assertSame(3, $query->count());
    }

    public function testFirstReturnsNullWhenNoPostMatches(): void
    {
        $query = PostQuery::make(
            PostQueryArg::make()
                ->postType('post')
                ->postStatus('publish')
                ->set('post__in', [PHP_INT_MAX])
        );

        self::assertNull($query->first());
    }
}
