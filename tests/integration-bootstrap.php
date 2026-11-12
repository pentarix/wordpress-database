<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$testsDirectory = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';

if (!is_file($testsDirectory . '/includes/functions.php')) {
    throw new RuntimeException(
        'WordPress test suite not found. Set WP_TESTS_DIR or run the test environment installer.'
    );
}

if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define(
        'WP_TESTS_PHPUNIT_POLYFILLS_PATH',
        dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills'
    );
}

require_once $testsDirectory . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
});

require $testsDirectory . '/includes/bootstrap.php';
