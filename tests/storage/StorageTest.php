<?php

namespace bandwidthThrottle\tokenBucket\storage;

use org\bovigo\vfs\vfsStream;
use Redis;
use Predis\Client;
use bandwidthThrottle\tokenBucket\TokenBucket;
use bandwidthThrottle\tokenBucket\Rate;

/**
 * Tests for Storage implementations.
 *
 * If you want to run vendor specific tests you should provide these
 * environment variables:
 *
 * - MYSQL_DSN, MYSQL_USER
 * - PGSQL_DSN, PGSQL_USER
 * - MEMCACHE_HOST
 * - REDIS_URI
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see Storage
 */
class StorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Storage The tested storage;
     */
    private $storage;
    
    protected function tearDown()
    {
        if (!is_null($this->storage) && $this->storage->isBootstrapped()) {
            $this->storage->remove();
        }
    }
    
    /**
     * Provides uninitialized Storage implementations.
     *
     * @return callable[][] Storage factories.
     */
    public function provideStorageFactories()
    {
        $cases = [
            "SingleProcessStorage" => [function () {
                return new SingleProcessStorage();
            }],
            "SessionStorage" => [function () {
                return new SessionStorage("test");
            }],
            "FileStorage" => [function () {
                vfsStream::setup('fileStorage');
                return new FileStorage(vfsStream::url("fileStorage/data"));
            }],
            "sqlite" => [function () {
                $pdo = new \PDO("sqlite::memory:");
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return new PDOStorage("test", $pdo);
            }],
            "IPCStorage" => [function () {
                return new IPCStorage(ftok(__FILE__, "a"));
            }],
        ];
        
        if (getenv("MYSQL_DSN")) {
            $cases["MYSQL"] = [function () {
                $pdo = new \PDO(getenv("MYSQL_DSN"), getenv("MYSQL_USER"));
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
                return new PDOStorage("test", $pdo);
            }];
        }
        if (getenv("PGSQL_DSN")) {
            $cases["PGSQL"] = [function () {
                $pdo = new \PDO(getenv("PGSQL_DSN"), getenv("PGSQL_USER"));
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return new PDOStorage("test", $pdo);
            }];
        }
        if (getenv("MEMCACHE_HOST")) {
            $cases["MemcachedStorage"] = [function () {
                $memcached = new \Memcached();
                $memcached->addServer(getenv("MEMCACHE_HOST"), 11211);
                return new MemcachedStorage("test", $memcached);
            }];
        }
        if (getenv("REDIS_URI")) {
            $cases["PHPRedisStorage"] = [function () {
                $uri   = parse_url(getenv("REDIS_URI"));
                $redis = new Redis();
                $redis->connect($uri["host"]);
                return new PHPRedisStorage("test", $redis);
            }];

            $cases["PredisStorage"] = [function () {
                $redis = new Client(getenv("REDIS_URI"));
                return new PredisStorage("test", $redis);
            }];
        }
        return $cases;
    }
    
    /**
     * Tests setMicrotime() and getMicrotime().
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testSetAndGetMicrotime(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);
        $this->storage->bootstrap(1);
        $this->storage->getMicrotime();
        
        $this->storage->setMicrotime(1.1);
        $this->assertSame(1.1, $this->storage->getMicrotime());
        $this->assertSame(1.1, $this->storage->getMicrotime());
        
        $this->storage->setMicrotime(1.2);
        $this->assertSame(1.2, $this->storage->getMicrotime());
        
        $this->storage->setMicrotime(1436551945.0192);
        $this->assertSame(1436551945.0192, $this->storage->getMicrotime());
    }
    
    /**
     * Tests isBootstrapped().
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testBootstrap(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);

        $this->storage->bootstrap(123);
        $this->assertTrue($this->storage->isBootstrapped());
        $this->assertEquals(123, $this->storage->getMicrotime());
    }
    
    /**
     * Tests isBootstrapped().
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testIsBootstrapped(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);
        $this->assertFalse($this->storage->isBootstrapped());

        $this->storage->bootstrap(123);
        $this->assertTrue($this->storage->isBootstrapped());

        $this->storage->remove();
        $this->assertFalse($this->storage->isBootstrapped());
    }
    
    /**
     * Tests remove().
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testRemove(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);
        $this->storage->bootstrap(123);

        $this->storage->remove();
        $this->assertFalse($this->storage->isBootstrapped());
    }
    
    /**
     * When no tokens are available, the bucket should return false.
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testConsumingUnavailableTokensReturnsFalse(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);
        $capacity = 10;
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket($capacity, $rate, $this->storage);
        $bucket->bootstrap(0);

        $this->assertFalse($bucket->consume(10));
    }
    
    /**
     * When tokens are available, the bucket should return true.
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testConsumingAvailableTokensReturnsTrue(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);
        $capacity = 10;
        $rate = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket($capacity, $rate, $this->storage);
        $bucket->bootstrap(10);

        $this->assertTrue($bucket->consume(10));
    }
    
    /**
     * Tests synchronized bootstrap
     *
     * @param callable $storageFactory Returns a storage.
     * @test
     * @dataProvider provideStorageFactories
     */
    public function testSynchronizedBootstrap(callable $storageFactory)
    {
        $this->storage = call_user_func($storageFactory);
        $this->storage->getMutex()->synchronized(function () {
            $this->assertFalse($this->storage->isBootstrapped());
            $this->storage->bootstrap(123);
            $this->assertTrue($this->storage->isBootstrapped());
        });
    }
}
