<?php
if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    $prefix = 'SchemaWeave\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    // Git source checkout: SchemaWeave-PHP is mounted as a submodule and
    // keeps its PSR-4 classes under src/.
    $sourceFile = SCHEMAWEAVE_DIR . 'includes/SchemaWeave/src/' . $relative;
    if (is_file($sourceFile)) {
        require_once $sourceFile;
        return;
    }

    // WordPress.org / installable ZIP: the release builder may bundle the
    // core classes directly under includes/SchemaWeave/ for a self-contained
    // plugin that requires neither Composer nor git submodules.
    $bundledFile = SCHEMAWEAVE_DIR . 'includes/SchemaWeave/' . $relative;
    if (is_file($bundledFile)) {
        require_once $bundledFile;
    }
});
