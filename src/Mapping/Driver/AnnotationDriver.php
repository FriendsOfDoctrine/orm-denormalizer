<?php

namespace Argayash\DenormalizedOrm\Mapping\Driver;

use Argayash\DenormalizedOrm\Mapping\Annotation\DnTable;
use Argayash\DenormalizedOrm\Mapping\DnClassMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * This class provides method to load metadata from class annotations.
 */
class AnnotationDriver implements DriverInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        /** @var DnTable $tableMetadata */
        if ($tableMetadata = $this->reader->getClassAnnotation($class, DnTable::class)) {
            if (!$tableMetadata->name) {
                $tableMetadata->name = $class->getShortName();
            }

            return new DnClassMetadata($tableMetadata);
        }

        return null;
    }

}