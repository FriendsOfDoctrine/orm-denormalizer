<?php
namespace FOD\OrmDenormalized\Listeners;


use FOD\OrmDenormalized\DnTableGroup;
use FOD\OrmDenormalized\DnTableGroupContainer;
use FOD\OrmDenormalized\Mapping\DnClassMetadata;
use FOD\OrmDenormalized\Mapping\DnClassMetadataFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Class LoadClassMetadataListener
 * @package AppBundle\EventListener
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
            $dependsEntities = [];

            /** @var ClassMetadata $classMetadata */
            foreach ($this->em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                if ($dnClassMetadata = $this->classMetadataFactory->loadMetadata($classMetadata)) {
                    $this->dnClassesMetadata[$classMetadata->name] = $dnClassMetadata;
                }
            }
            foreach ($this->dnClassesMetadata as $dnClassMetadata) {
                foreach ($dnClassMetadata->getClassMetadata()->getAssociationMappings() as $association) {
                    if (!empty($association['joinColumns']) && isset($this->dnClassesMetadata[$association['targetEntity']])) {
                        /** Many-One */
                        $group[$dnClassMetadata->getClassMetadata()->name][$association['fieldName']] = $association['targetEntity'];
                        $dependsEntities[] = $association['targetEntity'];
                    }
                }
            }

            foreach (array_filter($group, function ($key) use ($dependsEntities) {
                return !in_array($key, $dependsEntities, true);
            }, ARRAY_FILTER_USE_KEY) as $firstEntityName => $mappingEntities) {
                if (isset($group[$firstEntityName])) {
                    $this->container->add(new DnTableGroup($this->getEntityGroupSchema($firstEntityName, $group[$firstEntityName], $group), $this->dnClassesMetadata));
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
            if ($firstClass !== $relationClass) {
                $relation[$firstClass][$field] = $relationClass;
                if (isset($classesRelation[$relationClass])) {
                    $relation += $this->getEntityGroupSchema($relationClass, $classesRelation[$relationClass], $classesRelation);
                }
            }
        }

        return $relation;
    }
}