<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\Model\Model;
use Pentarix\WordPressDatabase\Model\ModelPaginator;

final class ArticleModel extends Model
{
    protected static string $postType = 'post';
}

final class ProductModel extends Model
{
    protected static string $postType = 'product';
}

final class ModelIntegrationTest extends WP_UnitTestCase
{
    public function testModelCreatesUpdatesAndRefreshesThroughPostEntity(): void
    {
        $model = ArticleModel::make([
            'post_title' => 'Initial title',
            'post_author' => self::factory()->user->create(),
            'post_content' => 'Initial content',
            'post_status' => 'publish',
        ]);

        self::assertFalse($model->exists());
        self::assertTrue($model->save());
        self::assertGreaterThan(0, $model->id());
        self::assertSame('Initial title', $model->get('post_title'));
        self::assertInstanceOf(WP_User::class, $model->author());

        $model->set('post_title', 'Updated title');
        self::assertTrue($model->save());
        self::assertSame('Updated title', ArticleModel::find($model->id())->get('post_title'));

        $model->set('post_title', 'Unsaved title');
        self::assertTrue($model->refresh());
        self::assertSame('Updated title', $model->get('post_title'));
    }

    public function testModelCreateFactoryAndPostTypeIsolation(): void
    {
        register_post_type('product', ['public' => true]);

        $model = ProductModel::create([
            'post_title' => 'Example product',
            'post_status' => 'publish',
        ]);

        self::assertInstanceOf(ProductModel::class, $model);
        self::assertSame('product', get_post_type($model->id()));
        self::assertNull(ArticleModel::find($model->id()));
        self::assertInstanceOf(ProductModel::class, ProductModel::find($model->id()));
    }

    public function testModelQueryReturnsModelInstancesAndSupportsRelationsByAuthor(): void
    {
        $userId = self::factory()->user->create();
        self::factory()->post->create([
            'post_author' => $userId,
            'post_title' => 'Author post',
            'post_status' => 'publish',
        ]);
        self::factory()->post->create([
            'post_author' => self::factory()->user->create(),
            'post_title' => 'Other post',
            'post_status' => 'publish',
        ]);

        $query = ArticleModel::query()
            ->author($userId)
            ->status('publish')
            ->orderBy('ID')
            ->order('ASC');

        self::assertSame(1, $query->count());
        self::assertInstanceOf(ArticleModel::class, $query->first());
        self::assertCount(1, $query->get());
    }

    public function testModelQueryPaginationReturnsModelInstancesAndMetadata(): void
    {
        foreach (range(1, 5) as $number) {
            self::factory()->post->create([
                'post_title' => 'Post ' . $number,
                'post_status' => 'publish',
            ]);
        }

        $result = ArticleModel::query()
            ->status('publish')
            ->orderBy('ID')
            ->order('ASC')
            ->paginate(2, 2);

        self::assertInstanceOf(ModelPaginator::class, $result);
        self::assertCount(2, $result->items);
        self::assertSame(5, $result->total);
        self::assertSame(2, $result->currentPage);
        self::assertSame(3, $result->lastPage);
        self::assertInstanceOf(ArticleModel::class, $result->items[0]);
    }

    public function testModelDeleteDelegatesToPostEntity(): void
    {
        $model = ArticleModel::make([
            'post_title' => 'Delete me',
            'post_status' => 'publish',
        ]);
        self::assertTrue($model->save());
        self::assertInstanceOf(WP_Post::class, $model->delete(true));
        self::assertNull(ArticleModel::find($model->id()));
    }
}
