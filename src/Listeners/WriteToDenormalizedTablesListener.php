<?php
namespace FOD\OrmDenormalizer\Listeners;


use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\UnitOfWork;
use FOD\OrmDenormalizer\DnColumn;
use FOD\OrmDenormalizer\DnTableGroup;
use FOD\OrmDenormalizer\DnTableGroupContainer;
use FOD\OrmDenormalizer\DnTableValue;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Class WriteToDenormalizedTablesListener
 * @package AppBundle\EventListener
 */
class WriteToDenormalizedTablesListener
{
    /**
     * @var DnTableGroupContainer
     */
    protected $dnTableGroupContainer;

    /** @var  \Doctrine\DBAL\Connection */
    protected $connection;

    /**
     * WriteToDenormalizedTablesListener constructor.
     *
     * @param DnTableGroupContainer $container
     * @param Connection|null $connection
     */
    public function __construct(DnTableGroupContainer $container, Connection $connection = null)
    {
        $this->dnTableGroupContainer = $container;
        if (null !== $connection) {
            $this->connection = $connection;
        }
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        if (!$this->connection) {
            $this->connection = $em->getConnection();
        }
        /**
         * Prepare new entities
         */
        $this->handleEntities($uow->getScheduledEntityInsertions(), $uow);

        /**
         * Prepare already isset entities
         */
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            foreach ($this->dnTableGroupContainer->getByContainClass(get_class($entity)) as $dnTableGroup) {
                $this->handleEntities($this->getLeadEntities($entity, $dnTableGroup), $uow);
            }
        }
        /**
         * UPDATE/INSERT operation of denormalized values
         */
        $this->handleDenormalizedValues();
    }

    /**
     * @param array $entities
     * @param UnitOfWork $uow
     */
    protected function handleEntities(array $entities, UnitOfWork $uow)
    {
        foreach ($entities as $entity) {
            foreach ($this->dnTableGroupContainer->getByLeadClass(get_class($entity)) as $dnTableGroup) {
                $relationEntities = [];
                $selfRelatedProperties = [];
                /** @var DnColumn[] $emptyColumns */
                $emptyColumns = [];
                foreach ($dnTableGroup->getColumns() as $column) {
                    if (get_class($entity) === $column->getTargetEntityClass()) {
                        if ($column->getSelfRelatedPropertyName() && !isset($selfRelatedProperties[$column->getSelfRelatedPropertyName()][$column->getTargetPropertyName()])) {
                            $selfRelatedProperties[$column->getSelfRelatedPropertyName()][$column->getTargetPropertyName()] = true;
                        }
                        $dnTableGroup->addColumnValue(new DnTableValue($column, $entity));
                    } else {
                        $emptyColumns[] = $column;
                    }
                }

                foreach ($dnTableGroup->getStructureSchema() as $schemaEntityKey => $schemaRelation) {
                    foreach ($schemaRelation as $property => $className) {
                        $relationEntities[$className] = $uow->getOriginalEntityData(isset($relationEntities[$schemaEntityKey]) ? $relationEntities[$schemaEntityKey] : $entity)[$property];
                    }
                }

                foreach ($emptyColumns as $column) {
                    $dnTableGroup->addColumnValue(new DnTableValue($column, isset($relationEntities[$column->getTargetEntityClass()]) ? $relationEntities[$column->getTargetEntityClass()] : null));
                }
            }
        }
    }

    /**
     * @param $childrenEntity
     * @param DnTableGroup $dnTableGroup
     *
     * @return array
     */
    protected function getLeadEntities($childrenEntity, DnTableGroup $dnTableGroup)
    {
        $leadEntities = [];

        if (isset($dnTableGroup->getOneToManyRelationSchema()[get_class($childrenEntity)])) {
            $getMethod = 'get' . Inflector::ucwords(current(array_keys($dnTableGroup->getOneToManyRelationSchema()[get_class($childrenEntity)])));
            if (method_exists($childrenEntity, $getMethod)) {
                foreach ($childrenEntity->{$getMethod}() as $entity) {
                    $leadEntities[] = $entity;
                    $leadEntities = array_merge($leadEntities, $this->getLeadEntities($entity, $dnTableGroup));
                }
            }
        }

        return $leadEntities;
    }

    /**
     * Update/Insert records in denormalized tables
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function handleDenormalizedValues()
    {
        foreach ($this->dnTableGroupContainer as $dnTableGroup) {
            foreach ($dnTableGroup->getValuesArray() as $values) {
                foreach ($values as $key => $value) {
                    $values[$key] = Type::getType($dnTableGroup->getColumns()[$key]->getType())->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
                }
                try {
                    /**
                     * Try update record in denormalized table by primary key
                     */
                    $this->connection->update($dnTableGroup->getTableName(), $values, array_filter($values, function ($key) use ($dnTableGroup) {
                        return in_array($key, $dnTableGroup->getIndexes(), true);
                    }, ARRAY_FILTER_USE_KEY));
                } catch (\Exception $exception) {
                    /**
                     * Fallback insert data in denormalized table, if platform not supported operation UPDATE
                     */
                    $this->connection->insert($dnTableGroup->getTableName(), $values);
                }
            }
        }
    }
}