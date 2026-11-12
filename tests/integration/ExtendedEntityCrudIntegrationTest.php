<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Attachment;
use Pentarix\WordPressDatabase\WordPress\Network;
use Pentarix\WordPressDatabase\WordPress\Revision;
use Pentarix\WordPressDatabase\WordPress\Site;

final class ExtendedEntityCrudIntegrationTest extends WP_UnitTestCase
{
    public function testAttachmentApiSupportsNativeAttachmentOperations(): void
    {
        $attachmentId = self::factory()->attachment->create_upload_object(
            DIR_TESTDATA . '/images/canola.jpg'
        );

        self::assertInstanceOf(WP_Post::class, Attachment::get($attachmentId));
        self::assertSame($attachmentId, Attachment::get($attachmentId)->ID);
        self::assertIsString(Attachment::getUrl($attachmentId));
        self::assertIsString(Attachment::getFile($attachmentId));
        self::assertIsArray(Attachment::getMetadata($attachmentId));
        self::assertSame(
            $attachmentId,
            Attachment::update([
                'ID' => $attachmentId,
                'post_title' => 'Updated attachment title',
            ])
        );
        self::assertSame('Updated attachment title', Attachment::get($attachmentId)->post_title);

        self::assertInstanceOf(WP_Post::class, Attachment::delete($attachmentId, true));
        self::assertNull(Attachment::get($attachmentId));
    }

    public function testRevisionApiCanReadRestoreAndDeleteRevisions(): void
    {
        $postId = self::factory()->post->create([
            'post_title' => 'Revision original',
            'post_content' => 'Original content',
        ]);

        wp_update_post([
            'ID' => $postId,
            'post_title' => 'Revision changed',
            'post_content' => 'Changed content',
        ]);

        $revisions = Revision::all($postId);

        self::assertNotEmpty($revisions);

        $revisionId = (int) array_key_first($revisions);
        self::assertInstanceOf(WP_Post::class, Revision::get($revisionId));
        self::assertSame($postId, Revision::restore($revisionId));
        self::assertTrue((bool) Revision::isRevision($revisionId));
        self::assertNotNull(Revision::getUrl($postId));
        self::assertInstanceOf(WP_Post::class, Revision::delete($revisionId));
    }

    public function testSiteCrudUsesNativeMultisiteFunctions(): void
    {
        if (!is_multisite()) {
            self::markTestSkipped('Site CRUD requires WordPress Multisite.');
        }

        $siteId = Site::create([
            'domain' => 'pentarix-' . strtolower(wp_generate_password(8, false)) . '.example.test',
            'path' => '/',
            'network_id' => get_current_network_id(),
            'title' => 'Pentarix test site',
        ]);

        self::assertIsInt($siteId);
        self::assertInstanceOf(WP_Site::class, Site::get($siteId));

        self::assertSame($siteId, Site::update($siteId, ['public' => 0]));
        self::assertSame(0, (int) Site::get($siteId)->public);

        self::assertInstanceOf(WP_Site::class, Site::delete($siteId));
        self::assertNull(Site::get($siteId));
    }

    public function testNetworkApiRetrievesCurrentNetwork(): void
    {
        if (!is_multisite()) {
            self::markTestSkipped('Network retrieval requires WordPress Multisite.');
        }

        self::assertInstanceOf(WP_Network::class, Network::get(get_current_network_id()));
    }
}
