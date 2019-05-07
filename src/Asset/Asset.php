<?php declare(strict_types=1);

namespace Becklyn\AssetsBundle\Asset;

use Becklyn\AssetsBundle\Exception\AssetsException;
use Becklyn\AssetsBundle\File\FilePath;

class Asset
{
    const NAMESPACE_REGEX = '[a-z][a-z0-9_]*?';

    //region Fields
    /**
     * @var string
     */
    private $namespace;


    /**
     * @var string
     */
    private $filePath;


    /**
     * @var string|null
     */
    private $hash;


    /**
     * @var string|null
     */
    private $fileNameHash;


    /**
     * @var string
     */
    private $fileType;
    //endregion


    /**
     * @param string $filePath
     * @param string $hash
     */
    public function __construct (string $namespace, string $filePath)
    {
        $this->namespace = $namespace;
        $this->filePath = \ltrim($filePath, "/");
        $this->fileType = \pathinfo($filePath, \PATHINFO_EXTENSION);
    }


    //region Accessors
    /**
     * @return string
     */
    public function getNamespace () : string
    {
        return $this->namespace;
    }


    /**
     * @return string
     */
    public function getFilePath () : string
    {
        return $this->filePath;
    }


    /**
     * @return string|null
     */
    public function getHash () : ?string
    {
        return $this->hash;
    }


    /**
     * @param string|null $hash
     * @param bool        $setFileNameHash
     */
    public function setHash (?string $hash, bool $setFileNameHash = true) : void
    {
        if ($setFileNameHash && null !== $hash)
        {
            $fileNameHash = \rtrim($hash, "=");
            $fileNameHash = \strtr($fileNameHash, [
                "/" => "_",
            ]);
            $fileNameHash = \substr($fileNameHash, 0, 20);
        }
        else
        {
            $fileNameHash = null;
        }

        $this->hash = $hash;
        $this->fileNameHash = $fileNameHash;
    }


    /**
     * @return mixed
     */
    public function getFileType ()
    {
        return $this->fileType;
    }
    //endregion


    /**
     * Returns the full asset path.
     *
     * @return string
     */
    public function getAssetPath () : string
    {
        return "@{$this->getNamespace()}/{$this->getFilePath()}";
    }


    /**
     * Returns the final storage path, where the production file is dumped to.
     *
     * @return string
     */
    public function getDumpFilePath () : string
    {
        $dir = \dirname($this->filePath);
        $fileName = \basename($this->filePath, ".{$this->fileType}");

        $dir = "." === $dir
            ? ""
            : "{$dir}/";

        $hash = !empty($this->fileNameHash)
            ? ".{$this->fileNameHash}"
            : "";

        return "{$dir}{$fileName}{$hash}.{$this->fileType}";
    }


    /**
     * @param string $assetPath
     *
     * @throws AssetsException
     *
     * @return Asset
     */
    public static function createFromAssetPath (string $assetPath) : self
    {
        if (1 === \preg_match('~^@(?<namespace>' . self::NAMESPACE_REGEX . ')/(?<path>.+)$~', $assetPath, $matches))
        {
            $path = \trim($matches["path"], "/");

            if ("" === $path)
            {
                throw new AssetsException("Invalid asset path – no path given.");
            }

            if (false !== \strpos($path, ".."))
            {
                throw new AssetsException("Invalid asset path – must not contain path '..'.");
            }

            return new self($matches["namespace"], $path);
        }

        throw new AssetsException(\sprintf(
            "Can't parse asset path: '%s'",
            $assetPath
        ));
    }


    /**
     * Returns an asset at a relative path (in relation to the current asset).
     *
     * @param string $relativePath
     *
     * @return self
     */
    public function getRelativeAsset (string $relativePath) : ?self
    {
        $filePath = new FilePath();

        return new self(
            $this->getNamespace(),
            $filePath->resolvePath($this->getFilePath(), $relativePath)
        );
    }
}