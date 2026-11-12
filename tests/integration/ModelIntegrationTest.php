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

    public function testModelAttributesRoutePostFieldsAndMetadataTransparently(): void
    {
        $model = ArticleModel::make([
            'post_title' => 'Attribute example',
            'post_status' => 'publish',
            'subtitle' => 'A metadata value',
            'featured' => false,
            'settings' => ['layout' => 'wide'],
        ]);

        self::assertTrue($model->save());

        $postId = $model->id();

        self::assertSame('Attribute example', get_post_field('post_title', $postId));
        self::assertSame('A metadata value', get_post_meta($postId, 'subtitle', true));
        self::assertSame('', get_post_meta($postId, 'featured', true));
        self::assertSame(['layout' => 'wide'], get_post_meta($postId, 'settings', true));

        $found = ArticleModel::find($postId);

        self::assertSame('Attribute example', $found->get('post_title'));
        self::assertSame('A metadata value', $found->get('subtitle'));
        self::assertSame('', $found->get('featured', true));
        self::assertSame(['layout' => 'wide'], $found->settings);
        self::assertTrue($found->has('featured'));
        self::assertSame($postId, $found->get('ID'));
        self::assertSame('post', $found->get('post_type'));

        $found->subtitle = 'Updated metadata';
        $found->set('post_title', 'Updated core field');
        self::assertTrue($found->save());

        self::assertSame('Updated core field', get_post_field('post_title', $postId));
        self::assertSame('Updated metadata', get_post_meta($postId, 'subtitle', true));

        $found->removeAttribute('subtitle');
        self::assertFalse($found->has('subtitle'));
        self::assertNull($found->get('subtitle'));
        self::assertTrue($found->save());
        self::assertFalse(metadata_exists('post', $postId, 'subtitle'));
    }

    public function testModelRefreshDiscardsUnsavedMetadataChanges(): void
    {
        $model = ArticleModel::make([
            'post_title' => 'Refresh metadata',
            'subtitle' => 'Persisted value',
        ]);

        self::assertTrue($model->save());
        $model->set('subtitle', 'Unsaved value');

        self::assertTrue($model->refresh());
        self::assertSame('Persisted value', $model->get('subtitle'));
    }

    public function testModelParentAndChildrenRelationshipsReturnModelInstances(): void
    {
        $parent = ArticleModel::create([
            'post_title' => 'Parent article',
            'post_status' => 'publish',
        ]);
        $child = ArticleModel::create([
            'post_title' => 'Child article',
            'post_status' => 'publish',
            'post_parent' => $parent->id(),
        ]);

        self::assertInstanceOf(ArticleModel::class, $child->parent());
        self::assertSame($parent->id(), $child->parent()->id());

        $children = $parent->children();

        self::assertCount(1, $children);
        self::assertInstanceOf(ArticleModel::class, $children[0]);
        self::assertSame($child->id(), $children[0]->id());
    }

    public function testModelThumbnailRelationshipReturnsNativeAttachmentPost(): void
    {
        $model = ArticleModel::create([
            'post_title' => 'Article with thumbnail',
            'post_status' => 'publish',
        ]);
        $attachmentId = self::factory()->attachment->create_upload_object(
            DIR_TESTDATA . '/images/canola.jpg',
            $model->id()
        );

        self::assertIsInt($attachmentId);
        self::assertNotFalse(set_post_thumbnail($model->id(), $attachmentId));

        $thumbnail = $model->thumbnail();

        self::assertInstanceOf(WP_Post::class, $thumbnail);
        self::assertSame($attachmentId, (int) $thumbnail->ID);
        self::assertSame('attachment', $thumbnail->post_type);
    }

    public function testModelTermsAndCommentsRelationshipsUseNativeApis(): void
    {
        register_taxonomy('article_topic', 'post');

        $model = ArticleModel::create([
            'post_title' => 'Article with related data',
            'post_status' => 'publish',
        ]);
        $term = wp_insert_term('Engineering', 'article_topic');

        self::assertIsArray($term);
        wp_set_object_terms($model->id(), [(int) $term['term_id']], 'article_topic');

        self::factory()->comment->create([
            'comment_post_ID' => $model->id(),
            'comment_content' => 'A related comment',
        ]);

        $terms = $model->terms('article_topic');
        $comments = $model->comments();

        self::assertCount(1, $terms);
        self::assertInstanceOf(WP_Term::class, $terms[0]);
        self::assertCount(1, $comments);
        self::assertInstanceOf(WP_Comment::class, $comments[0]);
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
