# PHP-API-Dathost.net
Dathost.net API for PHP. PHP 5.5+ and make sure your web host support cURL. Look at source code to see all available functions.

# How to use
[Click here to see parameters for all functions](https://dathost.net/api)

```php
<?php
include_once 'DathostAPI.php';

$api = new DathostAPI('your-email', 'your-password');

// Creating Server
$params = [
    'game' => 'csgo',
    'name' => 'Server created via API',
    'csgo_settings.rcon' => '123456'
]; 
$api->createGameServer($params);

// For deleting server
$api->deleteGameServer('your-server-id');
