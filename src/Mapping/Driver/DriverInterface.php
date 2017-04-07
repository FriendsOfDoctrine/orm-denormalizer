<?php
namespace Argayash\DenormalizedOrm\Mapping\Driver;

use Argayash\DenormalizedOrm\Mapping\DnClassMetadata;

/**
 * This class provides method to load custom driver.
 */
interface DriverInterface
{
    /**
     * @param \ReflectionClass $class
     *
     * @return DnClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class);
}