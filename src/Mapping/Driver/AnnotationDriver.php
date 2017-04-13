<?php

namespace FOD\OrmDenormalized\Mapping\Driver;

use FOD\OrmDenormalized\Mapping\Annotation\DnTable;
use FOD\OrmDenormalized\Mapping\DnClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This class provides method to load metadata from class annotations.
 */
class AnnotationDriver
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param Reader $reader
     */
    protected function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param Reader $reader
     *
     * @return AnnotationDriver
     */
    public static function getInstance(Reader $reader)
    {
        return new self($reader);
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return DnClassMetadata|null
     */
    public function loadMetadataForClass(ClassMetadata $classMetadata)
    {
        /** @var DnTable $tableMetadata */
        if ($tableMetadata = $this->reader->getClassAnnotation($classMetadata->reflClass, DnTable::CLASSNAME)) {
            if (!$tableMetadata->name) {
                $tableMetadata->name = $classMetadata->reflClass->getShortName();
            }

            return DnClassMetadata::create($classMetadata, $tableMetadata);
        }

        return null;
    }

}