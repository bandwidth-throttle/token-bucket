<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\Storage;
use malkusch\lock\mutex\TransactionalMutex;
use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;

/**
 * PDO based storage which can be shared over a common DBS.
 *
 * This storage is in the global scope.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class PDOStorage implements Storage, GlobalScope
{

    /**
     * @var PDO The pdo.
     */
    private $pdo;
    
    /**
     * @var string The shared name of the token bucket.
     */
    private $name;
    
    /**
     * @var TransactionalMutex The mutex.
     */
    private $mutex;
    
    /**
     * Sets the PDO and the bucket's name for the shared storage.
     *
     * The name should be the same for all token buckets which share the same
     * token storage.
     *
     * The transaction isolation level should avoid lost updates, i.e. it should
     * be at least Repeatable Read.
     *
     * @param string $name The name of the token bucket.
     * @param PDO    $pdo  The PDO.
     *
     * @throws \LengthException          The id should not be longer than 128 characters.
     * @throws \InvalidArgumentException PDO must be configured to throw exceptions.
     */
    public function __construct($name, \PDO $pdo)
    {
        if (strlen($name) > 128) {
            throw new \LengthException("The name should not be longer than 128 characters.");
        }
        if ($pdo->getAttribute(\PDO::ATTR_ERRMODE) !== \PDO::ERRMODE_EXCEPTION) {
            throw new \InvalidArgumentException("The pdo must have PDO::ERRMODE_EXCEPTION set.");
        }
        $this->pdo   = $pdo;
        $this->name  = $name;
        $this->mutex = new TransactionalMutex($pdo);
    }
    
    public function bootstrap($microtime)
    {
        try {
            try {
                $this->onErrorRollback(function () {
                    $options = $this->forVendor(["mysql" => "ENGINE=InnoDB CHARSET=utf8"]);
                    $this->pdo->exec(
                        "CREATE TABLE TokenBucket (
                            name      VARCHAR(128)     PRIMARY KEY,
                            microtime DOUBLE PRECISION NOT NULL
                         ) $options;"
                    );
                });
            } catch (\PDOException $e) {
                /*
                 * This exception is ignored to provide a portable way
                 * to create a table only if it doesn't exist yet.
                 */
            }

            $insert = $this->pdo->prepare(
                "INSERT INTO TokenBucket (name, microtime) VALUES (?, ?)"
            );
            $insert->execute([$this->name, $microtime]);
            if ($insert->rowCount() !== 1) {
                throw new StorageException("Failed to insert token bucket into storage '$this->name'");
            }
        } catch (\PDOException $e) {
            throw new StorageException("Failed to bootstrap storage '$this->name'", 0, $e);
        }
    }
    
    public function isBootstrapped()
    {
        try {
            return $this->onErrorRollback(function () {
                return (bool) $this->querySingleValue(
                    "SELECT 1 FROM TokenBucket WHERE name=?",
                    [$this->name]
                );
            });
        } catch (StorageException $e) {
            // This seems to be a portable way to determine if the table exists or not.
            return false;
        } catch (\PDOException $e) {
            throw new StorageException("Can't check bootstrapped state", 0, $e);
        }
    }

    public function remove()
    {
        try {
            $delete = $this->pdo->prepare("DELETE FROM TokenBucket WHERE name = ?");
            $delete->execute([$this->name]);

            $count = $this->querySingleValue("SELECT count(*) FROM TokenBucket");
            if ($count == 0) {
                $this->pdo->exec("DROP TABLE TokenBucket");
            }
        } catch (\PDOException $e) {
            throw new StorageException("Failed to remove the storage.", 0, $e);
        }
    }

    public function setMicrotime($microtime)
    {
        try {
            $update = $this->pdo->prepare(
                "UPDATE TokenBucket SET microtime = ? WHERE name = ?"
            );
            $update->execute([$microtime, $this->name]);
        } catch (\PDOException $e) {
            throw new StorageException("Failed to write to storage '$this->name'.", 0, $e);
        }
    }
    
    public function getMicrotime()
    {
        $forUpdate = $this->forVendor(["sqlite" => ""], "FOR UPDATE");
        return (double) $this->querySingleValue(
            "SELECT microtime from TokenBucket WHERE name = ? $forUpdate",
            [$this->name]
        );
    }

    /**
     * Returns a vendor specific dialect value.
     *
     * @param string[] $map     The vendor dialect map.
     * @param string   $default The default value, which is empty per default.
     *
     * @return string The vendor specific value.
     */
    private function forVendor(array $map, $default = "")
    {
        $vendor = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return isset($map[$vendor]) ? $map[$vendor] : $default;
    }
    
    /**
     * Returns one value from a query.
     *
     * @param string $sql        The SQL query.
     * @param array  $parameters The optional query parameters.
     *
     * @return string The value.
     * @throws StorageException The query failed.
     */
    private function querySingleValue($sql, $parameters = [])
    {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);

            $value = $statement->fetchColumn();

            $statement->closeCursor();
            if ($value === false) {
                throw new StorageException("The query returned no result.");
            }
            return $value;
        } catch (\PDOException $e) {
            throw new StorageException("The query failed.", 0, $e);
        }
    }

    /**
     * Rollback to an implicit savepoint.
     *
     * @throws \PDOException
     */
    private function onErrorRollback(callable $code)
    {
        if (!$this->pdo->inTransaction()) {
            return call_user_func($code);
        }
        
        $this->pdo->exec("SAVEPOINT onErrorRollback");
        try {
            $result = call_user_func($code);
        } catch (\Exception $e) {
            $this->pdo->exec("ROLLBACK TO SAVEPOINT onErrorRollback");
            throw $e;
        }
        $this->pdo->exec("RELEASE SAVEPOINT onErrorRollback");
        return $result;
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
