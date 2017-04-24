<?php
/**
 *  This file is part of the FOD\OrmDenormalizer package -- Denormalizer ORM Doctrine library
 *
 *  (c) FriendsOfDoctrine <https://github.com/FriendsOfDoctrine/>.
 *
 *  For the full copyright and license inflormation, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace FOD\OrmDenormalizer\Listeners;

use FOD\OrmDenormalizer\DnTableGroup;
use FOD\OrmDenormalizer\DnTableGroupContainer;
use FOD\OrmDenormalizer\Mapping\DnClassMetadata;
use FOD\OrmDenormalizer\Mapping\DnClassMetadataFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Class LoadClassMetadataListener
 * @package FOD\OrmDenormalizer\Listeners
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
 */
class LoadClassMetadataListener
{
    /** @var  EntityManager */
    protected $em;
    /** @var  DnTableGroupContainer */
    protected $container;
    /** @var  DnClassMetadataFactory */
    protected $classMetadataFactory;
    /**
     * @var DnClassMetadata[]
     */
    protected $dnClassesMetadata = [];

    /**
     * LoadClassMetadataListener constructor.
     *
     * @param DnTableGroupContainer $container
     * @param Reader $reader
     */
    public function __construct(DnTableGroupContainer $container, Reader $reader)
    {
        $this->classMetadataFactory = DnClassMetadataFactory::getInstance($reader);
        $this->container = $container;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @throws \Exception
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        if (null === $this->em) {
            $this->em = $eventArgs->getEntityManager();

            $group = [];
            $oneToManyRelations = [];

            /** @var ClassMetadata $classMetadata */
            foreach ($this->em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                if ($dnClassMetadata = $this->classMetadataFactory->loadMetadata($classMetadata)) {
                    $this->dnClassesMetadata[$classMetadata->name] = $dnClassMetadata;
                }
            }
            foreach ($this->dnClassesMetadata as $dnClassMetadata) {
                foreach ($dnClassMetadata->getClassMetadata()->getAssociationMappings() as $association) {
                    if (!empty($association['joinColumns']) && isset($this->dnClassesMetadata[$association['targetEntity']])) {
                        /** Many-to-One */
                        $group[$dnClassMetadata->getClassMetadata()->name][$association['fieldName']] = $association['targetEntity'];
                    } else {
                        /** One-to-Many */
                        $oneToManyRelations[$association['sourceEntity']][$association['fieldName']] = $association['targetEntity'];
                    }
                }
            }

            foreach ($group as $firstEntityName => $mappingEntities) {
                if (!isset($oneToManyRelations[$firstEntityName])) {
                    $this->container->add(
                        new DnTableGroup(
                            $this->getEntityGroupSchema($firstEntityName, $group[$firstEntityName], $group),
                            $this->dnClassesMetadata,
                            $oneToManyRelations
                        )
                    );
                }
            }
        }
    }

    /**
     * @param string $firstClass
     * @param array $classRelation
     * @param array $classesRelation
     *
     * @return array
     */
    protected function getEntityGroupSchema($firstClass, array $classRelation, array $classesRelation)
    {
        $relation = [];

        foreach ($classRelation as $field => $relationClass) {
            $relation[][$firstClass][$field] = $relationClass;
            if ($firstClass !== $relationClass && isset($classesRelation[$relationClass])) {
                $relation[] = $this->getEntityGroupSchema($relationClass, $classesRelation[$relationClass], $classesRelation);
            }
        }

        return $relation ? call_user_func_array('array_merge', $relation) : [];
    }
}