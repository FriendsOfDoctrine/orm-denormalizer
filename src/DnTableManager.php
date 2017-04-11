<?php
namespace Argayash\DenormalizedOrm;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;

/**
 * Class DnTableManager
 * @package AppBundle\DenormalizedOrm
 */
class DnTableManager
{
    /** @var  EntityManager */
    protected $em;

    /**
     * DnTableManager constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param DnTableGroup $dnTableGroup
     * @param Connection|null $connection
     */
    public function createTable(DnTableGroup $dnTableGroup, Connection $connection = null)
    {
        if (null === $connection) {
            $connection = $this->em->getConnection();
        }
        foreach ($dnTableGroup->getMigrationSQL($connection) as $sql) {
            $connection->exec($sql);
        }
    }
}