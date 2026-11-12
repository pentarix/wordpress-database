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

    public function testMetadataCanRetrieveAndUpdateMetadataById(): void
    {
        $postId = self::factory()->post->create();
        $metaId = add_post_meta($postId, 'pentarix_mid_key', 'before', true);

        $meta = Metadata::getById('post', $metaId);

        self::assertIsObject($meta);
        self::assertSame('before', $meta->meta_value);
        self::assertTrue(Metadata::updateById('post', $metaId, 'after'));
        self::assertSame('after', get_post_meta($postId, 'pentarix_mid_key', true));
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
