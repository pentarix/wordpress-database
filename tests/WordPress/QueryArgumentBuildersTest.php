<?php

declare(strict_types=1);

namespace Pentarix\\WordPressDatabase\\Tests\\WordPress;

use PHPUnit\\Framework\\TestCase;
use Pentarix\\WordPressDatabase\\WordPress\\DateQueryArg;
use Pentarix\\WordPressDatabase\\WordPress\\MetaQueryArg;
use Pentarix\\WordPressDatabase\\WordPress\\PostQueryArg;
use Pentarix\\WordPressDatabase\\WordPress\\TaxQueryArg;
use Pentarix\\WordPressDatabase\\WordPress\\UserQueryArg;

final class QueryArgumentBuildersTest extends TestCase
{
    public function testPostBuilderComposesWordPressArguments(): void
    {
        $args = PostQueryArg::make()
            ->postType('product')
            ->postStatus('publish')
            ->postsPerPage(10)
            ->page(2)
            ->metaQuery(MetaQueryArg::make()->where('featured', true))
            ->taxQuery(TaxQueryArg::make()->whereIn('product_cat', [4, 7]))
            ->dateQuery(DateQueryArg::make()->year(2025))
            ->toArray();

        self::assertSame('product', $args['post_type']);
        self::assertSame(['publish'], $args['post_status']);
        self::assertSame(10, $args['posts_per_page']);
        self::assertSame(2, $args['paged']);
        self::assertSame([['key'=>'featured','value'=>true,'compare'=>'=','type'=>'CHAR']], $args['meta_query']);
        self::assertSame('product_cat', $args['tax_query'][0]['taxonomy']);
        self::assertSame(2025, $args['date_query'][0]['year']);
    }

    public function testUserBuilderPreservesExplicitNullMetaValue(): void
    {
        $args = UserQueryArg::make()->metaKey('score')->metaValue(null)->toArray();

        self::assertSame('score', $args['meta_key']);
        self::assertArrayHasKey('meta_value', $args);
        self::assertNull($args['meta_value']);
    }

    public function testEmptyBuildersReturnEmptyArrays(): void
    {
        self::assertSame([], PostQueryArg::make()->toArray());
        self::assertSame([], UserQueryArg::make()->toArray());
        self::assertSame([], MetaQueryArg::make()->toArray());
        self::assertSame([], TaxQueryArg::make()->toArray());
    }
}
