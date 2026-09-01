<?php
echo "opcache enabled: " . (ini_get('opcache.enable') ? 'ON' : 'OFF') . PHP_EOL;
echo "opcache_cli: " . (ini_get('opcache.enable_cli') ? 'ON' : 'OFF') . PHP_EOL;
echo "opcache.validate: " . ini_get('opcache.validate_timestamps') . PHP_EOL;
echo "opcache.revalidate_freq: " . ini_get('opcache.revalidate_freq') . PHP_EOL;
echo "opcache_reset exists: " . (function_exists('opcache_reset') ? 'YES' : 'NO') . PHP_EOL;
echo "opcache_get_status exists: " . (function_exists('opcache_get_status') ? 'YES' : 'NO') . PHP_EOL;
