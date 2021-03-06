<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use App\Kernel;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

defined('SULU_MAINTENANCE') || define('SULU_MAINTENANCE', getenv('SULU_MAINTENANCE') ?: false);

// maintenance mode
if (SULU_MAINTENANCE) {
    $maintenanceFilePath = __DIR__ . '/maintenance.php';
    // show maintenance mode and exit if no allowed IP is met
    if (require $maintenanceFilePath) {
        exit();
    }
}

require __DIR__.'/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV']) && !isset($_ENV['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__.'/../.env');
}

$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? ('prod' !== $env));

if ($debug) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$kernel = new Kernel($env, $debug, SuluKernel::CONTEXT_WEBSITE);

// Comment this line if you want to use the "varnish" http
// caching strategy. See http://sulu.readthedocs.org/en/latest/cookbook/caching-with-varnish.html
//if ($env !== 'dev') {
//    $kernel = new SuluHttpCache($kernel);

    // When using the HttpCache, you need to call the method in your front controller
    // instead of relying on the configuration parameter
//    Request::enableHttpMethodParameterOverride();
//}

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
