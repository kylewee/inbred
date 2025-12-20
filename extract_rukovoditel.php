<?php
// extract_rukovoditel.php
// Run this to extract Rukovoditel and extension

$baseDir = __DIR__ . '/public_html/website_7e9e6396';
$tempDir = __DIR__ . '/public_html/tmp';

if (!is_dir($baseDir)) {
    die("Base dir not found: $baseDir\n");
}

// Move current crm to delete later
$deleteLater = $baseDir . '/crm_delete_later';
if (!is_dir($deleteLater)) {
    mkdir($deleteLater, 0755, true);
}

$crmDir = $baseDir . '/crm';
if (is_dir($crmDir)) {
    $files = glob($crmDir . '/*');
    foreach ($files as $file) {
        rename($file, $deleteLater . '/' . basename($file));
    }
    rmdir($crmDir);
}

// Extract main program
$zipFile = $tempDir . '/rukovoditel_3.6.3.zip';
if (file_exists($zipFile)) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($baseDir);
        $zip->close();
        // Rename rukovoditel_3.6.3 to crm
        if (is_dir($baseDir . '/rukovoditel_3.6.3')) {
            rename($baseDir . '/rukovoditel_3.6.3', $crmDir);
        }
        echo "Main program extracted.\n";
    } else {
        echo "Failed to open main ZIP.\n";
    }
} else {
    echo "Main ZIP not found: $zipFile\n";
}

// Extract extension
$extZip = $tempDir . '/rukovoditel_ext_3.6.3.zip';
$pluginsDir = $crmDir . '/plugins';
if (file_exists($extZip) && is_dir($crmDir)) {
    $zip = new ZipArchive();
    if ($zip->open($extZip) === TRUE) {
        $zip->extractTo($pluginsDir);
        $zip->close();
        // Move ext contents to plugins
        $extDir = $pluginsDir . '/ext';
        if (is_dir($extDir)) {
            $files = glob($extDir . '/*');
            foreach ($files as $file) {
                rename($file, $pluginsDir . '/' . basename($file));
            }
            rmdir($extDir);
        }
        echo "Extension extracted.\n";
    } else {
        echo "Failed to open extension ZIP.\n";
    }
} else {
    echo "Extension ZIP not found or CRM dir missing.\n";
}

// Set permissions
function setPermissions($dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $item) {
            chmod($item->getPathname(), 0755);
        }
    }
}

setPermissions($crmDir);
echo "Permissions set.\n";

// Create .htaccess
$htaccess = $crmDir . '/.htaccess';
$content = '<FilesMatch \.php$>
    SetHandler application/x-httpd-ea-php83
</FilesMatch>
Options -Indexes
DirectoryIndex index.php
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /crm/
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /crm/index.php [L]
</IfModule>';
file_put_contents($htaccess, $content);
echo ".htaccess created.\n";

echo "Installation complete. Visit https://mechanicstaugustine.com/crm/ to run the installer.\n";
?>