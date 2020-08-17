<?php

namespace Blackthorne\VoodooSms\Exceptions;

class UnexpectedResponse extends VoodooSmsApiException
{
    public function __construct(\stdClass $response_object)
    {
        $json = json_encode($response_object);
        $message = "Unexpected response format: {$json}";
        parent::__construct($message, -3);
    }
}