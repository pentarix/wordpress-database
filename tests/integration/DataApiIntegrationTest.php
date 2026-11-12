<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Metadata;
use Pentarix\WordPressDatabase\WordPress\Option;
use Pentarix\WordPressDatabase\WordPress\Transient;

final class DataApiIntegrationTest extends WP_UnitTestCase
{
    public function testOptionCrudPreservesNativeWordPressBehaviour(): void
    {
        $name = 'pentarix_option_' . wp_generate_uuid4();

        self::assertFalse(Option::exists($name));
        self::assertTrue(Option::add($name, ['enabled' => true], false));
        self::assertTrue(Option::exists($name));
        self::assertSame(['enabled' => true], Option::get($name));
        self::assertFalse(Option::add($name, 'duplicate'));

        Option::update($name, 'updated');
        self::assertSame('updated', Option::get($name));
        self::assertTrue(Option::delete($name));
        self::assertFalse(Option::exists($name));
        self::assertSame('fallback', Option::get($name, 'fallback'));
    }

    public function testOptionExistsHandlesFalseValues(): void
    {
        $name = 'pentarix_false_option_' . wp_generate_uuid4();

        add_option($name, false);

        self::assertTrue(Option::exists($name));
        self::assertFalse(Option::get($name));

        delete_option($name);
    }

    public function testNetworkOptionCrudPreservesNativeWordPressBehaviour(): void
    {
        $name = 'pentarix_network_option_' . wp_generate_uuid4();

        self::assertFalse(Option::existsSite($name));
        self::assertTrue(Option::addSite($name, ['enabled' => true]));
        self::assertTrue(Option::existsSite($name));
        self::assertSame(['enabled' => true], Option::getSite($name));
        self::assertFalse(Option::addSite($name, 'duplicate'));

        self::assertTrue(Option::updateSite($name, 'updated'));
        self::assertSame('updated', Option::getSite($name));
        self::assertTrue(Option::deleteSite($name));
        self::assertFalse(Option::existsSite($name));
        self::assertSame('fallback', Option::getSite($name, 'fallback'));
    }

    public function testNetworkOptionExistsHandlesFalseValues(): void
    {
        $name = 'pentarix_false_network_option_' . wp_generate_uuid4();

        add_site_option($name, false);

        self::assertTrue(Option::existsSite($name));
        self::assertSame(get_site_option($name), Option::getSite($name));

        delete_site_option($name);
    }

    public function testBlogOptionCrudPreservesNativeWordPressBehaviour(): void
    {
        $blogId = self::factory()->blog->create();
        $name = 'pentarix_blog_option_' . wp_generate_uuid4();

        self::assertFalse(Option::existsBlog($blogId, $name));
        self::assertTrue(Option::addBlog($blogId, $name, ['enabled' => true]));
        self::assertTrue(Option::existsBlog($blogId, $name));
        self::assertSame(['enabled' => true], Option::getBlog($blogId, $name));
        self::assertFalse(Option::addBlog($blogId, $name, 'duplicate'));

        self::assertTrue(Option::updateBlog($blogId, $name, 'updated'));
        self::assertSame('updated', Option::getBlog($blogId, $name));
        self::assertTrue(Option::deleteBlog($blogId, $name));
        self::assertFalse(Option::existsBlog($blogId, $name));
        self::assertSame('fallback', Option::getBlog($blogId, $name, 'fallback'));
    }

    public function testBlogOptionExistsHandlesFalseValues(): void
    {
        $blogId = self::factory()->blog->create();
        $name = 'pentarix_false_blog_option_' . wp_generate_uuid4();

        add_blog_option($blogId, $name, false);

        self::assertTrue(Option::existsBlog($blogId, $name));
        self::assertFalse(Option::getBlog($blogId, $name));

        delete_blog_option($blogId, $name);
    }

