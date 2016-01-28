[![Build Status](https://travis-ci.org/VIPnytt/CleanParam-URL-filter.svg?branch=master)](https://travis-ci.org/VIPnytt/CleanParam-URL-filter) [![Code Climate](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/gpa.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter) [![Test Coverage](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/badges/coverage.svg)](https://codeclimate.com/github/VIPnytt/CleanParam-URL-filter/coverage) [![License](https://poser.pugx.org/VIPnytt/CleanParam-URL-filter/license)](https://packagist.org/packages/VIPnytt/CleanParam-URL-filter) [![Join the chat at https://gitter.im/VIPnytt/CleanParam-URL-filter](https://badges.gitter.im/VIPnytt/CleanParam-URL-filter.svg)](https://gitter.im/VIPnytt/CleanParam-URL-filter)

# Clean-Param URL filtering class
PHP class to filter URL duplicates, with support for [Yandex Clean-Param specifications](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param).

## FAQ:
**What does it do?**
> It filters your URL lists so that any duplicate pages are removed.

**What to expect if I'll filter my URLs?**
> - You'll never have to reload duplicate information again
> - Server load will decrease
> - More effecient web crawling

**What is the Clean-Param directive?**
> It is a way to tell web crawlers and robots about dynamic parameters that do not affect the page content (e.g. identifiers of sessions, users, referrers etc.) [Learn more!](https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param)

## Usage:
````
$filter = new \vipnytt\CleanParamFilter($urlArray);

// Optional: Clean-Param
$filter->addCleanParam($parameter, $path);

// List duplicates
$filter->listDuplicate();

// List non-duplicates
$filter->listApproved();
````

## Schedule:
The class is currently in development, so expect things to break.

First Beta version is scheduled for release by the end of February 2016.