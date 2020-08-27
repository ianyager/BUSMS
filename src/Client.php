<?php

namespace Blackthorne\VoodooSms;

use Blackthorne\VoodooSms\Exceptions\VoodooSmsApiException;
use Blackthorne\VoodooSms\Exceptions\UnexpectedContentTypeException;
use Blackthorne\VoodooSms\Exceptions\UnexpectedResponse;
use GuzzleHttp\Client AS HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use \Datetime;
use \JsonException;

class Client
{
    const VOODOOSMS_API_URL = 'https://api.voodoosms.com/';

    private $config;

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    public function setConfig(array $config)
    {
        if (! isset($this->config)) {
            $this->config = (object)[
                'sandbox' => true,
            ];
        }
        foreach ($config as $key => $value) {
            $this->config->{$key} = $value;
        }
    }

    public function fetch_inbox()
    {
        try {
            return $this->api_request('GET', 'inbox');
        } catch (VoodooSmsApiException $ex) {
            if ($ex->getCode() == 30) {
                // Exception with code 30 seems to just mean we have no messages
                return [];
            }
            throw $ex;
        }
    }

    public function fetch_report()
    {
        return $this->api_request('GET', 'report');
    }

    public function fetch_report_for_datetime_range(Datetime $start, Datetime $end, int $limit = 25)
    {
        return $this->api_request('GET', 'report', [
            'start' => $start->format('c'),
            'end' => $end->format('c'),
            'limit' => $limit,
        ]);
    }

    public function fetch_report_for_message_id(string $message_id)
    {
        return $this->api_request('GET', 'report', [
            'message_id' => $message_id,
        ]);
    }

    public function send_template(int $template_id, VoodooSmsMessage $message)
    {
        $data = $message->jsonSerialize();
        return $this->api_request('POST', "sendsms/template/{$template_id}", $data);
    }

    public function send(VoodooSmsMessage $message)
    {
        $data = $message->jsonSerialize();
        if (isset($this->config->override_to)) {
            $data['to'] = $this->config->override_to;
        }
        return $this->api_request('POST', 'sendsms', $data);
    }

    public function send_single_sms($to, $msg)
    {
        // The Sender ID must be > 3 and <= 11 alphanumeric characters.
        $from = 'Blackthorne';
        $message = new VoodooSmsMessage(compact('to', 'from', 'msg'));
        return $this->api_request('POST', 'sendsms', $message);
    }

    public function send_to_list($list_id, $message)
    {
        $request = [
            'to' => 'c' . $list_id,
            'from' => 'Blackthorne',
            'msg' => $message,
        ];
        return $this->api_request('POST', "sendtocontacts", $request);
    }

    public function get_credits()
    {
        $response = $this->api_request('GET', 'credits');
        if (empty($response->amount)) {
            $json = json_encode($response);
            throw new VoodooSmsApiException("Unexpected message format: {$json}", -3);
        }
        return $response->amount;
    }

    // Contact Methods
    public function retrieve_contact_lists()
    {
        return $this->api_request('GET', 'contacts');
    }

    public function retrieve_contact_list($list_id)
    {
        return $this->api_request('GET', "contacts?contact_list_id={$list_id}");
    }

    public function create_contact_list($contact_list_name, $contact_objects)
    {
        $request = [
            'name' => $contact_list_name,
            'contacts' => $contact_objects,
        ];
        return $this->api_request('POST', 'contacts', $request);
    }

    public function update_contact_list($list_id, $contact_objects)
    {
        $request = [
            'contacts' => $contact_objects,
        ];
        $response = $this->api_request('PUT', "contacts/{$list_id}", $request);
        dd($response);
    }

    public function delete_contact($list_id, $contact_id)
    {
        $response = $this->api_request('DELETE', "contacts/{$list_id}/{$contact_id}");
        return $response->status == 'SUCCESS';
    }

    public function delete_contact_list($list_id)
    {
        $response = $this->api_request('DELETE', "contacts/{$list_id}");
        return $response->status == 'SUCCESS';
    }

    protected function api_request(string $method, string $uri, $data = NULL)
    {
        $client = $this->get_client();
        try {
            if ($data) {
                if ($this->config->sandbox) {
                    $data['sandbox'] = TRUE;
                }
                if (in_array($method, ['GET', 'HEAD'])) {
                    $response = $client->request($method, $uri, ['query' => $data]);
                } else {
                    $response = $client->request($method, $uri, ['json' => $data]);
                }
            } else {
                $response = $client->request($method, $uri);
            }
        } catch (ClientException $ex) {
            if ($ex->hasResponse()) {
                throw VoodooSmsApiException::fromResponse($ex->getResponse());
            }
        } catch (ConnectException $ex) {
            throw new VoodooSmsApiException("Couldn't connect", -1, $ex);
        }

        [$content_type] = $response->getHeader('Content-Type');
        if (strpos($content_type, 'application/json') === 0) {
            return json_decode($response->getBody());
        } else if (strpos($content_type, 'text/html') === 0) {
            $text_or_html = $response->getBody()->getContents();
            try {
                return json_decode($text_or_html, FALSE, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $ex) {
                throw new UnexpectedResponse((object)['text_response' => $text_or_html]);
            }
        } else {
            throw new UnexpectedContentTypeException($content_type);
        }
    }

    private function get_base_url()
    {
        if (isset($this->config->base_url)) {
            return $this->config->base_url;
        }
        return static::VOODOOSMS_API_URL;
    }

    private function get_default_headers()
    {
        return array_merge(
            $this->get_auth_headers(),
        );
    }

    private function get_auth_headers()
    {
        return [
            'Authorization' => "Bearer {$this->config->secret}",
        ];
    }

    protected function get_client_defaults()
    {
        return [
            'base_uri' => $this->get_base_url(),
            'headers' => $this->get_default_headers(),
        ];
    }

    protected function get_client(): HttpClient
    {
        return new HttpClient($this->get_client_defaults());
    }
}
