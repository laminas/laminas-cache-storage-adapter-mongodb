<?php

namespace LaminasTest\Cache\Psr\CacheItemPool;

use Cache\IntegrationTests\CachePoolTest;
use Laminas\Cache\Exception;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Laminas\Cache\Storage\Adapter\MongoDb;
use Laminas\Cache\Storage\Plugin\Serializer;
use Laminas\Cache\StorageFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

class MongoDbIntegrationTest extends CachePoolTest
{
    /**
     * Backup default timezone
     * @var string
     */
    private $tz;

    /**
     * @var MongoDb
     */
    private $storage;

    protected function setUp()
    {
        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        parent::setUp();
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->tz);

        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }

    public function createCachePool()
    {
        try {
            $storage = StorageFactory::adapterFactory('mongodb', [
                'server'     => getenv('TESTS_LAMINAS_CACHE_MONGODB_CONNECTSTRING'),
                'database'   => getenv('TESTS_LAMINAS_CACHE_MONGODB_DATABASE'),
                'collection' => getenv('TESTS_LAMINAS_CACHE_MONGODB_COLLECTION'),
            ]);
            $storage->addPlugin(new Serializer());

            $deferredSkippedMessage = sprintf(
                '%s storage doesn\'t support driver deferred',
                \get_class($storage)
            );
            $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = $deferredSkippedMessage;

            return new CacheItemPoolDecorator($storage);
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (ServiceNotCreatedException $e) {
            if ($e->getPrevious() instanceof Exception\ExtensionNotLoadedException) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }
}
