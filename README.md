# Tocken Bucket

This is an implementation of the [Token Bucket algorithm](https://en.wikipedia.org/wiki/Token_bucket)
in PHP.

# Installation

Use [Composer](https://getcomposer.org/):

```sh
composer require bandwidth-throttle/token-bucket
```

# Usage

The package is in the namespace
[`bandwidthThrottle\tokenBucket`](http://bandwidth-throttle.github.io/token-bucket/api/namespace-bandwidthThrottle.tokenBucket.html).

## Example

```php
<?php

use bandwidthThrottle\tokenBucket\TokenBucket;
use bandwidthThrottle\tokenBucket\storage\FileStorage;

$storage = new FileStorage(__DIR__ . "/api.bucket");
$bucket  = new TokenBucket(10, 1000, $storage);
$bucket->bootstrap(10);

if (!$bucket->consume(1, $seconds)) {
    http_response_code(429);
    header(sprintf("Retry-After: %d", floor($seconds)));
    exit();
}
```

# License and authors

This project is free and under the WTFPL.
Responsible for this project is Markus Malkusch markus@malkusch.de.

## Donations

If you like this project and feel generous donate a few Bitcoins here:
[1335STSwu9hST4vcMRppEPgENMHD2r1REK](bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK)

[![Build Status](https://travis-ci.org/bandwidth-throttle/token-bucket.svg?branch=master)](https://travis-ci.org/bandwidth-throttle/token-bucket)
