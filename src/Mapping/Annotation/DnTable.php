<?php
namespace FOD\DoctrineOrmDenormalized\Mapping\Annotation;


/**
 * This class contains database table metadata.
 *
 * @Annotation
 * @Target("CLASS")
 */
class DnTable implements DnAnnotation
{
    const CLASSNAME = __CLASS__;
    const DENORMALIZE_TABLE_DELIMITER = '___';
    const DENORMALIZE_FIELD_DELIMITER = '__';

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $tablePrefix = '';
    /**
     * @var string
     */
    public $fieldsPrefix = '';
    /**
     * @var array
     */
    public $excludeFields = [];
}