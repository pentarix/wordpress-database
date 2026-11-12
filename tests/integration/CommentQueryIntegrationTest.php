<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\CommentQuery;
use Pentarix\WordPressDatabase\WordPress\CommentQueryArg;

final class CommentQueryIntegrationTest extends WP_UnitTestCase
{
    private int $postId;

    public function set_up(): void
    {
        parent::set_up();
        $this->postId = self::factory()->post->create();
    }

    public function testExecuteReturnsTheNativeWordPressQueryObject(): void
    {
        self::factory()->comment->create(['comment_post_ID' => $this->postId]);

        $query = CommentQuery::make(
            CommentQueryArg::make(['post_id' => $this->postId])
        )->execute();

        self::assertInstanceOf(WP_Comment_Query::class, $query);
        self::assertCount(1, $query->comments);
    }

    public function testGetFirstAndCountUseWordPressQueryResults(): void
    {
        $commentIds = [
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
        ];

        $query = CommentQuery::make(
            CommentQueryArg::make([
                'post_id' => $this->postId,
                'orderby' => 'comment_ID',
                'order' => 'ASC',
                'number' => 2,
                'no_found_rows' => true,
            ])
        );

        self::assertSame(
            array_slice($commentIds, 0, 2),
            array_map('intval', wp_list_pluck($query->get(), 'comment_ID'))
        );
        self::assertSame($commentIds[0], (int) $query->first()->comment_ID);
        self::assertSame(3, $query->count());
    }

    public function testPaginateReturnsNativeQueryAndPaginationMetadata(): void
    {
        $commentIds = [
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
        ];

        $commentQuery = CommentQuery::make(
            CommentQueryArg::make([
                'post_id' => $this->postId,
                'orderby' => 'comment_ID',
                'order' => 'ASC',
                'number' => 20,
                'no_found_rows' => true,
            ])
        );

        $query = $commentQuery->paginate(2, 2);

        self::assertInstanceOf(WP_Comment_Query::class, $query);
        self::assertSame(2, (int) $query->query_vars['paged']);
        self::assertSame(2, (int) $query->query_vars['number']);
        self::assertFalse($query->query_vars['no_found_rows']);
        self::assertSame(5, (int) $query->found_comments);
        self::assertSame(3, (int) $query->max_num_pages);
        self::assertSame(
            array_slice($commentIds, 2, 2),
            array_map('intval', wp_list_pluck($query->comments, 'comment_ID'))
        );

        self::assertCount(5, $commentQuery->execute()->comments);
    }

    public function testPaginateIgnoresAnExistingOffset(): void
    {
        $commentIds = [
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
            self::factory()->comment->create(['comment_post_ID' => $this->postId]),
        ];

        $query = CommentQuery::make(
            CommentQueryArg::make([
                'post_id' => $this->postId,
                'orderby' => 'comment_ID',
                'order' => 'ASC',
                'offset' => 2,
            ])
        )->paginate(2, 1);

        self::assertSame(
            array_slice($commentIds, 0, 2),
            array_map('intval', wp_list_pluck($query->comments, 'comment_ID'))
        );
        self::assertSame(3, (int) $query->found_comments);
    }

    public function testFirstReturnsNullWhenNoCommentMatches(): void
    {
        $query = CommentQuery::make(
            CommentQueryArg::make([
                'post_id' => $this->postId,
                'comment__in' => [PHP_INT_MAX],
            ])
        );

        self::assertNull($query->first());
    }

    public function testEmptyArgumentsExecuteUsingWordPressDefaults(): void
    {
        self::factory()->comment->create(['comment_post_ID' => $this->postId]);

        $query = CommentQuery::make()->execute();

        self::assertNotEmpty($query->comments);
    }

    public function testGetPreservesNativeCountQueryResults(): void
    {
        self::factory()->comment->create(['comment_post_ID' => $this->postId]);
        self::factory()->comment->create(['comment_post_ID' => $this->postId]);

        $query = CommentQuery::make(['post_id' => $this->postId, 'count' => true]);

        self::assertSame(2, $query->get());
    }
}
