<?php

use vipnytt\CleanParamFilter;

class CleanParamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @covers CleanParamFilter::addCleanParam
     * @covers CleanParamFilter::addURL
     * @covers CleanParamFilter::listApproved
     * @covers CleanParamFilter::listDuplicate
     */
    public function testCleanParam()
    {
        // init parser
        $filter = new CleanParamFilter();
        $this->assertInstanceOf('vipnytt\CleanParamFilter', $filter);

        $filter->addCleanParam('ref');

        $filter->addURL('http://example.com/');
        $filter->addURL('http://example.com/?ref=somewhere1');
        $filter->addURL('http://example.com/?ref=somewhere2&test=2');
        $filter->addURL('http://example.com/?ref=somewhere3&test1=3');
        $filter->addURL('http://example.com/?ref=somewhere4&test1=3');
        $filter->addURL('http://example.com/?ref=somewhere5&test1=3');
        $filter->addURL('http://example.com/?ref=somewhere6');

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
}
