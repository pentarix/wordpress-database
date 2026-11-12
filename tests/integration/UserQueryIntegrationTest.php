<?php

declare(strict_types=1);

use Pentarix\\WordPressDatabase\\WordPress\\UserQuery;
use Pentarix\\WordPressDatabase\\WordPress\\UserQueryArg;

final class UserQueryIntegrationTest extends WP_UnitTestCase
{
    public function testExecuteReturnsTheNativeWordPressQueryObject(): void
    {
        $userId = self::factory()->user->create();

        $query = UserQuery::make(
            UserQueryArg::make()->includeIds([$userId])
        )->execute();

        self::assertInstanceOf(WP_User_Query::class, $query);
        self::assertCount(1, $query->get_results());
    }

    public function testGetFirstAndCountUseWordPressQueryResults(): void
    {
        $userIds = [
            self::factory()->user->create(),
            self::factory()->user->create(),
            self::factory()->user->create(),
        ];

        $query = UserQuery::make(
            UserQueryArg::make()
                ->includeIds($userIds)
                ->orderBy('ID', 'ASC')
                ->limit(2)
                ->countTotal(false)
        );

        self::assertSame(
            array_slice($userIds, 0, 2),
            array_map(
                static fn (WP_User $user): int => (int) $user->ID,
                $query->get()
            )
        );
        self::assertSame($userIds[0], (int) $query->first()->ID);
        self::assertSame(3, $query->count());
    }

    public function testPaginateReturnsNativeQueryAndPaginationMetadata(): void
    {
        $userIds = [
            self::factory()->user->create(),
            self::factory()->user->create(),
            self::factory()->user->create(),
            self::factory()->user->create(),
            self::factory()->user->create(),
        ];

        $userQuery = UserQuery::make(
            UserQueryArg::make()
                ->includeIds($userIds)
                ->orderBy('ID', 'ASC')
                ->limit(20)
                ->countTotal(false)
        );

        $query = $userQuery->paginate(2, 2);

        self::assertInstanceOf(WP_User_Query::class, $query);
        self::assertSame(2, (int) $query->query_vars['paged']);
        self::assertSame(2, (int) $query->query_vars['number']);
        self::assertTrue($query->query_vars['count_total']);
        self::assertSame(5, $query->get_total());
        self::assertSame(
            array_slice($userIds, 2, 2),
            array_map(
                static fn (WP_User $user): int => (int) $user->ID,
                $query->get_results()
            )
        );
    }

    public function testPaginateIgnoresAnExistingOffset(): void
    {
        $userIds = [
            self::factory()->user->create(),
            self::factory()->user->create(),
            self::factory()->user->create(),
        ];

        $query = UserQuery::make(
            UserQueryArg::make()
                ->includeIds($userIds)
                ->orderBy('ID', 'ASC')
                ->offset(2)
        )->paginate(2, 1);

        self::assertSame(
            array_slice($userIds, 0, 2),
            array_map(
                static fn (WP_User $user): int => (int) $user->ID,
                $query->get_results()
            )
        );
        self::assertSame(3, $query->get_total());
    }

    public function testFirstReturnsNullWhenNoUserMatches(): void
    {
        $query = UserQuery::make(
            UserQueryArg::make()->includeIds([PHP_INT_MAX])
        );

        self::assertNull($query->first());
    }

    public function testEmptyArgumentsExecuteUsingWordPressDefaults(): void
    {
        self::factory()->user->create();

        $query = UserQuery::make()->execute();

        self::assertNotEmpty($query->get_results());
    }
}
