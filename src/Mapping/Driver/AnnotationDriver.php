<?php

namespace Argayash\DenormalizedOrm\Mapping\Driver;

use Argayash\DenormalizedOrm\Mapping\Annotation\DnTable;
use Argayash\DenormalizedOrm\Mapping\DnClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;

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

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return DnClassMetadata|null
     */
    public function loadMetadataForClass(ClassMetadata $classMetadata)
    {
        /** @var DnTable $tableMetadata */
        if ($tableMetadata = $this->reader->getClassAnnotation($classMetadata->reflClass, DnTable::class)) {
            if (!$tableMetadata->name) {
                $tableMetadata->name = $classMetadata->reflClass->getShortName();
            }

            return DnClassMetadata::create($classMetadata, $tableMetadata);
        }

        return null;
    }

}