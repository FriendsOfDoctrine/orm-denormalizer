<?php
/**
 *  This file is part of the FOD\OrmDenormalizer package -- Denormalizer ORM Doctrine library
 *
 *  (c) FriendsOfDoctrine <https://github.com/FriendsOfDoctrine/>.
 *
 *  For the full copyright and license inflormation, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace FOD\OrmDenormalizer\Mapping\Annotation;

/**
 * This class contains database table metadata.
 * @package FOD\OrmDenormalizer\Mapping\Annotation
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
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