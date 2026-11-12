<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\PostQuery;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;

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

    public function testPaginateReturnsNativeQueryAndPaginationMetadata(): void
    {
        $postIds = [
            self::factory()->post->create(),
            self::factory()->post->create(),
            self::factory()->post->create(),
            self::factory()->post->create(),
            self::factory()->post->create(),
        ];

        $postQuery = PostQuery::make(
            PostQueryArg::make()
                ->postType('post')
                ->postStatus('publish')
                ->orderBy('ID')
                ->order('ASC')
                ->postsPerPage(20)
                ->set('no_found_rows', true)
        );

        $query = $postQuery->paginate(2, 2);

        self::assertInstanceOf(WP_Query::class, $query);
        self::assertSame(2, (int) $query->query_vars['paged']);
        self::assertSame(2, (int) $query->query_vars['posts_per_page']);
        self::assertFalse($query->query_vars['no_found_rows']);
        self::assertSame(5, (int) $query->found_posts);
        self::assertSame(3, (int) $query->max_num_pages);
        self::assertSame(
            array_slice($postIds, 2, 2),
            array_map('intval', wp_list_pluck($query->posts, 'ID'))
        );

        self::assertCount(5, $postQuery->execute()->posts);
    }

    public function testPaginateIgnoresAnExistingOffset(): void
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
                ->offset(2)
        )->paginate(2, 1);

        self::assertSame(
            array_slice($postIds, 0, 2),
            array_map('intval', wp_list_pluck($query->posts, 'ID'))
        );
        self::assertSame(3, (int) $query->found_posts);
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
