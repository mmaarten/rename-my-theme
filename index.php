<?php 

require 'vendor/autoload.php';

$theme_name = isset($_GET['name']) ? $_GET['name'] : '';

/**
 * Sanitize theme name.
 * ---------------------------------------------------------------------------------------------------------------------
 */
$theme_name = preg_replace('/ +/', ' ', trim($theme_name));

/**
 * Validation.
 * ---------------------------------------------------------------------------------------------------------------------
 */
if (! $theme_name) {
    die('Theme name is required');
}

/**
 * Generate slug & namespace
 * ---------------------------------------------------------------------------------------------------------------------
 */
$theme_slug = str_replace(' ', '-', strtolower($theme_name));
$theme_ns   = str_replace(' ', '', $theme_name);

/**
 * Create temporary directory.
 * ---------------------------------------------------------------------------------------------------------------------
 */
$dir = 'temp/' . uniqid();

if (! mkdir($dir)) {
    die('Unable to create temporary directory.');
}
/**
 * Download archive from GIT.
 * ---------------------------------------------------------------------------------------------------------------------
 */
$copy = copy('https://github.com/mmaarten/my-theme/archive/master.zip', "$dir/master.zip");

if (! $copy) {
    die('Unable to download theme from GIT.');
}

/**
 * Extract archive.
 * ---------------------------------------------------------------------------------------------------------------------
 */

$zip = new \PhpZip\ZipFile();

try {
    $zip
        ->openFile("$dir/master.zip")
        ->extractTo($dir)
        ->close();
} catch (\PhpZip\Exception\ZipException $e) {
    die($e->message);
}

/**
 * Delete archive.
 * ---------------------------------------------------------------------------------------------------------------------
 */
unlink("$dir/master.zip");

/**
 * Find and replace content.
 * ---------------------------------------------------------------------------------------------------------------------
 */

$files = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator("$dir/my-theme-master", \RecursiveDirectoryIterator::SKIP_DOTS),
    \RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($files as $fileinfo) {
    if (! $fileinfo->isFile()) {
        continue;
    }
    
    $contents = file_get_contents($fileinfo->getRealPath());
    $contents = str_replace('My Theme', $theme_name, $contents);
    $contents = str_replace('My/Theme', $theme_ns, $contents);
    $contents = str_replace('My\Theme', $theme_ns, $contents);
    $contents = str_replace('my-theme', $theme_slug, $contents);
    $contents = str_replace('@my/theme', $theme_slug, $contents);
    $contents = str_replace('my/theme', $theme_slug, $contents);
    $contents = str_replace('My\\Theme', $theme_ns, $contents);
    
    file_put_contents($fileinfo->getRealPath(), $contents);
}


/**
 * Rename files.
 * ---------------------------------------------------------------------------------------------------------------------
 */
 
rename("$dir/my-theme-master/languages/my-theme.pot", "$dir/my-theme-master/languages/$theme_slug.pot");
rename("$dir/my-theme-master", "$dir/$theme_slug");

/**
 * Create archive.
 * ---------------------------------------------------------------------------------------------------------------------
 */
 
try {
    $zip->addDirRecursive("$dir/$theme_slug")
        ->saveAsFile("$dir/$theme_slug.zip")
        ->close();    
} catch (\PhpZip\Exception\ZipException $e) {
     die($e->message);
}

/**
 * Remove all files.
 * ---------------------------------------------------------------------------------------------------------------------
 */
$files = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator("$dir/$theme_slug", \RecursiveDirectoryIterator::SKIP_DOTS),
    \RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($files as $fileinfo) {
    $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
    $todo($fileinfo->getRealPath());
}

rmdir("$dir/$theme_slug");

/**
 * Download archive.
 * ---------------------------------------------------------------------------------------------------------------------
 */
$file = "$dir/$theme_slug.zip";
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.basename($file).'"');
header('Content-Length: ' . filesize($file));
flush();
readfile($file);

/**
 * Remove archive.
 * ---------------------------------------------------------------------------------------------------------------------
 */
unlink($file);

/**
 * Remove directory.
 * ---------------------------------------------------------------------------------------------------------------------
 */
rmdir($dir);
