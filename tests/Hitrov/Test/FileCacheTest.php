<?php

namespace Hitrov\Test;

use Hitrov\FileCache;
use Hitrov\Test\Traits\DefaultConfig;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private string $configMd5;

    use DefaultConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getDefaultConfig();
                $this->configMd5 = md5(json_encode($config));

        if (file_exists($this->getCacheFilename())) {
            unlink($this->getCacheFilename());
        }
    }

    public function testGetCacheKey(): void
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $this->assertEquals(
            $this->configMd5,
            $cache->getCacheKey('foo'),
        );
    }

    public function testCacheFileCreated(): void
    {
        $config = $this->getDefaultConfig();
        $api = $this->getDefaultApi();

        $api->setCache(new FileCache($config));

        $this->assertTrue(
            file_exists(sprintf('%s/%s', getcwd(), 'oci_cache.json')),
        );
    }

    public function testAddsCacheFileContents()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $cache->add([1, 'one'], 'foo');

        $expected = json_encode(
            ['foo' => [$this->configMd5 => [1, 'one']]],
            JSON_PRETTY_PRINT
        );

        $this->assertEquals(
            $expected,
            file_get_contents($this->getCacheFilename()),
        );
    }

    public function testUpdatesCacheFileContents()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $existingData = ['foo' => [$this->configMd5 => [1, 'one']]];
        file_put_contents($this->getCacheFilename(), json_encode($existingData, JSON_PRETTY_PRINT));

        $cache->add([2, 'two'], 'bar');

        $expected = json_encode(
            ['foo' => [$this->configMd5 => [1, 'one']], 'bar' => [$this->configMd5 => [2, 'two']]],
            JSON_PRETTY_PRINT
        );

        $this->assertEquals(
            $expected,
            file_get_contents($this->getCacheFilename()),
        );
    }

    public function testUpdatesWithDifferentConfig()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $config2 = $this->getDefaultConfig();
        $config2->bootVolumeId = 'baz';
                $configMd5Two = md5(json_encode($config2));
        $cache2 = new FileCache($config2);

        $existingData = ['foo' => [$this->configMd5 => [1, 'one']]];
        file_put_contents($this->getCacheFilename(), json_encode($existingData, JSON_PRETTY_PRINT));

        $cache2->add([11, 'eleven'], 'foo');

        $expected = json_encode(
            ['foo' => [$this->configMd5 => [1, 'one'], $configMd5Two => [11, 'eleven']]],
            JSON_PRETTY_PRINT
        );

        $this->assertEquals(
            $expected,
            file_get_contents($this->getCacheFilename()),
        );
    }

    public function testGet()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $cache->add([1, 'one'], 'foo');

        $this->assertEquals(
            [1, 'one'],
            $cache->get('foo'),
        );
    }

    private function getCacheFilename(): string
    {
        return sprintf('%s/%s', getcwd(), 'oci_cache.json');
    }
}
