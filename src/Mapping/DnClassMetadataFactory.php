<?php
namespace Argayash\DenormalizedOrm\Mapping;

use Argayash\DenormalizedOrm\Mapping\Driver\DriverInterface;

/**
 * This class provides method to load entity class metadata.
 */
class DnClassMetadataFactory
{
    /**
     * @var array
     */
    private $loaded = [];

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param $className
     *
     * @return DnClassMetadata|mixed|null
     * @throws \Exception
     */
    public function loadMetadata($className)
    {
        $classMetadata = null;

        if (!class_exists($className)) {
            throw new \Exception(sprintf('Class "%s" does not exists.', $className));
        }

        if (!array_key_exists($className, $this->loaded) && ($classMetadata = $this->driver->loadMetadataForClass(new \ReflectionClass($className)))) {
            return $this->loaded[$className] = $classMetadata;
        }

        return $this->loaded[$className]??null;
    }
}