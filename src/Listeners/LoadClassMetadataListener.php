<?php
namespace Argayash\DenormalizedOrm\Listeners;


use Argayash\DenormalizedOrm\DnTableGroup;
use Argayash\DenormalizedOrm\DnTableGroupContainer;
use Argayash\DenormalizedOrm\Mapping\DnClassMetadata;
use Argayash\DenormalizedOrm\Mapping\DnClassMetadataFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

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
     * MetadataLoader constructor.
     *
     * @param DnClassMetadataFactory $dnClassMetadataFactory
     * @param DnTableGroupContainer $container
     */
    public function __construct(DnClassMetadataFactory $dnClassMetadataFactory, DnTableGroupContainer $container)
    {
        $this->container = $container;
        $this->classMetadataFactory = $dnClassMetadataFactory;
    }

    /**
     * @param \Doctrine\ORM\Event\LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(\Doctrine\ORM\Event\LoadClassMetadataEventArgs $eventArgs)
    {
        if (null === $this->em) {
            $this->em = $eventArgs->getEntityManager();
            $this->load();
        }
    }

    protected function load()
    {
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
                if ($this->dnClassesMetadata[$association['targetEntity']]??null) {
                    if (!empty($association['joinColumns'])) {
                        /** Many-One */
                        $group[$dnClassMetadata->getClassMetadata()->name][$association['fieldName']] = $association['targetEntity'];
                        $dependsEntities[] = $association['targetEntity'];
                    }
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

    /**
     * @param string $firstClass
     * @param array $classRelation
     * @param array $classesRelation
     *
     * @return array
     */
    protected function getEntityGroupSchema(string $firstClass, array $classRelation, array $classesRelation)
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