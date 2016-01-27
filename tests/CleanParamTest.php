<?php

use vipnytt\CleanParamFilter;

class CleanParamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @dataProvider generateDataForTest
     * @covers       CleanParamFilter::addCleanParam
     * @covers       CleanParamFilter::listApproved
     * @covers       CleanParamFilter::listDuplicate
     * @param array $urls
     */
    public function testCleanParam($urls)
    {
        // init parser
        $filter = new CleanParamFilter($urls);
        $this->assertInstanceOf('vipnytt\CleanParamFilter', $filter);

        $filter->addCleanParam('ref');

        // Contains
        $this->assertContains('http://example.com/', $filter->listApproved());
        $this->assertContains('http://example.com/?ref=somewhere1', $filter->listDuplicate());
        $this->assertContains('http://example.com/?ref=somewhere3&test1=3', $filter->listApproved());
        $this->assertContains('http://example.com/?ref=somewhere5&test1=3', $filter->listDuplicate());
        // Same tests as over, but as opposite of the first
        $this->assertNotContains('http://example.com/', $filter->listDuplicate());
        $this->assertNotContains('http://example.com/?ref=somewhere1', $filter->listApproved());
        $this->assertNotContains('http://example.com/?ref=somewhere3&test1=3', $filter->listDuplicate());
        $this->assertNotContains('http://example.com/?ref=somewhere5&test1=3', $filter->listApproved());
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
                    'http://example.com/?ref=somewhere1',
                    'http://example.com/?ref=somewhere2&test=2',
                    'http://example.com/?ref=somewhere3&test1=3',
                    'http://example.com/?ref=somewhere4&test1=3',
                    'http://example.com/?ref=somewhere5&test1=3',
                    'http://example.com/?ref=somewhere6'
                )
            )
        );
    }
}
