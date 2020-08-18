<?php

namespace Blackthorne\VoodooSms\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Xeen\MockServerClient\Client as MockServerClient;

class MockServerTestCase extends BaseTestCase
{
    protected MockServerClient $mockServerClient;

    /**
     * @before
     */
    public function setupMockServerClient()
    {
        $this->mockServerClient = new MockServerClient();
    }

    protected function setMockServerExpectation($expectaion)
    {
        $this->mockServerClient->addExpectation($expectaion);
    }

    protected function clearMockServerExpectaions()
    {
        $this->mockServerClient->reset();
    }
}