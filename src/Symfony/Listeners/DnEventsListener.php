<?php
/**
 *  This file is part of the FOD\OrmDenormalizer package -- Denormalizer ORM Doctrine library
 *
 *  (c) FriendsOfDoctrine <https://github.com/FriendsOfDoctrine/>.
 *
 *  For the full copyright and license inflormation, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace FOD\OrmDenormalizer\Symfony\Listeners;

use FOD\OrmDenormalizer\DnTableGroupContainer;
use FOD\OrmDenormalizer\Listeners\LoadClassMetadataListener;
use FOD\OrmDenormalizer\Listeners\WriteToDenormalizedTablesListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DnEventsListener
 * @package FOD\OrmDenormalizer\Symfony\Listeners
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
 */
class DnEventsListener
{
    /** @var  Connection */
    protected $connection;
    /** @var  DnTableGroupContainer */
    protected $dnTableGroupContainer;
    /** @var  WriteToDenormalizedTablesListener */
    protected $writeToDenormalizedTableListener;
    /** @var  LoadClassMetadataListener */
    protected $loadClassMetadataListener;
    /** @var  Reader */
    protected $reader;

    /**
     * DnEventListener constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
        $this->dnTableGroupContainer = DnTableGroupContainer::getInstance();
    }

    /**
     * @param ContainerInterface $container
     * @param $doctrineConnectionName string
     *
     * @return DnEventsListener
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function setConnection(ContainerInterface $container, $doctrineConnectionName)
    {
        if (!$this->connection) {
            $this->connection = $container->get($doctrineConnectionName);
        }

        return $this;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->writeToDenormalizedTableListener) {
            $this->writeToDenormalizedTableListener = new WriteToDenormalizedTablesListener($this->dnTableGroupContainer, $this->connection);
        }

        $this->writeToDenormalizedTableListener->onFlush($eventArgs);
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @throws \Exception
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        if (!$this->loadClassMetadataListener) {
            $this->loadClassMetadataListener = new LoadClassMetadataListener($this->dnTableGroupContainer, $this->reader);
        }

        $this->loadClassMetadataListener->loadClassMetadata($eventArgs);
    }

    /**
     * @return DnTableGroupContainer
     */
    public function getDnTableGroupContainer()
    {
        return $this->dnTableGroupContainer;
    }
}