<?php
namespace Argayash\DenormalizedOrm\Listeners;


use Argayash\DenormalizedOrm\DnTableGroupContainer;
use Argayash\DenormalizedOrm\DnTableValue;
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
     */
    public function __construct(DnTableGroupContainer $container)
    {
        $this->dnTableGroupContainer = $container;
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

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            foreach ($this->dnTableGroupContainer->getByLeadClass(get_class($entity)) as $dnTableGroup) {
                $relationEntities = [];
                foreach ($dnTableGroup->getColumns() as $column) {
                    if (get_class($entity) === $column->getTargetEntityClass()) {
                        $dnTableGroup->addColumnValue(new DnTableValue($column, $entity));
                    } else {
                        foreach ($dnTableGroup->getStructureSchema() as $schemaEntityKey => $schemaRelation) {
                            foreach ($schemaRelation as $property => $className) {
                                $relationEntities[$className] = $uow->getOriginalEntityData(isset($relationEntities[$schemaEntityKey]) ? $relationEntities[$schemaEntityKey] : $entity)[$property];
                            }
                        }
                    }
                }
                foreach ($dnTableGroup->getColumns() as $column) {
                    if (isset($relationEntities[$column->getTargetEntityClass()])) {
                        $dnTableGroup->addColumnValue(new DnTableValue($column, $relationEntities[$column->getTargetEntityClass()]));
                    }
                }
            }
        }

        foreach ($this->dnTableGroupContainer as $dnTableGroup) {
            foreach ($dnTableGroup->getValuesArray() as $values) {
                foreach ($values as $key => $value) {
                    $values[$key] = Type::getType($dnTableGroup->getColumns()[$key]->getType())->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
                }
                $this->connection->insert($dnTableGroup->getTableName(), $values);
            }
        }
    }
}