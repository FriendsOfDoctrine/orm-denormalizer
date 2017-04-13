<?php
namespace FOD\OrmDenormalizer\Mapping;

use FOD\OrmDenormalizer\Mapping\Annotation\DnTable;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This class contains entity class metadata.
 */
class DnClassMetadata
{
    /**
     * @var DnTable
     */
    protected $dnTable;

    /** @var  ClassMetadata */
    protected $classMetadata;

    /**
     * DnClassMetadata constructor.
     *
     * @param ClassMetadata $classMetadata
     * @param DnTable $dnTable
     */
    protected function __construct(ClassMetadata $classMetadata, DnTable $dnTable)
    {
        $this->classMetadata = $classMetadata;
        $this->dnTable = $dnTable;
    }

    /**
     * @return DnTable
     */
    public function getDnTable()
    {
        return $this->dnTable;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param DnTable $dnTable
     *
     * @return DnClassMetadata
     */
    public static function create(ClassMetadata $classMetadata, DnTable $dnTable)
    {
        return new self($classMetadata, $dnTable);
    }
}