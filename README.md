[![Build Status](https://travis-ci.org/VIPnytt/CleanParam-URL-filter.svg?branch=master)](https://travis-ci.org/VIPnytt/CleanParam-URL-filter) [![Code Climate](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/gpa.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter) [![Test Coverage](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/coverage.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/coverage) [![License](https://poser.pugx.org/VIPnytt/CleanParam-URL-filter/license)](https://packagist.org/packages/VIPnytt/CleanParam-URL-filter) [![Join the chat at https://gitter.im/VIPnytt/CleanParam-URL-filter](https://badges.gitter.im/VIPnytt/CleanParam-URL-filter.svg)](https://gitter.im/VIPnytt/CleanParam-URL-filter)

# Clean-Param URL filtering class
PHP class to filter URL duplicates, with integrated support for [Yandex Clean-Param specifications](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param).

## FAQ:
**What does it do?**

It filters your URL lists so that any duplicate pages are removed.

**What to expect if I'll filter my URLs?**

- You'll never have to reload duplicate information again
- More efficient web crawling
- Server load will decrease

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
