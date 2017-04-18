<?php
namespace FOD\OrmDenormalizer\Symfony\Services;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use FOD\OrmDenormalizer\Symfony\Listeners\DnEventsListener;
use FOD\OrmDenormalizer\ORMQueryBuilderDenormalizer;

/**
 * Class ORMQueryBuilderTransformer
 * @package FOD\OrmDenormalizer\Symfony\Services
 */
class ORMQueryBuilderTransformer
{
    /** @var  DnEventsListener */
    protected $dnEventListener;
    /** @var EntityManager */
    protected $em;

    /**
     * QueryBuilderToDenormTranslator constructor.
     *
     * @param DnEventsListener $dnEventsListener
     * @param EntityManager $entityManager
     */
    public function __construct(DnEventsListener $dnEventsListener, EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->dnEventListener = $dnEventsListener;
    }

    /**
     * @param ORMQueryBuilder $queryBuilder
     * @param Connection $connection
     *
     * @return DBALQueryBuilder|null
     * @throws \Exception
     */
    public function transform(ORMQueryBuilder $queryBuilder, Connection $connection)
    {
        $this->connection = $connection;

        if (DBALQueryBuilder::SELECT !== $queryBuilder->getType()) {
            throw new \Exception('Support only SELECT query');
        }

        foreach ($queryBuilder->getRootEntities() as $entityIndex => $rootEntity) {
            foreach ($this->dnEventListener->getDnTableGroupContainer()->getByLeadClass($this->em->getClassMetadata($rootEntity)->getName()) as $dnTableGroup) {
                return (new ORMQueryBuilderDenormalizer($queryBuilder, $dnTableGroup, $this->em->getMetadataFactory()))->translate($connection);
            }
        }

        return null;
    }


}