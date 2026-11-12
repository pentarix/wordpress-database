<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\CommentQueryArg;
use Pentarix\WordPressDatabase\WordPress\DateQueryArg;
use Pentarix\WordPressDatabase\WordPress\MetaQueryArg;
use Pentarix\WordPressDatabase\WordPress\NetworkQueryArg;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;
use Pentarix\WordPressDatabase\WordPress\SiteQueryArg;
use Pentarix\WordPressDatabase\WordPress\TaxQueryArg;
use Pentarix\WordPressDatabase\WordPress\TermQueryArg;
use Pentarix\WordPressDatabase\WordPress\UserQueryArg;

final class QueryArgumentBuildersIntegrationTest extends WP_UnitTestCase
{
    public function testPostQueryExecutesMetaAndDateArguments(): void
    {
        $matchingPostId = self::factory()->post->create([
            'post_date' => '2024-05-10 12:00:00',
        ]);
        update_post_meta($matchingPostId, 'visibility', 'public');

        $wrongDatePostId = self::factory()->post->create([
            'post_date' => '2023-05-10 12:00:00',
        ]);
        update_post_meta($wrongDatePostId, 'visibility', 'public');

        $wrongMetaPostId = self::factory()->post->create([
            'post_date' => '2024-05-10 12:00:00',
        ]);
        update_post_meta($wrongMetaPostId, 'visibility', 'private');

        $args = PostQueryArg::make()
            ->postType('post')
            ->postStatus('publish')
            ->metaQuery(MetaQueryArg::make()->where('visibility', 'public'))
            ->dateQuery(DateQueryArg::make()->year(2024))
            ->orderBy('ID')
            ->order('ASC')
            ->toArray();

        $query = new WP_Query($args);

        self::assertSame([$matchingPostId], array_map('intval', wp_list_pluck($query->posts, 'ID')));
    }

    public function testPostQueryExecutesAnOrTaxonomyClauseFromAnInitialClause(): void
    {
        $category = wp_insert_term('Integration Category', 'category');
        $tag = wp_insert_term('Integration Tag', 'post_tag');

        self::assertNotWPError($category);
        self::assertNotWPError($tag);

        $categoryPostId = self::factory()->post->create();
        wp_set_object_terms($categoryPostId, [(int) $category['term_id']], 'category');

        $tagPostId = self::factory()->post->create();
        wp_set_object_terms($tagPostId, [(int) $tag['term_id']], 'post_tag');

        $unmatchedPostId = self::factory()->post->create();

        $taxQuery = TaxQueryArg::make([
            'taxonomy' => 'category',
            'terms' => [(int) $category['term_id']],
            'field' => 'term_id',
        ])->orWhere('post_tag', (int) $tag['term_id']);

        $query = new WP_Query(
            PostQueryArg::make()
                ->postType('post')
                ->postStatus('publish')
                ->taxQuery($taxQuery)
                ->orderBy('ID')
                ->order('ASC')
                ->toArray()
        );

        self::assertSame(
            [$categoryPostId, $tagPostId],
            wp_list_pluck($query->posts, 'ID')
        );
        self::assertNotContains($unmatchedPostId, wp_list_pluck($query->posts, 'ID'));
    }

    public function testPostQueryExecutesAnOrMetaClauseFromAnInitialClause(): void
    {
        $visibilityPostId = self::factory()->post->create();
        update_post_meta($visibilityPostId, 'visibility', 'public');

        $featuredPostId = self::factory()->post->create();
        update_post_meta($featuredPostId, 'featured', 'yes');

        $unmatchedPostId = self::factory()->post->create();

        $metaQuery = MetaQueryArg::make([
            'key' => 'visibility',
            'value' => 'public',
        ])->orWhere('featured', 'yes');

        $query = new WP_Query(
            PostQueryArg::make()
                ->postType('post')
                ->postStatus('publish')
                ->metaQuery($metaQuery)
                ->orderBy('ID')
                ->order('ASC')
                ->toArray()
        );

        self::assertEqualsCanonicalizing(
            [$visibilityPostId, $featuredPostId],
            wp_list_pluck($query->posts, 'ID')
        );
        self::assertNotContains($unmatchedPostId, wp_list_pluck($query->posts, 'ID'));
    }

    public function testUserQueryExecutesRoleAndMetaArguments(): void
    {
        $matchingUserId = self::factory()->user->create(['role' => 'subscriber']);
        update_user_meta($matchingUserId, 'membership', 'gold');

        self::factory()->user->create(['role' => 'subscriber']);

        $query = new WP_User_Query(
            UserQueryArg::make()
                ->role('subscriber')
                ->where('membership', 'gold')
                ->toArray()
        );

        self::assertSame([$matchingUserId], array_map('intval', wp_list_pluck($query->get_results(), 'ID')));
    }

    public function testTermQueryExecutesTaxonomyAndSearchArguments(): void
    {
        $term = wp_insert_term('Pentarix Integration Term', 'category');

        self::assertNotWPError($term);

        $query = new WP_Term_Query(
            TermQueryArg::make([
                'taxonomy' => 'category',
                'hide_empty' => false,
            ])->search('Pentarix Integration Term')->toArray()
        );

        self::assertSame(
            [(int) $term['term_id']],
            array_map('intval', wp_list_pluck($query->get_terms(), 'term_id'))
        );
    }

    public function testCommentQueryExecutesPostAndStatusArguments(): void
    {
        $postId = self::factory()->post->create();
        $commentId = self::factory()->comment->create([
            'comment_post_ID' => $postId,
            'comment_approved' => '1',
            'comment_type' => 'comment',
        ]);

        self::factory()->comment->create([
            'comment_post_ID' => $postId,
            'comment_approved' => '0',
            'comment_type' => 'comment',
        ]);

        $query = new WP_Comment_Query(
            CommentQueryArg::make([
                'post_id' => $postId,
                'status' => 'approve',
                'type' => 'comment',
            ])->toArray()
        );

        self::assertSame([$commentId], array_map('intval', wp_list_pluck($query->get_comments(), 'comment_ID')));
    }

    public function testSiteAndNetworkQueriesExecuteTheirArguments(): void
    {
        $networkId = self::factory()->network->create();
        $siteId = self::factory()->blog->create([
            'network_id' => $networkId,
        ]);

        $sites = get_sites(
            SiteQueryArg::make()
                ->networkId($networkId)
                ->includeSites($siteId)
                ->toArray()
        );

        $networks = get_networks(
            NetworkQueryArg::make()
                ->includeNetworks($networkId)
                ->toArray()
        );

        self::assertSame([$siteId], array_map('intval', wp_list_pluck($sites, 'blog_id')));
        self::assertSame([$networkId], array_map('intval', wp_list_pluck($networks, 'id')));
    }
}
