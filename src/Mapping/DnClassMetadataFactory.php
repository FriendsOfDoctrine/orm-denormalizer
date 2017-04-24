<?php
/**
 *  This file is part of the FOD\OrmDenormalizer package -- Denormalizer ORM Doctrine library
 *
 *  (c) FriendsOfDoctrine <https://github.com/FriendsOfDoctrine/>.
 *
 *  For the full copyright and license inflormation, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace FOD\OrmDenormalizer\Mapping;

use FOD\OrmDenormalizer\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * This class provides method to load entity class metadata.
 * @package FOD\OrmDenormalizer\Mapping
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
 */
class DnClassMetadataFactory
{
    /**
     * @var array
     */
    protected $loaded = [];

    /**
     * @var AnnotationDriver
     */
    protected $driver;

    /**
     * @param Reader $reader
     */
    protected function __construct(Reader $reader)
    {
        $this->driver = AnnotationDriver::getInstance($reader);
    }

    /**
     * @param Reader $reader
     *
     * @return DnClassMetadataFactory
     */
    public static function getInstance(Reader $reader)
    {
        return new self($reader);
    }

    /**
     * @param ClassMetadata $ormClassMetadata
     *
     * @return DnClassMetadata|mixed|null
     * @throws \Exception
     */
    public function loadMetadata(ClassMetadata $ormClassMetadata)
    {
        $classMetadata = null;

        if (!array_key_exists($ormClassMetadata->name, $this->loaded) && ($classMetadata = $this->driver->loadMetadataForClass($ormClassMetadata))) {
            return $this->loaded[$ormClassMetadata->name] = $classMetadata;
        }

        return isset($this->loaded[$ormClassMetadata->name]) ? $this->loaded[$ormClassMetadata->name] : null;
    }
}