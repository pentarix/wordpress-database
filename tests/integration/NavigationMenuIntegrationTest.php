<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Menu;
use Pentarix\WordPressDatabase\WordPress\MenuItem;

final class NavigationMenuIntegrationTest extends WP_UnitTestCase
{
    public function testMenuCrudUsesNativeNavigationMenuApis(): void
    {
        $name = 'Pentarix menu ' . wp_generate_uuid4();
        $menuId = Menu::create($name);

        self::assertIsInt($menuId);

        $menu = Menu::get($menuId);

        self::assertInstanceOf(WP_Term::class, $menu);
        self::assertSame('nav_menu', $menu->taxonomy);
        self::assertSame($name, $menu->name);

        $updatedName = $name . ' Updated';
        $updated = Menu::update($menuId, ['name' => $updatedName]);

        self::assertIsArray($updated);
        self::assertSame($updatedName, Menu::get($menuId)->name);
        self::assertContains($menuId, array_map(
            static fn (WP_Term $term): int => (int) $term->term_id,
            Menu::all()
        ));

        self::assertIsArray(Menu::delete($menuId));
        self::assertFalse(Menu::get($menuId));
    }

    public function testMenuItemsCanBeCreatedRetrievedUpdatedAndDeleted(): void
    {
        $menuId = Menu::create('Pentarix item menu ' . wp_generate_uuid4());

        self::assertIsInt($menuId);

        $itemId = MenuItem::create($menuId, [
            'menu-item-title' => 'Original item',
            'menu-item-type' => 'custom',
            'menu-item-url' => 'https://example.test/original',
            'menu-item-status' => 'publish',
        ]);

        self::assertIsInt($itemId);
        self::assertInstanceOf(WP_Post::class, MenuItem::get($itemId));
        self::assertSame('nav_menu_item', MenuItem::get($itemId)->post_type);

        $items = Menu::items($menuId);

        self::assertIsArray($items);
        self::assertCount(1, $items);
        self::assertSame($itemId, (int) $items[0]->db_id);
        self::assertSame('Original item', $items[0]->title);
        self::assertSame('https://example.test/original', $items[0]->url);

        $updatedId = MenuItem::update($menuId, $itemId, [
            'menu-item-title' => 'Updated item',
            'menu-item-url' => 'https://example.test/updated',
            'menu-item-status' => 'publish',
        ]);

        self::assertSame($itemId, $updatedId);

        $updatedItems = MenuItem::getForMenu($menuId);

        self::assertSame('Updated item', $updatedItems[0]->title);
        self::assertSame('https://example.test/updated', $updatedItems[0]->url);

        self::assertInstanceOf(WP_Post::class, MenuItem::delete($itemId));
        self::assertNull(MenuItem::get($itemId));
        self::assertFalse(MenuItem::delete($itemId));
    }

    public function testMenuItemsSupportParentAndChildRelationships(): void
    {
        $menuId = Menu::create('Pentarix hierarchy menu ' . wp_generate_uuid4());

        $parentId = MenuItem::create($menuId, [
            'menu-item-title' => 'Parent item',
            'menu-item-type' => 'custom',
            'menu-item-url' => 'https://example.test/parent',
            'menu-item-status' => 'publish',
        ]);
        $childId = MenuItem::create($menuId, [
            'menu-item-title' => 'Child item',
            'menu-item-type' => 'custom',
            'menu-item-url' => 'https://example.test/child',
            'menu-item-parent-id' => $parentId,
            'menu-item-status' => 'publish',
        ]);

        self::assertIsInt($parentId);
        self::assertIsInt($childId);

        $items = Menu::items($menuId);

        self::assertCount(2, $items);

        $child = array_values(array_filter(
            $items,
            static fn (object $item): bool => (int) $item->db_id === $childId
        ))[0];

        self::assertSame($parentId, (int) $child->menu_item_parent);
    }

    public function testMenuItemGetDoesNotReturnOtherPostTypes(): void
    {
        $postId = self::factory()->post->create();

        self::assertNull(MenuItem::get($postId));
        self::assertFalse(MenuItem::delete($postId));
        self::assertNull(MenuItem::get(999999));
    }
}
