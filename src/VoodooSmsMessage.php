<?php

namespace Blackthorne\VoodooSms;

use \Datetime;
use \JsonSerializable;

class VoodooSmsMessage implements JsonSerializable
{
    public Datetime $schedule;
    public string $to;
    public string $from;
    public string $external_reference;

    public function __construct(array $data = [])
    {
        // foreach (['to', 'from', 'external_reference', 'schedule'] as $key) {
        foreach ($data as $key => $value) {
            if (empty($data[$key])) {
                continue;
            }
            $this->{$key} = $value; // $data[$key];
        }
    }

    public function jsonSerialize()
    {
        $array = get_object_vars($this);
        if (isset($this->schedule)) {
            $array['schedule'] = $this->schedule->getTimestamp();
            // $array['timezone'] = $this->schedule->getTimezone()->getName();
        }
        return array_filter($array);
    }

    public function serializeSchedule()
    {
        if ($this->schedule) {
            return $this->schedule->getTimestamp();
        }
        return NULL;
    }
}