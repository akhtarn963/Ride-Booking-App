<?php

echo 'PHP Version: ' . PHP_VERSION . '<br>';
echo 'PHP Binary: ' . PHP_BINARY . '<br>';
echo 'Loaded INI: ' . php_ini_loaded_file() . '<br>';
echo 'PDO PostgreSQL: ';
var_dump(extension_loaded('pdo_pgsql'));