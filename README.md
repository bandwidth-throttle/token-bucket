# Token Bucket

This is a threadsafe implementation of the [Token Bucket algorithm](https://en.wikipedia.org/wiki/Token_bucket)
in PHP. You can use a token bucket to limit an usage rate for a resource 
(e.g. a stream bandwidth or an API usage).

The token bucket is an abstract metapher which doesn't have a direction of
the resource consumption. I.e. you can limit a rate for consuming or producing.
E.g. you can limit the consumption rate of a third party API service, or you
can limit the usage rate of your own API service.

# Installation

Use [Composer](https://getcomposer.org/):

```sh
composer require bandwidth-throttle/token-bucket
```

# Usage

The package is in the namespace
[`bandwidthThrottle\tokenBucket`](http://bandwidth-throttle.github.io/token-bucket/api/namespace-bandwidthThrottle.tokenBucket.html).

## Example

This example will limit the rate of a global resource to 10 requests per second
for all requests.

```php
use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\TokenBucket;
use bandwidthThrottle\tokenBucket\storage\FileStorage;

$storage = new FileStorage(__DIR__ . "/api.bucket");
$rate    = new Rate(10, Rate::SECOND);
$bucket  = new TokenBucket(10, $rate, $storage);
$bucket->bootstrap(10);

if (!$bucket->consume(1, $seconds)) {
    http_response_code(429);
    header(sprintf("Retry-After: %d", floor($seconds)));
    exit();
}

echo "API response";
```

Note: In this example `TokenBucket::bootstrap()` is part of the code. This is
not recommended for production, as this is producing unnecessary storage
communication. `TokenBucket::bootstrap()` should be part of the application's
bootstrap or deploy process.

## Scope of the storage

First you need to decide the scope of your resource. I.e. do you want to limit
it per request, per user or amongst all requests? You can do this by choosing a
[`Storage`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.storage.Storage.html)
implementation of the desired scope:

- The [`RequestScope`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.storage.scope.RequestScope.html)
limits the rate only within one request. E.g. to limit the bandwidth of a download.
Each requests will have the same bandwidth limit.

- The [`SessionScope`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.storage.scope.SessionScope.html)
limits the rate of a resource within a session. The rate is controlled over
all requests of one session. E.g. to limit the API usage per user.

- The [`GlobalScope`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.storage.scope.GlobalScope.html)
limits the rate of a resource for all processes (i.e. requests). E.g. to limit
the aggregated download bandwidth of a resource over all processes. This scope
permits race conditions between processes. The TokenBucket is therefore
synchronized on a shared mutex.

## TokenBucket

When you have your storage you can finally instantiate a
[`TokenBucket`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.TokenBucket.html).
The first parameter is the capacity of the bucket. I.e. there will be never
more tokens available. This also means that consuming more tokens than the
capacity is invalid.

The second parameter is the token-add-[`Rate`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.Rate.html).
It determines the speed for filling the bucket with tokens. The rate is the
amount of tokens added per unit, e.g. `new Rate(100, Rate::SECOND)`
would add 100 tokens per second.

The third parameter is the storage, which is used to persist the token amount
of the bucket. The storage does determine the scope of the bucket.

### Bootstrapping

A token bucket needs to be bootstrapped. While the method
[`TokenBucket::bootstrap()`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.TokenBucket.html#_bootstrap)
doesn't have any side effects on an already bootstrapped bucket, it is not
recommended do call it for every request. Better include that in your
application's bootstrap or deploy process.

### Consuming

Now that you have a bootstrapped bucket, you can start consuming tokens. The
method [`TokenBucket::consume()`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.TokenBucket.html#_consume)
will either return `true` if the tokens were consumed or `false` else.
If the tokens were consumed your application can continue to serve the resource.

Else if the tokens were not consumed you should not serve the resource.
In that case `consume()` did write a duration of seconds into its second parameter
(which was passed by reference). This is the duration until sufficient
tokens would be available.

## BlockingConsumer

In the first example we did either serve the request or fail with the HTTP status
code 429. This is actually a very resource efficient way of throtteling API
requests as it doesn't reserve resources on your server.

However sometimes
it is desirable not to fail but instead wait a little bit and then continue
serving the requests. You can do this by consuming the token bucket with
a [`BlockingConsumer`](http://bandwidth-throttle.github.io/token-bucket/api/class-bandwidthThrottle.tokenBucket.BlockingConsumer.html).

```php
use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\TokenBucket;
use bandwidthThrottle\tokenBucket\BlockingConsumer;
use bandwidthThrottle\tokenBucket\storage\FileStorage;

$storage  = new FileStorage(__DIR__ . "/api.bucket");
$rate     = new Rate(10, Rate::SECOND);
$bucket   = new TokenBucket(10, $rate, $storage);
$consumer = new BlockingConsumer($bucket);
$bucket->bootstrap(10);

// This will block until one token is available.
$consumer->consume(1);

echo "API response";
```

This will effectively limit the rate to 10 requests per seconds as well. But
in this case the client has not to bother with the 429 error. Instead the
connection is just delayed to the desired rate.

# License and authors

This project is free and under the WTFPL.
Responsible for this project is Markus Malkusch markus@malkusch.de.

## Donations

If you like this project and feel generous donate a few Bitcoins here:
[1335STSwu9hST4vcMRppEPgENMHD2r1REK](bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK)

[![Build Status](https://travis-ci.org/bandwidth-throttle/token-bucket.svg?branch=master)](https://travis-ci.org/bandwidth-throttle/token-bucket)
