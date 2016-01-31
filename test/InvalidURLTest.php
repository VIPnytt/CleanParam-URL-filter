<?php

use vipnytt\CleanParamFilter;

class InvalidURLTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @dataProvider generateDataForTest
     * @param array $urls
     * @return void
     */
    public function testInvalidURL($urls)
    {
        $filter = new CleanParamFilter($urls);
        $this->assertInstanceOf('vipnytt\CleanParamFilter', $filter);

        // Invalid URLs
        $this->assertContains('http:/example.tld/', $filter->listInvalid());
        $this->assertNotContains('http:/example.tld/', $filter->listApproved());
        $this->assertNotContains('http:/example.tld/', $filter->listDuplicate());
    }

    /**
     * Generate test case data
     * @return array
     */
    public function generateDataForTest()
    {
        return array(
            array(
                array(
                    'http:/example.tld/',
                    'http://example.com/',
                    'http://example.com/?ref=somewhere2',
                    'http://example.com/?ref=somewhere3?'
                )
            )
        );
    }
}
