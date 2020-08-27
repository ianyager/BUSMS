<?php

namespace Blackthorne\VoodooSms\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Xeen\MockServerClient\Client as MockServerClient;
use Xeen\MockServerClient\Traits\MockServerTestCase;
use Blackthorne\VoodooSms\Client;

class TestCase extends BaseTestCase
{
    use MockServerTestCase;
    const CONFIG_NAME = 'test_config.ini';

    // Populated by loadConfig() before any setUp()s are called
    protected static $config;

    // Setup with fresh client on each setUp()
    protected Client $client;

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
    public function setUp(): void
    {
        $this->client = new Client($this->clientConfig());
        $this->clearMockServerExpectaions();
    }

    public function clientConfig()
    {
        return self::$config['voodoosms'];
    }

    public function setSecret($secret) {
        $this->client->setConfig([
            'secret' => $secret,
        ]);
    }

    protected function loadMockServerExpectation($filename)
    {
        return $this->setMockServerExpectation(Config::load($filename)->all());
    }
}