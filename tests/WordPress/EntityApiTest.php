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

    public function testCommentRequiresWordPressToBeLoaded(): void
    {
        if (function_exists('wp_insert_comment')) {
            self::markTestSkipped('WordPress is already loaded.');
        }

        $this->expectException(LogicException::class);
        Comment::create([]);
    }
}
