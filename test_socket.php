<?php
header('Content-Type: text/plain');

echo "Socket file check:\n";
echo "==================\n\n";

$socket = '/var/run/mysqld/mysqld.sock';
echo "Socket path: $socket\n";
echo "Exists: " . (file_exists($socket) ? 'YES' : 'NO') . "\n";
echo "Readable: " . (is_readable($socket) ? 'YES' : 'NO') . "\n";
echo "Is socket: " . (is_link($socket) || filetype($socket) == 'socket' ? 'YES' : 'NO') . "\n";

echo "\nPHP info:\n";
echo "User: " . get_current_user() . "\n";
echo "UID: " . posix_getuid() . "\n";
echo "GID: " . posix_getgid() . "\n";

echo "\nMySQL default socket from PHP:\n";
echo ini_get('mysqli.default_socket') . "\n";
