<?php
namespace FOD\OrmDenormalizer\Mapping;

use FOD\OrmDenormalizer\Mapping\Annotation\Table;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This class contains entity class metadata.
 */
class DnClassMetadata
{
    /**
     * @var Table
     */
    protected $dnTable;

    /** @var  ClassMetadata */
    protected $classMetadata;

    /**
     * DnClassMetadata constructor.
     *
     * @param ClassMetadata $classMetadata
     * @param Table $dnTable
     */
    protected function __construct(ClassMetadata $classMetadata, Table $dnTable)
    {
        $this->classMetadata = $classMetadata;
        $this->dnTable = $dnTable;
    }

    /**
     * @return Table
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
     * @param Table $dnTable
     *
     * @return DnClassMetadata
     */
    public static function create(ClassMetadata $classMetadata, Table $dnTable)
    {
        return new self($classMetadata, $dnTable);
    }
}