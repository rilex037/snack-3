#!/usr/bin/env php
<?php

declare(strict_types=1);

$root  = dirname(__DIR__);
$build = $root . '/build';

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

function rcopy(string $src, string $dst): void
{
    if (is_dir($src)) {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            rcopy($src . '/' . $item, $dst . '/' . $item);
        }
        return;
    }

    $dstDir = dirname($dst);
    if (!is_dir($dstDir)) {
        mkdir($dstDir, 0755, true);
    }
    copy($src, $dst);
}

echo "Cleaning build/...\n";
rrmdir($build);
mkdir($build, 0755, true);

echo "Copying framework + app code...\n";
rcopy("$root/snack", "$build/snack");
rcopy("$root/app", "$build/app");

echo "Copying migrations...\n";
rcopy("$root/database/migrations", "$build/database/migrations");

echo "Flattening public assets...\n";
rcopy("$root/public/assets", "$build/assets");

echo "Copying config.php (patched for flat layout)...\n";
$config = file_get_contents("$root/config.php");
$config = str_replace("__DIR__ . '/public'", '__DIR__', $config);
file_put_contents("$build/config.php", $config);

echo "Writing front controller (index.php)...\n";
$index = file_get_contents("$root/public/index.php");
// public/index.php lived one level below root; at the build root it *is* the root now.
$index = str_replace("dirname(__DIR__) . '/snack/bootstrap.php'", "__DIR__ . '/snack/bootstrap.php'", $index);
file_put_contents("$build/index.php", $index);

echo "Copying bin/migrate.php...\n";
rcopy("$root/bin/migrate.php", "$build/bin/migrate.php");

echo "Copying .env.example...\n";
copy("$root/.env.example", "$build/.env.example");

echo "Writing .htaccess...\n";
$htaccess = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Block direct web access to framework/source folders
    RewriteRule ^(snack|app|database|bin|vendor|tests)(/|$) - [F,L]

    # Block direct web access to sensitive files
    RewriteRule ^(composer\.(json|lock)|\.env(\.example)?|config\.php|phpunit\.xml|README\.md)$ - [F,L]

    # Serve real files/directories as-is (assets, etc.)
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Everything else goes through the front controller
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS;
file_put_contents("$build/.htaccess", $htaccess);

echo "Copying vendor/ (as-is — same relative depth, so autoload paths still resolve)...\n";
if (!is_dir("$root/vendor")) {
    fwrite(STDERR, "vendor/ not found. Run `composer install` first, then `composer build`.\n");
    exit(1);
}
rcopy("$root/vendor", "$build/vendor");

echo "\nBuild complete: $build\n";
echo "Copy the *contents* of build/ to your shared hosting web root.\n";
echo "Remember to add a .env on the server (copy .env.example -> .env and fill in DB creds).\n";
