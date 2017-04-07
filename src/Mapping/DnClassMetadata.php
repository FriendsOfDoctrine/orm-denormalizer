<?php
namespace Argayash\DenormalizedOrm\Mapping;

use Argayash\DenormalizedOrm\Mapping\Annotation\DnTable;

/**
 * This class contains entity class metadata.
 */
class DnClassMetadata
{
    /**
     * @var DnTable
     */
    protected $table;

    /**
     * @param DnTable $table
     */
    public function __construct(DnTable $table)
    {
        $this->table = $table;
    }

    /**
     * @return DnTable
     */
    public function getTable(): DnTable
    {
        return $this->table;
    }
}