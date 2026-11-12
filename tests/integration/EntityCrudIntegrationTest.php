<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Comment;
use Pentarix\WordPressDatabase\WordPress\Post;
use Pentarix\WordPressDatabase\WordPress\Term;
use Pentarix\WordPressDatabase\WordPress\User;

final class EntityCrudIntegrationTest extends WP_UnitTestCase
{
    public function testPostCrudSupportsCustomPostTypes(): void
    {
        register_post_type('pentarix_item', ['public' => true]);

        $postId = Post::create([
            'post_type' => 'pentarix_item',
            'post_status' => 'publish',
            'post_title' => 'Original item',
        ], true);

        self::assertIsInt($postId);
        self::assertSame('pentarix_item', Post::get($postId)->post_type);

        self::assertSame($postId, Post::update([
            'ID' => $postId,
            'post_title' => 'Updated item',
        ], true));

        self::assertSame('Updated item', Post::get($postId)->post_title);
        self::assertInstanceOf(WP_Post::class, Post::delete($postId, true));
        self::assertNull(Post::get($postId));

        unregister_post_type('pentarix_item');
    }

    public function testUserCrudPreservesNativeWordPressResults(): void
    {
        $login = 'pentarix_' . strtolower(wp_generate_password(12, false));
        $userId = User::create([
            'user_login' => $login,
            'user_pass' => wp_generate_password(),
            'user_email' => $login . '@example.test',
        ]);

        self::assertIsInt($userId);
        self::assertInstanceOf(WP_User::class, User::get($userId));
        self::assertSame($userId, User::findBy('login', $login)->ID);

        self::assertSame($userId, User::update([
            'ID' => $userId,
            'display_name' => 'Updated display name',
        ]));
        self::assertSame('Updated display name', User::get($userId)->display_name);

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $blogId = get_current_blog_id();

        self::assertTrue(User::delete($userId));

        if (is_multisite()) {
            self::assertFalse(is_user_member_of_blog($userId, $blogId));
        } else {
            self::assertFalse(User::get($userId));
        }
    }

    public function testTermCrudPreservesNativeWordPressResults(): void
    {
        $name = 'Pentarix ' . wp_generate_uuid4();
        $created = Term::create($name, 'category', ['slug' => sanitize_title($name)]);

        self::assertIsArray($created);
        $termId = (int) $created['term_id'];

        self::assertSame($name, Term::get($termId, 'category')->name);
        self::assertSame($termId, (int) Term::findBy('id', $termId, 'category')->term_id);

        $updated = Term::update($termId, 'category', ['name' => $name . ' Updated']);

        self::assertIsArray($updated);
        self::assertSame($name . ' Updated', Term::get($termId, 'category')->name);
        $postId = self::factory()->post->create();
        self::assertIsArray(Term::assignToObject($postId, [$termId], 'category'));
        self::assertSame($termId, (int) Term::getForObject($postId, 'category')[0]->term_id);
        self::assertNotFalse(Term::exists($termId, 'category'));
        self::assertTrue(Term::removeFromObject($postId, [$termId], 'category'));
        self::assertTrue(Term::delete($termId, 'category'));
        self::assertNull(Term::get($termId, 'category'));
    }

    public function testCommentCrudPreservesNativeWordPressResults(): void
    {
        $postId = self::factory()->post->create();
        $commentId = Comment::create([
            'comment_post_ID' => $postId,
            'comment_author' => 'Pentarix',
            'comment_author_email' => 'comment@example.test',
            'comment_content' => 'Original comment',
            'comment_approved' => 1,
        ]);

        self::assertIsInt($commentId);
        self::assertInstanceOf(WP_Comment::class, Comment::get($commentId));

        $updated = Comment::update([
            'comment_ID' => $commentId,
            'comment_content' => 'Updated comment',
        ], true);

        self::assertNotInstanceOf(WP_Error::class, $updated);
        self::assertSame('Updated comment', Comment::get($commentId)->comment_content);
        self::assertTrue(Comment::delete($commentId, true));
        self::assertNull(Comment::get($commentId));
    }
}
