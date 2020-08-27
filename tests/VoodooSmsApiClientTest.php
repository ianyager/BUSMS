<?php

namespace Blackthorne\VoodooSms\Tests;

use Blackthorne\VoodooSms\Exceptions\VoodooSmsApiException;
use Blackthorne\VoodooSms\VoodooSmsMessage;
use Noodlehaus\Config;

class VoodooSmsApiClientTest extends TestCase
{
    /**
     * Test get_credits
     * @dataProvider getBalanceProvider
     */
    public function testGetBalance($message, $expected, $secret, $spec)
    {
        $this->setSecret($secret);
        $this->setMockServerExpectation($spec);

        $balance = $this->client->get_credits();
        $this->assertEquals($expected, $balance, $message);
    }

    public function getBalanceProvider()
    {
        return [
            [
                "Should have 100 credits",
                100,
                "secret1",
                [
                    'id' => 'get_credits_100',
                    'times' => [
                        "remainingTimes" => 1,
                        "unlimited" => false,
                    ],
                    'httpRequest' => [
                        'path' => '/credits',
                        'method' => 'GET',
                        'headers' => [
                            [
                                'name' => 'Authorization',
                                'values' => ['Bearer secret1'],
                            ],
                        ],
                    ],
                    'httpResponse' => [
                        'body' => [
                            'json' => [
                                'amount' => 100,
                            ],
                        ],
                    ],
                ],
            ],
            [
                "Should have 10 credits",
                10,
                "secret2",
                [
                    'id' => 'get_credits_10',
                    'times' => [
                        "remainingTimes" => 1,
                        "unlimited" => false,
                    ],
                    'httpRequest' => [
                        'path' => '/credits',
                        'method' => 'GET',
                        'headers' => [
                            [
                                'name' => 'Authorization',
                                'values' => ['Bearer secret2'],
                            ],
                        ],
                    ],
                    'httpResponse' => [
                        'body' => [
                            'json' => [
                                'amount' => 10,
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider sendProvider
     */
    public function testSend($count, $to, $from, $message, $secret, $spec)
    {
        $this->setSecret($secret);
        $this->setMockServerExpectation($spec);
        $message = new VoodooSmsMessage(compact('to', 'from', 'message'));
        $result = $this->client->send($message);
        $this->assertIsObject($result);
        foreach (['count', 'body', 'credits', 'balance'] as $key) {
            $this->assertObjectHasAttribute($key, $result);
        }
        $this->assertEquals($count, $result->count, 'Should have same count');
    }

    public function sendProvider()
    {
        return [
            [
                1,
                '+447800000000',
                'Test Sender',
                'Hello World!',
                'secret2',
                [
                    'id' => 'send_1',
                    'httpRequest' => [
                        'path' => '/sendsms',
                        'method' => 'POST',
                        'headers' => [
                            [
                                'name' => 'Authorization',
                                'values' => ['Bearer secret2'],
                            ],
                        ],
                        'body' => [
                            'to' => '+447800000000',
                        ],
                    ],
                    'httpResponse' => [
                        'body' => [
                            'json' => [
                                "count" => 1,
                                "originator" => "VoodooSMS",
                                "body" => "Hello this is your SMS body",
                                "scheduledDateTime" => 1537525949,
                                "credits" => 1,
                                "balance" => 2365,
                                "messages" => [
                                    [
                                        "id" => "97709216074987x3NFD16GgkChK2E67441209181vapi",
                                        "recipient" => 447800000000,
                                        "reference" => null,
                                        "status" => "PENDING_SENT",
                                    ]
                                ]
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    public function testSendOverrideTo()
    {
        $expectation = Config::load('mocks/send_ok_schema.json')->all();
        $this->setMockServerExpectation($expectation);

        $message = new VoodooSmsMessage([
            'to' => '+447700900100',
            'from' => 'TestCorp.',
            'msg' => 'Test Message',
        ]);
        $this->client->setConfig([
            'override_to' => '+447700900000',
            'secret' => 'secret2',
        ]);
        $result = $this->client->send($message);
        $this->assertIsObject($result);
        $this->assertEquals('1', $result->count, "Should send 1 message");
        $this->assertCount(1, $result->messages);
        $this->assertEquals(447700900000, $result->messages[0]->recipient);
        $this->assertEquals('PENDING_SENT', $result->messages[0]->status);
    }

    public function testSendAuthFail()
    {
        $expectation = Config::load('mocks/send_fail_auth.json')->all();
        $this->setMockServerExpectation($expectation);

        $message = new VoodooSmsMessage([
            'to' => '+447700900000',
            'from' => 'TestCorp.',
            'msg' => 'Test Message',
        ]);
        $this->expectException(VoodooSmsApiException::class);
        $result = $this->client->send($message);
        $this->assert(false, "Shouldn't get here");
    }
}