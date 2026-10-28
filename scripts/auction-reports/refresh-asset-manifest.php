<?php

declare(strict_types=1);

// Mechanical hash regeneration only; never repackage source SVGs or query a DB.
$root = dirname(__DIR__, 2).'/resources/auction-reports';
$manifest = require $root.'/manifest.php';
$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! $file->isFile() || $file->getPathname() === $root.'/manifest.php' || $file->getFilename() === 'manifest.php') {
        continue;
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    $files[$relative] = hash_file('sha256', $file->getPathname());
}
ksort($files);
$manifest['files'] = $files;
file_put_contents($root.'/manifest.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($manifest, true).";\n");
echo count($files).' local assets verified and hashed.'.PHP_EOL;
