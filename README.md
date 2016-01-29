[![Build Status](https://travis-ci.org/VIPnytt/CleanParam-URL-filter.svg?branch=master)](https://travis-ci.org/VIPnytt/CleanParam-URL-filter) [![Code Climate](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/gpa.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter) [![Test Coverage](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/coverage.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/coverage) [![License](https://poser.pugx.org/VIPnytt/CleanParam-URL-filter/license)](https://packagist.org/packages/VIPnytt/CleanParam-URL-filter) [![Join the chat at https://gitter.im/VIPnytt/CleanParam-URL-filter](https://badges.gitter.im/VIPnytt/CleanParam-URL-filter.svg)](https://gitter.im/VIPnytt/CleanParam-URL-filter)

# Clean-Param URL filtering class
PHP class to filter URL duplicates, with support for [Yandex Clean-Param specifications](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param).

## Installation
The library is available for install via Composer package. To install via Composer, please add the requirement to your `composer.json` file, like this:

```json
{
    "require": {
        "vipnytt/cleanparam-url-filter": "dev-master"
    }
}
```

and then use composer to load the lib:

```php
<?php
    require 'vendor/autoload.php';
    $filter = new \vipnytt\CleanParamFilter($urls);
    ...
```

You can find out more about Composer here: https://getcomposer.org/

## FAQ:
**What does it do?**
> It filters your URL lists so that any duplicate pages are removed.

**What to expect if I'll filter my URLs?**
> - You'll never have to reload duplicate information again
> - More efficient web crawling
> - Server load will decrease

**What is Clean-Param?**
> It's a directive witch tells web crawlers, spiders and robots about dynamic parameters in the URLs that do not affect the page content (e.g. identifiers of sessions, users, referrers etc.) [Learn more!](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param)

> If an website have added support for it, you'll find it in the robots.txt file. Providing these parameters is optional, but has an significant impact on the number of URLs considered as duplicates.

## Usage:
````
$filter = new \vipnytt\CleanParamFilter($urlArray);

// Optional: Add Clean-Param
$filter->addCleanParam($parameter, $path);

// List duplicates
$filter->listDuplicate();

// List non-duplicates
$filter->listApproved();
````

## Schedule:
The class is currently in development, so expect things to break.

First Beta version is scheduled for release by the end of February 2016.