<?php

namespace bandwidthThrottle\tokenBucket\storage;

use org\bovigo\vfs\vfsStream;
use Redis;
use Predis\Client;

/**
 * Tests for shared Storage implementations.
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
class SharedStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Storages Tests storages.
     */
    private $storages = [];
    
    protected function tearDown()
    {
        foreach ($this->storages as $storage) {
            try {
                @$storage->remove();
            } catch (StorageException $e) {
                // ignore missing vfsStream files.
            }
        }
    }
    
    /**
     * Provides shared Storage implementations.
     *
     * @return callable[][] Storage factories.
     */
    public function provideStorageFactories()
    {
        $cases = [
            [function ($name) {
                return new SessionStorage($name);
            }],

            [function ($name) {
                vfsStream::setup('fileStorage');
                return new FileStorage(vfsStream::url("fileStorage/$name"));
            }],

            [function ($name) {
                $key = ftok(__FILE__, $name);
                return new IPCStorage($key);
            }],

            [function ($name) {
                $pdo = new \PDO("sqlite::memory:");
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return new PDOStorage($name, $pdo);
            }],
        ];
        
        if (getenv("MYSQL_DSN")) {
            $cases[] = [function ($name) {
                $pdo = new \PDO(getenv("MYSQL_DSN"), getenv("MYSQL_USER"));
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
                
                $storage = new PDOStorage($name, $pdo);
                
                $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
                
                return $storage;
            }];
        }
        if (getenv("PGSQL_DSN")) {
            $cases[] = [function ($name) {
                $pdo = new \PDO(getenv("PGSQL_DSN"), getenv("PGSQL_USER"));
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return new PDOStorage($name, $pdo);
            }];
        }
        if (getenv("MEMCACHE_HOST")) {
            $cases[] = [function ($name) {
                $memcached = new \Memcached();
                $memcached->addServer(getenv("MEMCACHE_HOST"), 11211);
                return new MemcachedStorage($name, $memcached);
            }];
        }
        if (getenv("REDIS_URI")) {
            $cases["PHPRedisStorage"] = [function ($name) {
                $uri   = parse_url(getenv("REDIS_URI"));
                $redis = new Redis();
                $redis->connect($uri["host"]);
                return new PHPRedisStorage($name, $redis);
            }];
            
            $cases["PredisStorage"] = [function ($name) {
                $redis = new Client(getenv("REDIS_URI"));
                return new PredisStorage($name, $redis);
            }];
        }
        return $cases;
    }
    
    /**
     * Tests two storages with different names don't interfere each other.
     *
     * @param callable $factory The storage factory.
     *
     * @dataProvider provideStorageFactories
     * @test
     */
    public function testStoragesDontInterfere(callable $factory)
    {
        $storageA = call_user_func($factory, "A");
        $storageA->bootstrap(0);
        $storageA->getMicrotime();
        $this->storages[] = $storageA;

        $storageB = call_user_func($factory, "B");
        $storageB->bootstrap(0);
        $storageB->getMicrotime();
        $this->storages[] = $storageB;
        
        $storageA->setMicrotime(1);
        $storageB->setMicrotime(2);
        
        $this->assertNotEquals($storageA->getMicrotime(), $storageB->getMicrotime());
    }
}
