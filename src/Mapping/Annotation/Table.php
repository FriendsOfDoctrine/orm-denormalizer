<?php
namespace FOD\OrmDenormalizer\Mapping\Annotation;


/**
 * This class contains database table metadata.
 *
 * @Annotation
 * @Target("CLASS")
 */
class Table implements DnAnnotation
{
    const CLASSNAME = __CLASS__;
    const DENORMALIZE_TABLE_DELIMITER = '__';
    const DENORMALIZE_FIELD_DELIMITER = '_';

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