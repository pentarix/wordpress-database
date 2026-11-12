<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Attachment;
use Pentarix\WordPressDatabase\WordPress\Comment;
use Pentarix\WordPressDatabase\WordPress\Link;
use Pentarix\WordPressDatabase\WordPress\Network;
use Pentarix\WordPressDatabase\WordPress\Post;
use Pentarix\WordPressDatabase\WordPress\Revision;
use Pentarix\WordPressDatabase\WordPress\Site;
use Pentarix\WordPressDatabase\WordPress\Term;
use Pentarix\WordPressDatabase\WordPress\User;

final class EntityConvenienceApiIntegrationTest extends WP_UnitTestCase
{
    public function testPostConvenienceMethodsDelegateToCore(): void
    {
        $parentId = self::factory()->post->create([
            'post_title' => 'Parent title',
            'post_content' => 'Parent content',
            'post_excerpt' => 'Parent excerpt',
            'post_status' => 'publish',
        ]);
        $childId = self::factory()->post->create([
            'post_title' => 'Child title',
            'post_parent' => $parentId,
        ]);

        self::assertSame('Parent title', Post::getTitle($parentId));
        self::assertSame('Parent content', Post::getContent($parentId));
        self::assertSame('Parent excerpt', Post::getField('post_excerpt', $parentId));
        self::assertSame('publish', Post::getStatus($parentId));
        self::assertSame('post', Post::getType($parentId));
        self::assertSame($parentId, Post::getParentId($childId));
        self::assertSame([$parentId], Post::getAncestors($childId));
        self::assertArrayHasKey($childId, Post::getChildren($parentId));
        self::assertNotEmpty(Post::getPermalink($parentId));
    }

    public function testUserConvenienceMethodsDelegateToCore(): void
    {
        $userId = self::factory()->user->create(['role' => 'subscriber']);
        $user = User::get($userId);

        self::assertTrue(User::exists($userId));
        self::assertTrue(User::exists($user->user_login));
        self::assertSame($userId, User::findByEmail($user->user_email)->ID);
        self::assertContains('subscriber', User::getRoles($userId));

        User::setRole($userId, 'editor');
        User::addRole($userId, 'author');
        self::assertContains('editor', User::getRoles($userId));
        self::assertContains('author', User::getRoles($userId));
        self::assertTrue(User::can($userId, 'edit_posts'));

        User::removeRole($userId, 'author');
        self::assertNotContains('author', User::getRoles($userId));

        User::setPassword('pentarix-test-password', $userId);
        self::assertTrue(User::checkPassword('pentarix-test-password', $userId));
        self::assertFalse(User::checkPassword('incorrect-password', $userId));
    }

    public function testTermHierarchyAndRelationshipHelpersDelegateToCore(): void
    {
        $parent = Term::create('Parent ' . wp_generate_uuid4(), 'category');
        $child = Term::create('Child ' . wp_generate_uuid4(), 'category', ['parent' => $parent['term_id']]);
        $postId = self::factory()->post->create();

        Term::assignToObject($postId, [(int) $child['term_id']], 'category');

        self::assertContains((int) $child['term_id'], Term::getChildren((int) $parent['term_id'], 'category'));
        self::assertSame([(int) $parent['term_id']], Term::getAncestors((int) $child['term_id'], 'category'));
        self::assertContains($postId, Term::getObjectIds((int) $child['term_id'], 'category'));
        self::assertInstanceOf(WP_Taxonomy::class, Term::getTaxonomy('category'));
        self::assertIsNumeric(Term::count('category', ['hide_empty' => false]));
        self::assertIsString(Term::getLink((int) $child['term_id'], 'category'));
    }

