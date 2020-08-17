<?php

namespace Blackthorne\VoodooSms\Exceptions;

use GuzzleHttp\Psr7\Response;

class VoodooSmsApiException extends \RuntimeException
{
    public static function fromResponse(Response $response)
    {
        if (! $response->hasHeader('Content-Type')) {
            return new static("Client Error {$response->getStatusCode()}", $response->getStatusCode());
        }

        [$content_type] = $response->getHeader('Content-Type');
        if (! $content_type || strpos($content_type, 'application/json') !== 0) {
            return new static("Client Error, and unexpected return content type", $response->getStatusCode());
        }

        $body = json_decode($response->getBody());
        if (empty($body->error)) {
            return new static("Client Error, no error message", $response->getStatusCode());
        }

        return new static($body->error->msg, $body->error->code);
    }
}