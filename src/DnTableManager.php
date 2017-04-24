<?php
/**
 *  This file is part of the FOD\OrmDenormalizer package -- Denormalizer ORM Doctrine library
 *
 *  (c) FriendsOfDoctrine <https://github.com/FriendsOfDoctrine/>.
 *
 *  For the full copyright and license inflormation, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace FOD\OrmDenormalizer;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;

/**
 * Class DnTableManager
 * @package FOD\OrmDenormalizer
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
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