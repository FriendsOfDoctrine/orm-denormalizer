<?php
namespace Argayash\DenormalizedOrm\Mapping\Driver;

use Argayash\DenormalizedOrm\Mapping\DnClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This class provides method to load custom driver.
 */
interface DriverInterface
{
    /**
     * @param ClassMetadata $classMetadata
     *
     * @return DnClassMetadata
     */
    public function loadMetadataForClass(ClassMetadata $classMetadata);
}