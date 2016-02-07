<?php

namespace vipnytt\CleanParamFilter\tests;

use vipnytt\CleanParamFilter;

class MultiHostTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic usage test
     *
     * @dataProvider generateDataForTest
     * @param array $urls
     * @return void
     */
    public function testMultiHost($urls)
    {
        $filter = new CleanParamFilter($urls);
        $this->assertInstanceOf('vipnytt\CleanParamFilter', $filter);

        $filter->addCleanParam('ref', '/', 'example.com');
        $filter->addCleanParam('uid', '/', 'test.net');

        // Contains
        $this->assertContains('http://example.com/', $filter->listApproved());
        $this->assertContains('http://example.com/?ref=somewhere', $filter->listDuplicate());
        $this->assertContains('http://example.com/?uid=12345', $filter->listApproved());
        $this->assertContains('http://test.net/', $filter->listApproved());
        $this->assertContains('http://test.net/?ref=somewhere', $filter->listApproved());
        $this->assertContains('http://test.net/?uid=12345', $filter->listDuplicate());
        $this->assertContains('http://somewhere.tld/?param=unknown', $filter->listApproved());
        // Same tests as over, but as opposite of the first
        $this->assertNotContains('http://example.com/', $filter->listDuplicate());
        $this->assertNotContains('http://example.com/?ref=somewhere', $filter->listApproved());
        $this->assertNotContains('http://example.com/?uid=12345', $filter->listDuplicate());
        $this->assertNotContains('http://test.net/', $filter->listDuplicate());
        $this->assertNotContains('http://test.net/?ref=somewhere', $filter->listDuplicate());
        $this->assertNotContains('http://test.net/?uid=12345', $filter->listApproved());
        $this->assertNotContains('http://somewhere.tld/?param=unknown', $filter->listDuplicate());
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
                    'http://example.com/?ref=somewhere',
                    'http://example.com/?uid=12345',
                    'http://test.net/',
                    'http://test.net/?ref=somewhere',
                    'http://test.net/?uid=12345',
                    'http://somewhere.tld/?param=unknown'
                )
            )
        );
    }
}
