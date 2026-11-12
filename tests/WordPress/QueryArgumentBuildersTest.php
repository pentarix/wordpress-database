<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/ephpicman LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\CommentQueryArg;
use Pentarix\WordPressDatabase\WordPress\DateQueryArg;
use Pentarix\WordPressDatabase\WordPress\IncludeExcludeIsQueryArg;
use Pentarix\WordPressDatabase\WordPress\IncludeExcludeQueryArg;
use Pentarix\WordPressDatabase\WordPress\IncludeQueryArg;
use Pentarix\WordPressDatabase\WordPress\MetaQueryArg;
use Pentarix\WordPressDatabase\WordPress\NetworkQueryArg;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;
use Pentarix\WordPressDatabase\WordPress\QueryArg;
use Pentarix\WordPressDatabase\WordPress\SearchQueryArg;
use Pentarix\WordPressDatabase\WordPress\SiteQueryArg;
use Pentarix\WordPressDatabase\WordPress\TaxQueryArg;
use Pentarix\WordPressDatabase\WordPress\TermQueryArg;
use Pentarix\WordPressDatabase\WordPress\UserQueryArg;
use PHPUnit\Framework\TestCase;

final class QueryArgumentBuildersTest extends TestCase
{
    public function testQueryArgContractAndIncludeQueryArg(): void
    {
        $query = IncludeQueryArg::make(['include' => [-1, '2', 2, 3.9]], [
            'includeKey' => 'include',
        ]);

        self::assertInstanceOf(QueryArg::class, $query);
        self::assertSame(['include' => [1, 2, 3]], $query->toArray());
        self::assertSame($query, $query->include(['4', -1]));
        self::assertSame(['include' => [1, 2, 3, 4]], $query->toArray());
        self::assertSame([], IncludeQueryArg::make()->toArray());
        self::assertSame(
            ['names' => ['alpha', 'beta']],
            IncludeQueryArg::make(['names' => ['alpha', 'beta', 'alpha']], [
                'isIdentifier' => false,
                'includeKey' => 'names',
            ])->toArray()
        );
    }

    public function testIncludeExcludeQueryArgKeepsListsDisjoint(): void
    {
        $query = IncludeExcludeQueryArg::make(
            ['only' => [1, 2, 2], 'except' => [3, 4]],
            ['includeKey' => 'only', 'excludeKey' => 'except']
        );

        $query->include([3, 5])->exclude([1, 6]);

        self::assertSame(['only' => [2, 3, 5], 'except' => [4, 1, 6]], $query->toArray());
        self::assertSame($query, $query->exclude(5));
        self::assertSame(['only' => [2, 3], 'except' => [4, 1, 6, 5]], $query->toArray());
    }

    public function testIncludeExcludeIsQueryArgSupportsSingleAndMultipleValues(): void
    {
        $single = IncludeExcludeIsQueryArg::make(
            ['is' => 'publish', 'included' => [1], 'excluded' => [2]],
            ['isIdentifier' => false, 'includeKey' => 'included', 'excludeKey' => 'excluded']
        );
        self::assertSame(
            ['included' => ['1'], 'excluded' => ['2'], 'is' => 'publish'],
            $single->toArray()
        );
        self::assertSame(['included' => ['1'], 'excluded' => ['2']], $single->is('')->toArray());

        $multi = IncludeExcludeIsQueryArg::make(
            ['is' => ['editor', '', 'author', 'editor']],
            ['isIdentifier' => false, 'multiValue' => true]
        );
        self::assertSame(['is' => ['editor', 'author']], $multi->toArray());
        self::assertSame(['is' => ['subscriber']], $multi->is(['subscriber', 'subscriber'])->toArray());
        self::assertSame([], $multi->is([])->toArray());
    }

    public function testIncludeExcludeIsQueryArgRejectsWrongIsValueShape(): void
    {
        $single = new IncludeExcludeIsQueryArg([], false);
        $multi = new IncludeExcludeIsQueryArg([], false, true);

        $this->expectException(InvalidArgumentException::class);
        $single->is(['publish']);
    }

