<?php

declare(strict_types=1);

use Pentarix\WordPressDatabase\WordPress\CustomTable;

final class CustomTableIntegrationTest extends WP_UnitTestCase
{
    public function testCustomTableSupportsCrudAndPreparedQueries(): void
    {
        global $wpdb;

        $table = CustomTable::forSite('pentarix_custom_table_test');
        $tableName = $table->getTableName();

        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
        $wpdb->query(
            "CREATE TABLE {$tableName} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(191) NOT NULL,
                quantity int unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY  (id)
            ) {$wpdb->get_charset_collate()}"
        );

        try {
            $id = $table->insert([
                'title' => 'First item',
                'quantity' => 3,
            ], ['%s', '%d']);

            self::assertIsInt($id);
            self::assertGreaterThan(0, $id);

            $row = $table->getRow(
                'SELECT * FROM %i WHERE %i = %d',
                [$tableName, 'id', $id]
            );

            self::assertIsObject($row);
            self::assertSame('First item', $row->title);
            self::assertSame('3', $row->quantity);

            $updated = $table->update(
                ['title' => 'Updated item', 'quantity' => 8],
                ['id' => $id],
                ['%s', '%d'],
                ['%d']
            );

            self::assertSame(1, $updated);
            self::assertSame(
                'Updated item',
                $table->getVar('SELECT %i FROM %i WHERE %i = %d', ['title', $tableName, 'id', $id])
            );

            self::assertSame(1, $table->execute(
                'UPDATE %i SET %i = %d WHERE %i = %d',
                [$tableName, 'quantity', 10, 'id', $id]
            ));

            $rows = $table->getResults('SELECT * FROM %i ORDER BY %i ASC', [$tableName, 'id']);

            self::assertIsArray($rows);
            self::assertCount(1, $rows);
            self::assertSame('10', $rows[0]->quantity);

            self::assertSame(1, $table->delete(['id' => $id], ['%d']));
            self::assertNull($table->getRow(
                'SELECT * FROM %i WHERE %i = %d',
                [$tableName, 'id', $id]
            ));
            self::assertSame('0', $table->getVar('SELECT COUNT(*) FROM %i', [$tableName]));
        } finally {
            $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
        }
    }

    public function testCustomTableUsesSiteAndNetworkPrefixes(): void
    {
        global $wpdb;

        self::assertSame(
            $wpdb->prefix . 'pentarix_scope_test',
            CustomTable::forSite('pentarix_scope_test')->getTableName()
        );
        self::assertSame(
            $wpdb->base_prefix . 'pentarix_scope_test',
            CustomTable::forNetwork('pentarix_scope_test')->getTableName()
        );
    }

    public function testCustomTableRejectsUnsafeTableSuffixes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CustomTable::forSite('inventory; DROP TABLE wp_posts');
    }

    public function testPrepareUsesWordPressPlaceholders(): void
    {
        $table = CustomTable::forSite('pentarix_prepare_test');
        $prepared = $table->prepare('SELECT %s, %d', ['prepared value', 42]);

        self::assertStringContainsString("'prepared value'", $prepared);
        self::assertStringContainsString('42', $prepared);
    }

    public function testPrepareSupportsNumberedAndFormattedPlaceholders(): void
    {
        $table = CustomTable::forSite('pentarix_prepare_test');
        $prepared = $table->prepare(
            'SELECT %1$s, %2$d, %05d, %\'#5s',
            ['numbered value', 42, 7, 'pad']
        );

        self::assertStringContainsString("'numbered value'", $prepared);
        self::assertStringContainsString('42', $prepared);
        self::assertStringContainsString('00007', $prepared);
        self::assertStringContainsString('##pad', $prepared);
    }

    public function testPrepareIgnoresEscapedPlaceholdersAndLiteralPercentages(): void
    {
        $table = CustomTable::forSite('pentarix_prepare_test');

        self::assertSame('SELECT %%s, 100%', $table->prepare('SELECT %%s, 100%'));
        self::assertSame('SELECT %s', $table->prepare('SELECT %s', ['value']));
    }

    public function testPrepareRequiresArgumentsForPlaceholders(): void
    {
        $table = CustomTable::forSite('pentarix_prepare_test');

        $this->expectException(InvalidArgumentException::class);

        $table->prepare('SELECT %d');
    }

    public function testPrepareRejectsArgumentsWithoutPlaceholders(): void
    {
        $table = CustomTable::forSite('pentarix_prepare_test');

        $this->expectException(InvalidArgumentException::class);

        $table->prepare('SELECT 1', ['unused']);
    }}
