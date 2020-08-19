<?php

namespace Blackthorne\VoodooSms\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Xeen\MockServerClient\Client as MockServerClient;

class TestCase extends BaseTestCase
{
    const CONFIG_NAME = 'test_config.ini';

    // Populated by loadConfig() before any setUp()s are called
    protected static $config;

    /**
     * @beforeClass
     */
    public static function loadConfig()
    {
        if ($envJson = getenv('TEST_CONFIG_JSON')) {
            self::$config = Config::load($envJson, new Json(), true);
        } elseif ($configName = getenv('TEST_CONFIG')) {
            self::$config = Config::load($configName);
        } else {
            self::$config = Config::load(static::CONFIG_NAME);
        }
    }
}