    public function testMetadataCrudSupportsPostMetadata(): void
    {
        $postId = self::factory()->post->create();
        $metaKey = 'pentarix_meta_' . wp_generate_uuid4();

        self::assertFalse(Metadata::exists('post', $postId, $metaKey));
        self::assertNotFalse(Metadata::add('post', $postId, $metaKey, ['one', 'two'], true));
        self::assertTrue(Metadata::exists('post', $postId, $metaKey));
        self::assertSame(['one', 'two'], Metadata::get('post', $postId, $metaKey, true));
        self::assertFalse(Metadata::add('post', $postId, $metaKey, 'duplicate', true));

        Metadata::update('post', $postId, $metaKey, 'updated');
        self::assertSame('updated', Metadata::get('post', $postId, $metaKey, true));
        self::assertTrue(Metadata::delete('post', $postId, $metaKey));
        self::assertFalse(Metadata::exists('post', $postId, $metaKey));
    }

    public function testMetadataSupportsUserAndCommentObjectTypes(): void
    {
        $userId = self::factory()->user->create();
        $commentId = self::factory()->comment->create();

        Metadata::add('user', $userId, 'pentarix_user_key', 'user-value');
        Metadata::add('comment', $commentId, 'pentarix_comment_key', 'comment-value');

        self::assertSame('user-value', Metadata::get('user', $userId, 'pentarix_user_key', true));
        self::assertSame('comment-value', Metadata::get('comment', $commentId, 'pentarix_comment_key', true));
    }

    public function testMetadataCanRegisterAndUnregisterObjectTypeMetadata(): void
    {
        $metaKey = 'pentarix_registered_meta_' . wp_generate_uuid4();

        self::assertTrue(Metadata::register('post', $metaKey, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ], 'post'));

        self::assertTrue(registered_meta_key_exists('post', $metaKey, 'post'));
        self::assertFalse(registered_meta_key_exists('post', $metaKey));
        self::assertTrue(Metadata::unregister('post', $metaKey, 'post'));
        self::assertFalse(registered_meta_key_exists('post', $metaKey, 'post'));
    }

    public function testMetadataSupportsTermObjectTypes(): void
    {
        $termId = self::factory()->term->create();

        Metadata::add('term', $termId, 'pentarix_term_key', 'term-value');

        self::assertSame('term-value', Metadata::get('term', $termId, 'pentarix_term_key', true));
        self::assertTrue(Metadata::exists('term', $termId, 'pentarix_term_key'));
    }

    public function testMetadataCanRetrieveAndUpdateMetadataById(): void
    {
        $postId = self::factory()->post->create();
        $metaId = add_post_meta($postId, 'pentarix_mid_key', 'before', true);

        $meta = Metadata::getById('post', $metaId);

        self::assertIsObject($meta);
        self::assertSame('before', $meta->meta_value);
        self::assertTrue(Metadata::updateById('post', $metaId, 'after'));
        self::assertSame('after', get_post_meta($postId, 'pentarix_mid_key', true));
        self::assertTrue(Metadata::updateById('post', $metaId, 'renamed', 'pentarix_renamed_mid_key'));
        self::assertFalse(metadata_exists('post', $postId, 'pentarix_mid_key'));
        self::assertSame('renamed', get_post_meta($postId, 'pentarix_renamed_mid_key', true));
        self::assertTrue(Metadata::deleteById('post', $metaId));
    }

    public function testTransientCrudPreservesNativeWordPressBehaviour(): void
    {
        $name = 'pentarix_transient_' . wp_generate_uuid4();

        self::assertTrue(Transient::set($name, ['cached' => true], 300));
        self::assertSame(['cached' => true], Transient::get($name));
        self::assertTrue(Transient::delete($name));
        self::assertFalse(Transient::get($name));
    }

    public function testSiteTransientCrudPreservesNativeWordPressBehaviour(): void
    {
        $name = 'pentarix_site_transient_' . wp_generate_uuid4();

        self::assertTrue(Transient::setSite($name, 'network-cache', 300));
        self::assertSame('network-cache', Transient::getSite($name));
        self::assertTrue(Transient::deleteSite($name));
        self::assertFalse(Transient::getSite($name));
    }
}
