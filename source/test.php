<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('CleanParamFilter.php');

$filter = new \vipnytt\CleanParamFilter();

$filter->addCleanParam('ref');

$filter->addURL('http://example.com/?ref=somewhere1');
$filter->addURL('http://example.com/?ref=somewhere2&test=2');
$filter->addURL('http://example.com/?ref=somewhere3&test=3');
$filter->addURL('http://example.com/?ref=somewhere1&test1=3');
$filter->addURL('http://example.com/?ref=somewhere2&test1=3');
$filter->addURL('http://example.com/?ref=somewhere3&test1=3');



print_r($filter->listApproved());

