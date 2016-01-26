<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('CleanParamFilter.php');

$filter = new \vipnytt\CleanParamFilter();

$filter->addCleanParam('ref&site');

$filter->addURL('http://example.com/');
$filter->addURL('http://example.com/?ref=somewhere1');
$filter->addURL('http://example.com/?ref=somewhere2&test=2');
$filter->addURL('http://example.com/?ref=somewhere3&test1=3');
$filter->addURL('http://example.com/?ref=somewhere4&test1=3');
$filter->addURL('http://example.com/?ref=somewhere5&test1=3');
$filter->addURL('http://example.com/?ref=somewhere6');



print_r($filter->listApproved());

