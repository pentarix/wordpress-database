<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\Menu;
use Pentarix\WordPressDatabase\WordPress\MenuItem;

final class NavigationMenuIntegrationTest extends WP_UnitTestCase
{
    public function testMenuCrudUsesNativeNavigationMenuApis(): void
    {
        $name = 'Pentarix menu ' . wp_generate_uuid4() . " with O'Reilly";
        $menuId = Menu::create($name);

        self::assertIsInt($menuId);

        $menu = Menu::get($menuId);

        self::assertInstanceOf(WP_Term::class, $menu);
        self::assertSame('nav_menu', $menu->taxonomy);
        self::assertSame($name, $menu->name);

        $updatedName = $name . ' Updated';
        $updated = Menu::update($menuId, ['name' => $updatedName]);

        self::assertSame($menuId, $updated);
        self::assertSame($updatedName, Menu::get($menuId)->name);
        self::assertContains($menuId, array_map(
            static fn (WP_Term $term): int => (int) $term->term_id,
            Menu::all()
        ));

        self::assertTrue(Menu::delete($menuId));
        self::assertFalse(Menu::get($menuId));
    }

    public function testMenuUpdateFiresNativeHookAndRejectsDuplicateNames(): void
    {
        $name = 'Pentarix hook menu ' . wp_generate_uuid4();
        $menuId = Menu::create($name);
        $duplicateId = Menu::create($name . ' duplicate');

        self::assertIsInt($menuId);
        self::assertIsInt($duplicateId);

        $hookArguments = null;
        $callback = static function (int $updatedMenuId, array $menuData) use (&$hookArguments): void {
            $hookArguments = [$updatedMenuId, $menuData];
        };

        add_action('wp_update_nav_menu', $callback, 10, 2);

        try {
            $updatedName = $name . ' updated';
            self::assertSame($menuId, Menu::update($menuId, ['menu-name' => $updatedName]));
            self::assertSame($menuId, $hookArguments[0]);
            self::assertSame($updatedName, $hookArguments[1]['menu-name']);

            $duplicate = Menu::update($menuId, ['name' => Menu::get($duplicateId)->name]);

            self::assertWPError($duplicate);
            self::assertSame('menu_exists', $duplicate->get_error_code());
        } finally {
            remove_action('wp_update_nav_menu', $callback, 10);
            Menu::delete($menuId);
            Menu::delete($duplicateId);
        }
    }

    public function testMenuItemsCanBeCreatedRetrievedUpdatedAndDeleted(): void
    {
        $menuId = Menu::create('Pentarix item menu ' . wp_generate_uuid4());

        self::assertIsInt($menuId);

        $itemTitle = "Original item's \"Books\"";
        $itemId = MenuItem::create($menuId, [
            'menu-item-title' => $itemTitle,
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
        self::assertSame($itemTitle, $items[0]->title);
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
