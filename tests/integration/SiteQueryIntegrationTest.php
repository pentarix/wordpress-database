<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\SiteQuery;
use Pentarix\WordPressDatabase\WordPress\SiteQueryArg;

final class SiteQueryIntegrationTest extends WP_UnitTestCase
{
    public function testExecuteReturnsTheNativeWordPressQueryObject(): void
    {
        $siteId = self::factory()->blog->create();

        $query = SiteQuery::make(['site__in' => [$siteId]])->execute();

        self::assertInstanceOf(WP_Site_Query::class, $query);
        self::assertNotEmpty($query->sites);
    }

    public function testGetFirstAndCountPreserveNativeSiteResults(): void
    {
        $siteIds = [
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
        ];

        $query = SiteQuery::make(
            SiteQueryArg::make([
                'site__in' => $siteIds,
                'orderby' => 'id',
                'order' => 'ASC',
                'number' => 2,
                'fields' => 'ids',
            ])
        );

        self::assertSame(array_slice($siteIds, 0, 2), $query->get());
        self::assertSame($siteIds[0], $query->first());
        self::assertSame(3, $query->count());
    }

    public function testGetPreservesNativeCountQueryResults(): void
    {
        $siteIds = [
            self::factory()->blog->create(),
            self::factory()->blog->create(),
        ];

        $query = SiteQuery::make([
            'site__in' => $siteIds,
            'count' => true,
        ]);

        self::assertSame(2, $query->get());
    }

    public function testPaginateReturnsNativeQueryAndPaginationMetadata(): void
    {
        $siteIds = [
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
        ];

        $query = SiteQuery::make([
            'site__in' => $siteIds,
            'orderby' => 'id',
            'order' => 'ASC',
            'number' => 20,
            'no_found_rows' => true,
            'fields' => 'ids',
        ])->paginate(2, 2);

        self::assertInstanceOf(WP_Site_Query::class, $query);
        self::assertSame(2, (int) $query->query_vars['number']);
        self::assertSame(2, (int) $query->query_vars['offset']);
        self::assertFalse($query->query_vars['no_found_rows']);
        self::assertSame(5, (int) $query->found_sites);
        self::assertSame(3, (int) $query->max_num_pages);
        self::assertSame(array_slice($siteIds, 2, 2), $query->sites);
    }

    public function testPaginateAddsToAnExistingOffset(): void
    {
        $siteIds = [
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
            self::factory()->blog->create(),
        ];

        $query = SiteQuery::make([
            'site__in' => $siteIds,
            'orderby' => 'id',
            'order' => 'ASC',
            'offset' => 1,
            'fields' => 'ids',
        ])->paginate(2, 2);

        self::assertSame(3, (int) $query->query_vars['offset']);
        self::assertSame(array_slice($siteIds, 3, 2), $query->sites);
    }

    public function testFirstReturnsNullWhenNoSiteMatches(): void
    {
        $query = SiteQuery::make([
            'site__in' => [PHP_INT_MAX],
        ]);

        self::assertNull($query->first());
    }

    public function testEmptyArgumentsExecuteUsingWordPressDefaults(): void
    {
        $query = SiteQuery::make()->execute();

        self::assertInstanceOf(WP_Site_Query::class, $query);
        self::assertIsArray($query->sites);
    }
}
