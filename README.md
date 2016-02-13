[![Build Status](https://travis-ci.org/VIPnytt/CleanParam-URL-filter.svg?branch=master)](https://travis-ci.org/VIPnytt/CleanParam-URL-filter) [![Code Climate](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/gpa.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter) [![Test Coverage](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/coverage.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/coverage) [![License](https://poser.pugx.org/VIPnytt/CleanParam-URL-filter/license)](https://packagist.org/packages/VIPnytt/CleanParam-URL-filter) [![Join the chat at https://gitter.im/VIPnytt/CleanParam-URL-filter](https://badges.gitter.im/VIPnytt/CleanParam-URL-filter.svg)](https://gitter.im/VIPnytt/CleanParam-URL-filter)

# Clean-Param URL filtering class

[![Join the chat at https://gitter.im/VIPnytt/CleanParam-URL-filter](https://badges.gitter.im/VIPnytt/CleanParam-URL-filter.svg)](https://gitter.im/VIPnytt/CleanParam-URL-filter?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
PHP class to filter URL duplicates, with integrated support for [Yandex Clean-Param specifications](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param).

## FAQ:
**What does it do?**

It filters your URL lists so that any duplicate pages are removed.

**What to expect if I'll filter my URLs?**

- You'll never have to reload duplicate information again.
- More efficient web crawling.
- Server load will decrease.

**What is Clean-Param?**

It's a robots.txt directive witch describes dynamic parameters that do not affect the page content (e.g. identifiers of sessions, users, referrers etc.). When added, it has an significant impact on the number of URLs considered as duplicates. [Learn more.](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param)

## Installation
The library is available for install via Composer package. To install via Composer, please add the requirement to your ````composer.json```` file, like this:

```json
{
	"require": {
		"VIPnytt/CleanParam-URL-Filter": "dev-master"
	}
}
```

and then use composer to load the lib:

```php
<?php
require_once('vendor/autoload.php');
$filter = new \VIPnytt\CleanParamFilter($urls);
```

You can find out more about Composer here: https://getcomposer.org/


## Usage:
````php
$filter = new \vipnytt\CleanParamFilter($urlArray);

// Optional: Add Clean-Param
$filter->addCleanParam($parameter, $path);

// List duplicates
print_r($filter->listDuplicate());

// List non-duplicates
print_r($filter->listApproved());
````
**Pro tip:** If you're going to filter tens of thousands of URLs, (or even more), it is recommended to break down the list to a bare minimum. This can be done by grouping the URLs by domain (or even host), and then filter each group individually. This is for the sake of performance!

## Problem solving:
##### Fatal error:  Maximum execution time exceeded.

Reason: You're probably trying to filter thousands of URLs.

1. It is recommended to break down the list of URLs to a bare minimum. This can be done by grouping the URLs by domain (or even host), and then filter each group individually.
2. Increase PHP's max execution time limit by using ````set_time_limit(60);````. When called, it sets the time limit to 60 seconds, and restarts the timeout counter from zero.
3. If you're already looping thou groups of URLs (like suggested), put ````set_time_limit(60);```` into the loop, so that each time a new set of URLs is parsed, the timeout counter is restarted.

##### Fatal error:  Allowed memory size of 134217728 bytes exhausted.

Reason: You're probably trying to filter tens of thousands of URLs, maybe even more.

1. At this point, you're required to break down the list of URLs to a bare minimum. This can be done by grouping the URLs by domain (or even better, host), and then filter each group individually.
2. Increase PHP's memory limit. This could be done by setting ````ini_set('memory_limit', '256M');```` or by changing the ````memory_limit```` variable in your ````php.ini```` file.