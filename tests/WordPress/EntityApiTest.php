<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Comment;
use Pentarix\WordPressDatabase\WordPress\Post;
use Pentarix\WordPressDatabase\WordPress\Term;
use Pentarix\WordPressDatabase\WordPress\User;
use PHPUnit\Framework\TestCase;

final class EntityApiTest extends TestCase
{
    public function testPostRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_insert_post')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Post::create([]);
    }

    public function testUserRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_insert_user')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        User::create([]);
    }

    public function testTermRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_insert_term')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Term::create('Example', 'category');
    }

    public function testAttachmentRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_insert_attachment')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Attachment::get(1);
    }

    public function testRevisionRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_get_post_revisions')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Revision::all(1);
    }

    public function testSiteRequiresWordPressMultisiteToBeLoaded(): void
    {
        if (function_exists('wp_insert_site')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Site::get(1);
    }

    public function testNetworkRequiresWordPressMultisiteToBeLoaded(): void
    {
        if (function_exists('get_network')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Network::get(1);
    }

    public function testCommentRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_insert_comment')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Comment::create([]);
    }
}
