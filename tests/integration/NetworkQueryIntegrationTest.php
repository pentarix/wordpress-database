<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\NetworkQuery;
use Pentarix\WordPressDatabase\WordPress\NetworkQueryArg;

final class NetworkQueryIntegrationTest extends WP_UnitTestCase
{
    public function testExecuteReturnsTheNativeWordPressQueryObject(): void
    {
        $networkId = self::factory()->network->create();

        $query = NetworkQuery::make(['network__in' => [$networkId]])->execute();

        self::assertInstanceOf(WP_Network_Query::class, $query);
        self::assertNotEmpty($query->networks);
    }

    public function testGetFirstAndCountPreserveNativeNetworkResults(): void
    {
        $networkIds = [
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
        ];

        $query = NetworkQuery::make(
            NetworkQueryArg::make([
                'network__in' => $networkIds,
                'orderby' => 'id',
                'order' => 'ASC',
                'number' => 2,
                'fields' => 'ids',
            ])
        );

        self::assertSame(array_slice($networkIds, 0, 2), $query->get());
        self::assertSame($networkIds[0], $query->first());
        self::assertSame(3, $query->count());
    }

    public function testGetPreservesNativeCountQueryResults(): void
    {
        $networkIds = [
            self::factory()->network->create(),
            self::factory()->network->create(),
        ];

        $query = NetworkQuery::make([
            'network__in' => $networkIds,
            'count' => true,
        ]);

        self::assertSame(2, $query->get());
    }

    public function testGetReturnsNativeNetworkObjectsByDefault(): void
    {
        $networkId = self::factory()->network->create();

        $networks = NetworkQuery::make(['network__in' => [$networkId]])->get();

        self::assertCount(1, $networks);
        self::assertInstanceOf(WP_Network::class, $networks[0]);
        self::assertSame($networkId, (int) $networks[0]->id);
    }

    public function testPaginateReturnsNativeQueryAndPaginationMetadata(): void
    {
        $networkIds = [
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
        ];

        $query = NetworkQuery::make([
            'network__in' => $networkIds,
            'orderby' => 'id',
            'order' => 'ASC',
            'number' => 20,
            'no_found_rows' => true,
            'fields' => 'ids',
        ])->paginate(2, 2);

        self::assertInstanceOf(WP_Network_Query::class, $query);
        self::assertSame(2, (int) $query->query_vars['number']);
        self::assertSame(2, (int) $query->query_vars['offset']);
        self::assertFalse($query->query_vars['no_found_rows']);
        self::assertSame(5, (int) $query->found_networks);
        self::assertSame(3, (int) $query->max_num_pages);
        self::assertSame(array_slice($networkIds, 2, 2), $query->networks);
    }

    public function testPaginateAddsToAnExistingOffset(): void
    {
        $networkIds = [
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
            self::factory()->network->create(),
        ];

        $query = NetworkQuery::make([
            'network__in' => $networkIds,
            'orderby' => 'id',
            'order' => 'ASC',
            'offset' => 1,
            'fields' => 'ids',
        ])->paginate(2, 2);

        self::assertSame(3, (int) $query->query_vars['offset']);
        self::assertSame(array_slice($networkIds, 3, 2), $query->networks);
    }

    public function testFirstReturnsNullWhenNoNetworkMatches(): void
    {
        $query = NetworkQuery::make([
            'network__in' => [PHP_INT_MAX],
        ]);

        self::assertNull($query->first());
    }

    public function testEmptyArgumentsExecuteUsingWordPressDefaults(): void
    {
        $query = NetworkQuery::make()->execute();

        self::assertInstanceOf(WP_Network_Query::class, $query);
        self::assertIsArray($query->networks);
    }
}
