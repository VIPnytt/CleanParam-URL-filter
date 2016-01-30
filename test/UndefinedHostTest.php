<?php

use vipnytt\CleanParamFilter;

class UndefinedHostTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @dataProvider generateDataForTest
     * @param array $urls
     * @expectedException PHPUnit_Framework_Error_WARNING
     * @return void
     */
    public function testUndefinedHost($urls)
    {
        $filter = new CleanParamFilter($urls);
        $this->assertInstanceOf('vipnytt\CleanParamFilter', $filter);

        $filter->addCleanParam('articleID');

        // Contains
        $this->assertContains('http:/example.com/', $filter->listApproved());
        $this->assertContains('http:/example.com/?articleID', $filter->listApproved());
        $this->assertContains('http:/example.net/', $filter->listApproved());
        $this->assertContains('http:/example.net/?articleID', $filter->listApproved());
        // Same tests as over, but as opposite of the first
        $this->assertNotContains('http:/example.com/', $filter->listDuplicate());
        $this->assertNotContains('http:/example.com/?articleID', $filter->listDuplicate());
        $this->assertNotContains('http:/example.net/', $filter->listDuplicate());
        $this->assertNotContains('http:/example.net/?articleID', $filter->listDuplicate());
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
                    'http://example.com/',
                    'http://example.com/?articleID',
                    'http://example.net/',
                    'http://example.net/?articleID'
                )
            )
        );
    }
}