    public function testIncludeExcludeIsQueryArgRejectsScalarInMultiValueMode(): void
    {
        $query = new IncludeExcludeIsQueryArg([], false, true);

        $this->expectException(InvalidArgumentException::class);
        $query->is('publish');
    }

    public function testSearchQueryArgValidatesAndNormalizesSearchColumns(): void
    {
        $query = SearchQueryArg::make(
            ['search' => 'needle', 'searchColumns' => ['title', 'content', 'title', '']],
            ['allowedSearchColumns' => ['title', 'content', 'title']]
        );

        self::assertSame(
            ['search' => 'needle', 'searchColumns' => ['title', 'content']],
            $query->toArray()
        );
        self::assertSame(['searchColumns' => ['content']], $query->search('')->searchColumns('content')->toArray());
        self::assertSame([], $query->searchColumns([])->toArray());
    }

    public function testSearchQueryArgRequiresAllowedColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SearchQueryArg::make();
    }

    public function testSearchQueryArgRejectsUnknownColumns(): void
    {
        $query = new SearchQueryArg(['title']);

        $this->expectException(InvalidArgumentException::class);
        $query->searchColumns('unknown');
    }

    public function testSearchQueryArgRejectsNonStringColumns(): void
    {
        $query = new SearchQueryArg(['title']);

        $this->expectException(InvalidArgumentException::class);
        $query->searchColumns(['title', 12]);
    }

    public function testDateQueryArgBuildsClausesAndBooleanGroups(): void
    {
        $query = DateQueryArg::make()
            ->after('2024-01-01')
            ->orWhere(['year' => 2020])
            ->whereGroup(
                static fn (DateQueryArg $group) => $group->month(1)->day(15),
                'OR'
            );

        $array = $query->toArray();
        self::assertSame('AND', $array['relation']);
        self::assertSame('OR', $array[0]['relation']);
        self::assertSame('2024-01-01', $array[0][0][0]['after']);
        self::assertSame(2020, $array[0][1]['year']);
        self::assertSame('OR', $array[1]['relation']);
        self::assertSame(1, $array[1][0][0]['month']);
        self::assertSame(15, $array[1][0][1]['day']);
    }

    public function testDateQueryArgSupportsDateHelpersAndEmptyGroups(): void
    {
        $query = DateQueryArg::make()
            ->before(['year' => 2023], true, 'post_modified')
            ->between('2020-01-01', '2020-12-31')
            ->week(3)->hour(10)->minute(20)->second(30)
            ->whereAny(static function (DateQueryArg $group): void {
                $group->year(2022);
            })
            ->whereAll(static function (DateQueryArg $group): void {
            });

        self::assertCount(7, $query->toArray());
        self::assertSame('post_modified', $query->toArray()[0]['column']);
        self::assertSame('OR', $query->toArray()[6]['relation']);
    }

    public function testMetaQueryArgSupportsComparisonsAndGroups(): void
    {
        $query = MetaQueryArg::make()
            ->where('color', 'blue')
            ->whereIn('size', ['s', 'm'])
            ->orWhere('price', '>', 10, 'NUMERIC')
            ->whereMetaKey('rank', 'LIKE', 'BINARY')
            ->whereNotExists('deprecated')
            ->whereAny(static function (MetaQueryArg $group): void {
                $group->whereLike('label', '%a%')->whereNotLike('label', '%b%');
            });

        $array = $query->toArray();
        self::assertSame('AND', $array['relation']);
        self::assertSame('OR', $array[0]['relation']);
        self::assertSame('=', $array[0][0][0]['compare']);
        self::assertSame('IN', $array[0][0][1]['compare']);
        self::assertNotEmpty($array);
    }

    public function testMetaQueryArgSupportsAllComparisonHelpers(): void
    {
        $query = MetaQueryArg::make()
            ->whereNotIn('a', [1, 2])
            ->whereBetween('b', [1, 5])
            ->whereNotBetween('c', [2, 4])
            ->whereRegexp('d', '^x')
            ->whereNotRegexp('e', '^y')
            ->whereExists('f')
            ->whereClause(['key' => 'custom'], 'invalid')
            ->whereAll(static function (MetaQueryArg $group): void {
                $group->where('nested', null);
            });

        self::assertCount(8, $query->toArray());
        self::assertSame('NOT IN', $query->toArray()[0]['compare']);
        self::assertSame('EXISTS', $query->toArray()[5]['compare']);
    }

    public function testTaxQueryArgBuildsConditionsAndGroups(): void
    {
        $query = TaxQueryArg::make()
            ->where('category', [1, 2])
            ->orWhere('post_tag', 'news', 'slug', 'IN', false)
            ->whereNotIn('genre', ['drama'])
            ->whereAnd('actor', [3, 4])
            ->whereExists('language')
            ->whereNotExists('obsolete')
            ->whereGroup(static function (TaxQueryArg $group): void {
                $group->whereIn('topic', [7])->whereExists('format');
            }, 'OR');

        $array = $query->toArray();
        self::assertSame('AND', $array['relation']);
        self::assertSame('OR', $array[0]['relation']);
        self::assertSame('category', $array[0][0][0]['taxonomy']);
        self::assertSame('NOT IN', $array[1]['operator']);
        self::assertFalse($array[0][1]['include_children']);
        self::assertSame('EXISTS', $array[3]['operator']);
    }

    public function testPostQueryArgMapsNestedQueriesAndFluentMethods(): void
    {
        $date = DateQueryArg::make()->year(2024);
        $meta = MetaQueryArg::make()->where('featured', true);
        $tax = TaxQueryArg::make()->whereIn('category', [2]);

        $query = PostQueryArg::make([
            'post__in' => [1, 2],
            'post__not_in' => [3],
            'author__in' => [7],
            'post_type' => ['post', 'page'],
            'post_status' => ['publish'],
            's' => 'hello',
            'posts_per_page' => 10,
            'paged' => 0,
            'offset' => -4,
            'order' => 'desc',
            'name' => 'sample',
            'post_name__in' => ['one', 'one', 2],
            'has_password' => false,
            'cache_results' => true,
        ])
            ->includeIds(4)
            ->excludeIds(1)
            ->postType('book')
            ->postTypesIn('page')
            ->postStatus('draft')
            ->postStatusesNotIn('private')
            ->authorsIn('8')
            ->parentsNotIn(9)
            ->search('updated')
            ->dateQuery($date)
            ->metaQuery($meta)
            ->taxQuery($tax)
            ->postsPerPage(5)
            ->page(3)
            ->offset(2)
            ->orderBy(['date' => 'DESC'])
            ->order('invalid')
            ->fields('ids');

        $args = $query->toArray();
        self::assertSame([2, 4], $args['post__in']);
        self::assertSame([3, 1], $args['post__not_in']);
        self::assertSame(['page'], $args['post_type__in']);
        self::assertSame(['book'], $args['post_type']);
        self::assertSame('updated', $args['s']);
        self::assertSame(5, $args['posts_per_page']);
        self::assertSame(3, $args['paged']);
        self::assertSame(2, $args['offset']);
        self::assertSame('ASC', $args['order']);
        self::assertSame('ids', $args['fields']);
        self::assertSame($date->toArray(), $args['date_query']);
        self::assertSame($meta->toArray(), $args['meta_query']);
        self::assertSame($tax->toArray(), $args['tax_query']);
        self::assertSame(['one', '2'], $args['post_name__in']);
    }

    public function testPostQueryArgAcceptsArrayNestedQueriesAndEmptyDefaults(): void
    {
        $query = PostQueryArg::make()
            ->dateQuery(['year' => 2020])
            ->metaQuery([['key' => 'x', 'value' => 1]])
            ->taxQuery([['taxonomy' => 'category', 'terms' => [1]]]);

        $args = $query->toArray();
        self::assertSame(['year' => 2020], $args['date_query']);
        self::assertSame([['key' => 'x', 'value' => 1]], $args['meta_query']);
        self::assertSame([['taxonomy' => 'category', 'terms' => [1]]], $args['tax_query']);
        self::assertSame([], PostQueryArg::make()->toArray());
    }

    public function testCommentQueryArgMapsConstructorAndFluentValues(): void
    {
        $query = CommentQueryArg::make([
            'comment__in' => [1, 2],
            'comment__not_in' => [3],
            'post__in' => [4],
            'parent__not_in' => [5],
            'author__in' => [6],
            'post_id' => '7',
            'parent' => 8,
            'user_id' => 9,
            'author_email' => 'a@example.com',
            'author_url' => 'https://example.com',
            'author_ip' => '127.0.0.1',
            'type' => 'review',
            'status' => 'approve',
            'number' => 20,
            'offset' => 2,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'hierarchical' => false,
            'count' => true,
            'no_found_rows' => true,
            'update_comment_meta_cache' => false,
            'update_comment_post_cache' => true,
        ])->includeComments([3, 4])->excludeComments(1)->postId(10)->search('term');

        $args = $query->toArray();
        self::assertSame([2, 3, 4], $args['comment__in']);
        self::assertSame([1], $args['comment__not_in']);
        self::assertSame([6], $args['author__in']);
        self::assertSame(10, $args['post_id']);
        self::assertSame('term', $args['search']);
        self::assertFalse($args['hierarchical']);
        self::assertTrue($args['update_comment_post_cache']);
        self::assertSame([], CommentQueryArg::make()->toArray());
    }

    public function testNetworkQueryArgMapsIdsAndOptions(): void
    {
        $query = NetworkQueryArg::make([
            'network__in' => [1],
            'network__not_in' => [2],
            'network_id' => '3',
            'domain' => 'example.com',
            'path' => '/',
            'number' => 10,
            'offset' => 2,
            'orderby' => 'id',
            'order' => 'DESC',
            'fields' => 'ids',
            'count' => false,
            'update_network_cache' => true,
        ])->includeNetworks(4)->excludeNetworks(1)->networkId(5);

        $args = $query->toArray();
        self::assertSame([4], $args['network__in']);
        self::assertSame([2, 1], $args['network__not_in']);
        self::assertSame(5, $args['network_id']);
        self::assertFalse($args['count']);
        self::assertTrue($args['update_network_cache']);
        self::assertSame([], NetworkQueryArg::make()->toArray());
    }

    public function testSiteQueryArgMapsIdsAndOptions(): void
    {
        $query = SiteQueryArg::make([
            'site__in' => [1, 2],
            'site__not_in' => [3],
            'network__in' => [4],
            'ID' => '5',
            'network_id' => 6,
            'domain' => 'example.org',
            'path' => '/blog/',
            'search' => 'sample',
            'number' => 10,
            'offset' => 1,
            'orderby' => ['name' => 'ASC'],
            'order' => 'DESC',
            'fields' => 'ids',
            'public' => false,
            'archived' => true,
            'mature' => false,
            'spam' => true,
            'deleted' => false,
            'lang_id' => 2,
            'update_site_cache' => true,
        ])->includeSites([3, 7])->excludeSites(1)->networkId(8)->search('changed');

        $args = $query->toArray();
        self::assertSame([2, 3, 7], $args['site__in']);
        self::assertSame([1], $args['site__not_in']);
        self::assertSame(5, $args['ID']);
        self::assertSame(8, $args['network_id']);
        self::assertSame('changed', $args['search']);
        self::assertFalse($args['public']);
        self::assertTrue($args['archived']);
        self::assertSame([], SiteQueryArg::make()->toArray());
    }

    public function testTermQueryArgMapsTaxonomyAndTermOptions(): void
    {
        $query = TermQueryArg::make([
            'include' => [1, 2],
            'exclude' => [3],
            'parent__in' => [4],
            'term_id' => '5',
            'parent' => 6,
            'taxonomy' => ['category', 'post_tag'],
            'name' => 'News',
            'slug' => 'news',
            'search' => 'news',
            'name__like' => 'new',
            'description__like' => 'latest',
            'number' => 10,
            'offset' => 2,
            'orderby' => 'name',
            'order' => 'ASC',
            'fields' => 'ids',
            'hide_empty' => false,
            'hierarchical' => true,
            'pad_counts' => false,
            'get' => true,
            'update_term_meta_cache' => false,
            'cache_domain' => 'test',
        ])->includeTerms(7)->excludeTerms(1)->taxonomy('post')->search('updated');

        $args = $query->toArray();
        self::assertSame([2, 7], $args['include']);
        self::assertSame([3, 1], $args['exclude']);
        self::assertSame(5, $args['term_id']);
        self::assertSame('post', $args['taxonomy']);
        self::assertSame('updated', $args['search']);
        self::assertFalse($args['hide_empty']);
        self::assertSame('test', $args['cache_domain']);
        self::assertSame([], TermQueryArg::make()->toArray());
    }

    public function testUserQueryArgMapsIdentitySearchMetaAndPagination(): void
    {
        $query = UserQueryArg::make([
            'include' => [1, 2],
            'exclude' => [3],
            'role' => ['editor'],
            'role__in' => ['author'],
            'role__not_in' => ['subscriber'],
            'capability' => ['edit_posts'],
            'login' => 'alice',
            'login__in' => ['bob'],
            'nicename' => 'alice-name',
            'search' => 'alice',
            'search_columns' => ['user_email'],
            'orderby' => 'login',
            'order' => 'desc',
            'number' => 20,
            'offset' => 2,
            'fields' => ['ID', 'display_name'],
            'count_total' => false,
            'blog_id' => 4,
            'meta_key' => 'level',
            'meta_value' => 0,
            'meta_compare' => '>=',
            'meta_type' => 'NUMERIC',
            'meta_compare_key' => 'LIKE',
            'meta_type_key' => 'BINARY',
            'paged' => 0,
            'who' => 'authors',
            'has_published_posts' => ['post'],
            'cache_results' => true,
        ])->includeIds(4)->excludeIds(1)
            ->role('administrator')->rolesIn('editor')->rolesNotIn('author')
            ->capability('manage_options')->capabilitiesIn('edit_pages')->capabilitiesNotIn('read')
            ->login('root')->loginsIn('admin')->loginsNotIn('guest')
            ->nicename('root-name')->nicenamesIn('nice')->nicenamesNotIn('not-nice')
            ->searchByEmail('root@example.com')
            ->orderBy('ID', 'DESC')->limit(5)->offset(3)->select('ID')->countTotal(true)
            ->blogId(9)->metaKey('score')->metaValue(null)->metaCompare('=')->metaType('SIGNED')
            ->metaCompareKey('=')->metaTypeKey('CHAR')->paged(2)->who('users')
            ->hasPublishedPosts(false)->cacheResults(false)
            ->where('active', 1)->orWhere('score', '>', 5)->whereExists('flag');

        $args = $query->toArray();
        self::assertSame([2, 4], $args['include']);
        self::assertSame([3, 1], $args['exclude']);
        self::assertSame(['administrator'], $args['role']);
        self::assertSame(['editor'], $args['role__in']);
        self::assertSame(['subscriber', 'author'], $args['role__not_in']);
        self::assertSame(['manage_options'], $args['capability']);
        self::assertSame('root', $args['login']);
        self::assertSame('root-name', $args['nicename']);
        self::assertSame('root@example.com', $args['search']);
        self::assertSame(['user_email'], $args['search_columns']);
        self::assertSame('DESC', $args['order']);
        self::assertSame(5, $args['number']);
        self::assertSame(9, $args['blog_id']);
        self::assertNull($args['meta_value']);
        self::assertSame(2, $args['paged']);
        self::assertFalse($args['has_published_posts']);
        self::assertFalse($args['cache_results']);
        self::assertArrayHasKey('meta_query', $args);
    }


    public function testQueryBuildersPreserveAdditionalWordPressArguments(): void
    {
        $post = PostQueryArg::make([
            'p' => 42,
            'author' => 7,
            'search_columns' => ['post_title'],
            'meta_key' => ['colour', 'size'],
        ])->set('update_menu_item_cache', false);

        self::assertSame(42, $post->toArray()['p']);
        self::assertSame(7, $post->toArray()['author']);
        self::assertSame(['post_title'], $post->toArray()['search_columns']);
        self::assertSame(['colour', 'size'], $post->toArray()['meta_key']);
        self::assertFalse($post->toArray()['update_menu_item_cache']);

        $comment = CommentQueryArg::make([
            'author__in' => [3, 4],
            'date_query' => [['year' => 2024]],
            'include_unapproved' => [5],
            'meta_query' => [['key' => 'visible', 'value' => 1]],
        ]);

        self::assertSame([3, 4], $comment->toArray()['author__in']);
        self::assertArrayHasKey('date_query', $comment->toArray());
        self::assertArrayHasKey('include_unapproved', $comment->toArray());
        self::assertArrayHasKey('meta_query', $comment->toArray());

        $term = TermQueryArg::make([
            'object_ids' => [10, 11],
            'exclude_tree' => [12],
            'term_taxonomy_id' => [13],
            'childless' => true,
        ]);

        self::assertSame([10, 11], $term->toArray()['object_ids']);
        self::assertSame([12], $term->toArray()['exclude_tree']);
        self::assertSame([13], $term->toArray()['term_taxonomy_id']);
        self::assertTrue($term->toArray()['childless']);

        $site = SiteQueryArg::make([
            'count' => true,
            'date_query' => [['after' => '2024-01-01']],
            'domain__in' => ['example.com'],
            'update_site_meta_cache' => false,
        ]);

        self::assertTrue($site->toArray()['count']);
        self::assertSame(['example.com'], $site->toArray()['domain__in']);
        self::assertFalse($site->toArray()['update_site_meta_cache']);

        $network = NetworkQueryArg::make([
            'no_found_rows' => true,
            'domain__not_in' => ['blocked.example'],
            'path__in' => ['/network/'],
        ])->setMany(['custom_query_var' => 'value', 'number' => 5]);

        self::assertTrue($network->toArray()['no_found_rows']);
        self::assertSame(['blocked.example'], $network->toArray()['domain__not_in']);
        self::assertSame(['/network/'], $network->toArray()['path__in']);
        self::assertSame('value', $network->toArray()['custom_query_var']);
        self::assertSame(5, $network->toArray()['number']);
    }

    public function testDateQueryArgSupportsCompleteDatePartsAndArrayValues(): void
    {
        $args = DateQueryArg::make()
            ->year([2022, 2024], 'post_date', 'IN')
            ->month([1, 6])
            ->dayOfYear([10, 20])
            ->dayOfWeek(2)
            ->dayOfWeekIso([1, 5])
            ->toArray();

        self::assertSame([2022, 2024], $args[0]['year']);
        self::assertSame('IN', $args[0]['compare']);
        self::assertSame([1, 6], $args[1]['month']);
        self::assertSame([10, 20], $args[2]['dayofyear']);
        self::assertSame(2, $args[3]['dayofweek']);
        self::assertSame([1, 5], $args[4]['dayofweek_iso']);
            }

    public function testUserQueryArgAcceptsMultipleMetaKeys(): void
    {
        self::assertSame(
            ['meta_key' => ['first_key', 'second_key']],
            UserQueryArg::make()->metaKey(['first_key', 'second_key'])->toArray()
        );

        self::assertSame(
            ['meta_key' => ['first_key', 'second_key']],
            UserQueryArg::make(['meta_key' => ['first_key', 'second_key']])->toArray()
        );
    }

    public function testUserQueryArgSupportsEmptyAndAlternativeSearchMethods(): void
    {
        self::assertSame([], UserQueryArg::make()->toArray());

        $query = UserQueryArg::make()
            ->searchByLogin('login')
            ->searchByUrl('url')
            ->searchByNicename('nice')
            ->searchByDisplayName('display');

        self::assertSame('display', $query->toArray()['search']);
        self::assertSame(['display_name'], $query->toArray()['search_columns']);
        self::assertContains('user_pass', UserQueryArg::USER_COLUMNS);
    }
}
