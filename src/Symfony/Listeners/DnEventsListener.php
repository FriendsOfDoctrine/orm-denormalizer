<?php
namespace FOD\DoctrineOrmDenormalized\Symfony\Listeners;


use FOD\DoctrineOrmDenormalized\DnTableGroupContainer;
use FOD\DoctrineOrmDenormalized\Listeners\LoadClassMetadataListener;
use FOD\DoctrineOrmDenormalized\Listeners\WriteToDenormalizedTablesListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DnEventListener
 * @package AppBundle\EventListener
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
}