    public function testCommentModerationAndReplyHelpersDelegateToCore(): void
    {
        $postId = self::factory()->post->create();
        $commentId = self::factory()->comment->create([
            'comment_post_ID' => $postId,
            'comment_approved' => 1,
        ]);

        $replyId = Comment::reply($commentId, [
            'comment_author' => 'Reply author',
            'comment_content' => 'Reply content',
        ], true);

        self::assertIsInt($replyId);
        self::assertCount(1, Comment::getChildren($commentId));
        self::assertTrue(Comment::hold($replyId));
        self::assertTrue(Comment::approve($replyId));
        self::assertTrue(Comment::spam($replyId));
        self::assertTrue(Comment::unspam($replyId));
        self::assertTrue(Comment::trash($replyId));
        self::assertTrue(Comment::restore($replyId));
        self::assertArrayHasKey('approved', Comment::countForPost($postId));
    }

    public function testAttachmentMediaHelpersDelegateToCore(): void
    {
        $attachmentId = self::factory()->attachment->create_upload_object(
            DIR_TESTDATA . '/images/canola.jpg'
        );

        self::assertTrue(Attachment::isImage($attachmentId));
        self::assertSame('image/jpeg', Attachment::getMimeType($attachmentId));
        self::assertSame(0, Attachment::getParentId($attachmentId));
        self::assertTrue(Attachment::updateAlt($attachmentId, 'A field of canola'));
        self::assertSame('A field of canola', Attachment::getAlt($attachmentId));
        self::assertIsString(Attachment::getCaption($attachmentId));
        self::assertIsString(Attachment::getDescription($attachmentId));
        self::assertStringContainsString('<img', Attachment::getImage($attachmentId));
        self::assertIsString(Attachment::getIcon($attachmentId));
    }

    public function testRevisionHelpersRetrieveLatestAndAutosave(): void
    {
        $postId = self::factory()->post->create(['post_title' => 'Revision helper test']);
        Revision::save($postId);

        self::assertInstanceOf(WP_Post::class, Revision::getLatest($postId));
        self::assertFalse(Revision::getAutosave($postId));
    }

    public function testSiteContextAndOptionsHelpersDelegateToCore(): void
    {
        $siteId = get_current_blog_id();

        self::assertSame($siteId, Site::getCurrentId());
        self::assertInstanceOf(WP_Site::class, Site::getCurrent());
        self::assertNotEmpty(Site::getHomeUrl($siteId));
        self::assertNotEmpty(Site::getSiteUrl($siteId));
        self::assertNotEmpty(Site::getAdminUrl($siteId));

        self::assertTrue(Site::updateOption($siteId, 'pentarix_entity_test', 'value'));
        self::assertSame('value', Site::getOption($siteId, 'pentarix_entity_test'));
        self::assertTrue(Site::deleteOption($siteId, 'pentarix_entity_test'));

        self::assertTrue(Site::switchTo($siteId));
        self::assertTrue(Site::isSwitched());
        self::assertTrue(Site::restoreCurrent());
        self::assertFalse(Site::isSwitched());
    }

    public function testNetworkContextAndOptionsHelpersDelegateToCore(): void
    {
        if (!is_multisite()) {
            self::markTestSkipped('Network helpers require WordPress Multisite.');
        }

        self::assertSame(get_current_network_id(), Network::getCurrentId());
        self::assertInstanceOf(WP_Network::class, Network::getCurrent());

        $option = 'pentarix_entity_test_' . wp_generate_uuid4();
        self::assertTrue(Network::addOption($option, 'value'));
        self::assertSame('value', Network::getOption($option));
        self::assertTrue(Network::updateOption($option, 'updated'));
        self::assertSame('updated', Network::getOption($option));
        self::assertTrue(Network::deleteOption($option));
    }

    public function testLegacyLinkCategoryHelpersDelegateToCore(): void
    {
        if (!function_exists('wp_insert_link')) {
            require_once ABSPATH . 'wp-admin/includes/bookmark.php';
        }

        $category = self::factory()->term->create(['taxonomy' => 'link_category']);
        $linkId = Link::create([
            'link_name' => 'Pentarix category test',
            'link_url' => 'https://example.test',
        ]);

        Link::setCategories($linkId, [$category]);
        self::assertContains($category, Link::getCategoryIds($linkId));
        self::assertIsString(Link::getCategoryName($linkId));
    }
}
