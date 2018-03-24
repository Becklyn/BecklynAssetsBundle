<?php

namespace Tests\Becklyn\AssetsBundle\Storage;

use Becklyn\AssetsBundle\Asset\Asset;
use Becklyn\AssetsBundle\File\FileLoader;
use Becklyn\AssetsBundle\File\FileTypeRegistry;
use Becklyn\AssetsBundle\File\Type\FileType;
use Becklyn\AssetsBundle\File\Type\GenericFile;
use Becklyn\AssetsBundle\Namespaces\NamespaceRegistry;
use Becklyn\AssetsBundle\Storage\AssetStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;


class AssetStorageTest extends TestCase
{
    /**
     * @var AssetStorage
     */
    private $storage;


    /**
     * @var string
     */
    private $fixtures;


    /**
     * @var string
     */
    private $outDir;


    /**
     * @inheritdoc
     */
    public function setUp ()
    {
        $this->fixtures = dirname(__DIR__) . "/fixtures/public";
        $this->outDir = "{$this->fixtures}/out";

        $namespaces = new NamespaceRegistry($this->fixtures, [
            "bundles" => "bundles",
            "other" => "other",
        ]);

        $fileTypeRegistry = new FileTypeRegistry(new GenericFile(), [
            "js" => new class extends FileType {
                public function shouldIncludeHashInFileName () : bool
                {
                    return false;
                }
            },
        ]);

        $this->storage = new AssetStorage(
            new FileLoader($namespaces, $fileTypeRegistry),
            $fileTypeRegistry,
            $this->outDir,
            "assets"
        );

        $fs = new Filesystem();
        $fs->remove($this->outDir);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown ()
    {
        $fs = new Filesystem();
        $fs->remove($this->outDir);
    }


    /**
     * Test file import with hashed file name
     */
    public function testGenerate ()
    {
        $expectedOutputFilePath = "test/css/app2.zu+_RiyZqaqqHgSHa3Xv.css";
        $outputPath = "{$this->outDir}/assets/other/{$expectedOutputFilePath}";

        self::assertFileNotExists($outputPath);
        $asset = $this->storage->import(new Asset("other", "test/css/app2.css"));
        self::assertFileExists($outputPath);

        self::assertSame("zu+/RiyZqaqqHgSHa3Xv6DI8rZax0+hDMV0WQk8xEZc=", $asset->getHash());
        self::assertSame($expectedOutputFilePath, $asset->getDumpFilePath());

        self::assertFileEquals(
            "{$this->fixtures}/other/test/css/app2.css",
            $outputPath
        );
    }


    /**
     * Tests the file generation with a file type that should not include hashes in the file name
     */
    public function testGenerateWithoutHash ()
    {
        $expectedOutputFilePath = "test/js/test.js";
        $outputPath = "{$this->outDir}/assets/bundles/{$expectedOutputFilePath}";

        self::assertFileNotExists($outputPath);
        $asset = $this->storage->import(new Asset("bundles", "test/js/test.js"));
        self::assertFileExists($outputPath);

        self::assertSame("47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU=", $asset->getHash());
        self::assertSame($expectedOutputFilePath, $asset->getDumpFilePath());

        self::assertFileEquals(
            "{$this->fixtures}/bundles/test/js/test.js",
            $outputPath
        );
    }


    public function testBundleStripping ()
    {
        $asset = $this->storage->import(new Asset("bundles", "test/css/app.css"));

        self::assertSame("U9K1d1vkqVvk8f9j82mik2tMIxI8E4C/QlXS/T6qgeE=", $asset->getHash());
        self::assertSame("test/css/app.U9K1d1vkqVvk8f9j82mi.css", $asset->getDumpFilePath());
    }


    public function testClear ()
    {
        $fs = new Filesystem();
        $assetsDir = "{$this->outDir}/assets";
        $fs->mkdir("{$assetsDir}/test");
        $fs->dumpFile("{$assetsDir}/test/a", "test");

        self::assertFileExists("{$assetsDir}/test/a");
        self::assertDirectoryExists($assetsDir);
        $this->storage->removeAllStoredFiles();
        self::assertDirectoryNotExists($assetsDir);
    }
}