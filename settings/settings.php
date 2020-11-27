<?php

/**
 * Skip file system permissions hardening.
 *
 * Drupal composer project conflicts with the permission hardening.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Security.
 */
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');
$settings['update_free_access'] = FALSE;
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^httpd',
  '^' . getenv('DRUPAL_HOST_PATTERN') . '$',
  '^.*\.' . getenv('DRUPAL_HOST_PATTERN') . '$',
];

/**
 * Config.
 */
$settings['config_sync_directory'] = '../config/sync';
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.cookie.yml';

/**
 * Entities.
 */
$settings['entity_update_batch_size'] = getenv('DRUPAL_BATCH_SIZE');
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * Database.
 */
$databases['default']['default'] = [
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASSWORD'),
  'prefix' => '',
  'host' => getenv('DB_HOST'),
  'port' => getenv('DB_PORT'),
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => getenv('DB_DRIVER'),
];

/**
 * Custom config.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.custom.php')) {
  include $app_root . '/' . $site_path . '/settings.custom.php';
}

/**
 * Dev config.
 */
if (getenv('DEV_MODE') && file_exists($app_root . '/' . $site_path . '/settings.dev.php')) {
  include $app_root . '/' . $site_path . '/settings.dev.php';
}

/**
 * Local config.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
