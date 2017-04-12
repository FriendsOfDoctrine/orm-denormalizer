<?php
namespace Argayash\DenormalizedOrm\Mapping;

use Argayash\DenormalizedOrm\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadata;

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
     * @var AnnotationDriver
     */
    private $driver;

    /**
     * DnClassMetadataFactory constructor.
     * @param AnnotationDriver $driver
     */
    public function __construct(AnnotationDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param ClassMetadata $ormClassMetadata
     *
     * @return DnClassMetadata|mixed|null
     * @throws \Exception
     */
    public function loadMetadata(ClassMetadata $ormClassMetadata)
    {
        $classMetadata = null;

        if (!array_key_exists($ormClassMetadata->name, $this->loaded) && ($classMetadata = $this->driver->loadMetadataForClass($ormClassMetadata))) {
            return $this->loaded[$ormClassMetadata->name] = $classMetadata;
        }

        return $this->loaded[$ormClassMetadata->name]??null;
    }
}