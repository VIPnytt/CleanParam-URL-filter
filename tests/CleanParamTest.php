<?php

use vipnytt\CleanParamFilter;

class CleanParamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @covers CleanParamFilter::addURL
     * @covers CleanParamFilter::addURL
     */
    public function testCleanParam()
    {
        // init parser
        $filter = new CleanParamFilter();
        $this->assertInstanceOf('CleanParamFilter', $filter);

        $filter->addCleanParam('ref');

        $filter->addURL('http://example.com/');
        $filter->addURL('http://example.com/?ref=somewhere1');
        $filter->addURL('http://example.com/?ref=somewhere2&test=2');
        $filter->addURL('http://example.com/?ref=somewhere3&test1=3');
        $filter->addURL('http://example.com/?ref=somewhere4&test1=3');
        $filter->addURL('http://example.com/?ref=somewhere5&test1=3');
        $filter->addURL('http://example.com/?ref=somewhere6');

        $this->assertFalse($filter->isDuplicate('http://example.com/'));
        $this->assertTrue($filter->isDuplicate('http://example.com/?ref=somewhere5&test1=3'));
    }
}
