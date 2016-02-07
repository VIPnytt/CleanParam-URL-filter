<?php

namespace vipnytt\CleanParamFilter\tests;

use vipnytt\CleanParamFilter;

class CleanParamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @dataProvider generateDataForTest
     * @param array $urls
     * @return void
     */
    public function testCleanParam($urls)
    {
        $filter = new CleanParamFilter($urls);
        $this->assertInstanceOf('vipnytt\CleanParamFilter', $filter);

        $filter->addCleanParam('ref');
        $filter->addCleanParam('uid', '/page2/', 'example.com');

        // Contains
        $this->assertContains('http://example.com/', $filter->listApproved());
        $this->assertContains('http://example.com/?ref=somewhere1', $filter->listDuplicate());
        $this->assertContains('http://example.com/?ref=somewhere3&test1=3', $filter->listApproved());
        $this->assertContains('http://example.com/?ref=somewhere5&test1=3', $filter->listDuplicate());
        $this->assertContains('http://example.com/page1/', $filter->listApproved());
        $this->assertContains('http://example.com/page1/?uid=12345', $filter->listApproved());
        $this->assertContains('http://example.com:80/', $filter->listDuplicate());
        // Same tests as over, but as opposite of the first
        $this->assertNotContains('http://example.com/', $filter->listDuplicate());
        $this->assertNotContains('http://example.com/?ref=somewhere1', $filter->listApproved());
        $this->assertNotContains('http://example.com/?ref=somewhere3&test1=3', $filter->listDuplicate());
        $this->assertNotContains('http://example.com/?ref=somewhere5&test1=3', $filter->listApproved());
        $this->assertNotContains('http://example.com/page1/', $filter->listDuplicate());
        $this->assertNotContains('http://example.com/page1/?uid=12345', $filter->listDuplicate());
        $this->assertNotContains('http://example.com:80/', $filter->listApproved());
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
                    'http://example.com/?ref=somewhere6',
                    'http://example.com:80/',
                    'http://example.com/page1/',
                    'http://example.com/page1/?uid=12345'
                )
            )
        );
    }
}
