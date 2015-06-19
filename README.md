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
Use [`TokenBucketBuilder`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.TokenBucketBuilder.html)
to build a [`TokenBucket`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.TokenBucket.html).
You can then consume tokens with
[`TokenBucket::consume()`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.TokenBucket.html#_consume).

## Example

```php
<?php

use bandwidthThrottle\tokenBucket\TokenBucketBuilder;

// Build a token bucket with a capacity of 10MiB and a rate of 1 MiB/s.
$builder = new TokenBucketBuilder();
$builder->setRateInMiBPerSecond(1);
$builder->setCapacityInMiB(10);

$tokenBucket = $builder->build();

// Consume 1024 tokens.
$tokenBucket->consume(1024);
```

# License and authors

This project is free and under the WTFPL.
Responsible for this project is Markus Malkusch markus@malkusch.de.

## Donations

If you like this project and feel generous donate a few Bitcoins here:
[1335STSwu9hST4vcMRppEPgENMHD2r1REK](bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK)

[![Build Status](https://travis-ci.org/bandwidth-throttle/token-bucket.svg?branch=master)](https://travis-ci.org/bandwidth-throttle/token-bucket)
