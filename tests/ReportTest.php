<?php

namespace Blackthorne\VoodooSms\Tests;

use \Datetime;

class ReportTest extends TestCase
{
    const GOOD_SECRET = 'secret2';

    public function testReportForRange()
    {
        $this->setSecret(self::GOOD_SECRET);
        $this->loadMockServerExpectation('mocks/report_date_range.json');

        $start = new Datetime('2020-01-01');
        $end = new Datetime('2020-02-01');
        $result = $this->client->fetch_report_for_datetime_range($start, $end);

        $this->assertObjectHasAttribute('limit', $result);
        $this->assertObjectHasAttribute('report', $result);
        $this->assertIsArray($result->report, "Should have a 'report' array");
    }
}