<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Link;

final class LinkEntityIntegrationTest extends WP_UnitTestCase
{
    public function testLegacyLinkCrudAndQueryUseNativeWordPressFunctions(): void
    {
        require_once ABSPATH . 'wp-admin/includes/bookmark.php';

        $linkId = Link::create([
            'link_name' => 'Pentarix original link',
            'link_url' => 'https://example.test/original',
        ]);

        self::assertIsInt($linkId);
        self::assertSame('Pentarix original link', Link::get($linkId)->link_name);

        self::assertSame($linkId, Link::update([
            'link_id' => $linkId,
            'link_name' => 'Pentarix updated link',
            'link_url' => 'https://example.test/updated',
        ]));

        self::assertSame('Pentarix updated link', Link::get($linkId)->link_name);
        self::assertCount(1, Link::query(['include' => [$linkId]]));
        self::assertTrue(Link::delete($linkId));
        self::assertNull(Link::get($linkId));
    }
}
