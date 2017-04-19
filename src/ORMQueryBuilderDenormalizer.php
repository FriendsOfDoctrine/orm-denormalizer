<?php
namespace FOD\OrmDenormalizer;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Class ORMQueryBuilderDenormalizer
 * @package FOD\OrmDenormalizer
 */
class ORMQueryBuilderDenormalizer
{
    protected $entityClassTableMapping = [];

    protected $entityClassMapping = [];

    protected $entityAliases = [];

    protected $aliasesReplacedMap = [];

    /** @var  DnTableGroup */
    protected $dnTableGroup;

    /** @var  QueryBuilder */
    protected $queryBuilder;

    /**
     * DenormalizedQueryBuilder constructor.
     *
     * @param QueryBuilder $queryBuilder
     * @param DnTableGroup $dnTableGroup
     * @param ClassMetadataFactory $classMetadataFactory
     */
    public function __construct(QueryBuilder $queryBuilder, DnTableGroup $dnTableGroup, ClassMetadataFactory $classMetadataFactory)
    {
        $this->queryBuilder = $queryBuilder;
        $this->dnTableGroup = $dnTableGroup;

        foreach ($queryBuilder->getRootEntities() as $entityIndex => $rootEntity) {
            $this->entityClassMapping[$rootEntity] = $classMetadataFactory->getMetadataFor($rootEntity)->getName();
            $this->entityClassTableMapping[$this->entityClassMapping[$rootEntity]] = $dnTableGroup->getTableName();
            $this->entityAliases[$queryBuilder->getRootAliases()[$entityIndex]] = $this->entityClassMapping[$rootEntity];
        }

        $this->extractAliases();
    }

    /**
     * @param Connection $connection
     *
     * @return DBALQueryBuilder
     */
    public function translate(Connection $connection)
    {
        $target = $connection->createQueryBuilder();
        foreach ($this->queryBuilder->getDQLParts() as $name => $dqlPart) {
            if (!$dqlPart) {
                continue;
            }
            $method = 'translate' . ucfirst($name);
            if (method_exists($this, $method)) {
                $dbalPart = is_array($dqlPart)
                    ? array_map([$this, $method], $dqlPart)
                    : $this->{$method}($dqlPart);
                $target->add($name, $dbalPart);
            }
        }

        $dbalParams = $this->translateParameters($this->queryBuilder->getParameters());
        $target->setParameters($dbalParams['params'], $dbalParams['paramTypes']);
        $target->setFirstResult($this->queryBuilder->getFirstResult());
        $target->setMaxResults($this->queryBuilder->getMaxResults());

        return $target;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function extractAliases()
    {
        /** @var Join[] $joins */
        foreach ($this->queryBuilder->getDQLPart('join') as $joins) {
            /** @var Join $join */
            foreach ($joins as $join) {

                $splitJoin = explode('.', $join->getJoin());

                if (count($splitJoin) === 2) {
                    list($joinAlias, $joinProperty) = $splitJoin;
                    if (isset($this->entityAliases[$joinAlias], $this->dnTableGroup->getStructureSchema()[$this->entityAliases[$joinAlias]], $this->dnTableGroup->getStructureSchema()[$this->entityAliases[$joinAlias]][$joinProperty])) {
                        $this->entityAliases[$join->getAlias()] = $this->dnTableGroup->getStructureSchema()[$this->entityAliases[$joinAlias]][$joinProperty];
                    } else {
                        throw new \Exception($this->entityAliases[$joinAlias] . ' not in schema table ' . $this->dnTableGroup->getTableName());
                    }
                } elseif (count($splitJoin) === 1) {
                    $this->entityAliases[$join->getAlias()] = $join->getJoin();
                }
            }
        }

        foreach (array_flip($this->entityAliases) as $className => $entityAlias) {
            foreach ($this->dnTableGroup->getColumns() as $dnColumn) {
                if ($dnColumn->getTargetEntityClass() === $className) {
                    $this->aliasesReplacedMap[$entityAlias . '.' . $dnColumn->getTargetPropertyName()] = $dnColumn->getName();
                }
            }
        }

        return $this;
    }

    /**
     * @param Select $dqlPart
     *
     * @return string
     */
    protected function translateSelect(Select $dqlPart)
    {
        return (string)new Select(array_map([$this, 'stripAlias'], $dqlPart->getParts()));
    }

    /**
     * @param From $dqlPart
     *
     * @return array
     */
    protected function translateFrom(From $dqlPart)
    {
        return [
            'table' => $this->entityClassTableMapping[$this->entityAliases[$dqlPart->getAlias()]],
            'alias' => null,
        ];
    }

    /**
     * @param GroupBy $groupBy
     *
     * @return string
     */
    protected function translateGroupBy(GroupBy $groupBy)
    {
        return $this->stripAlias((string)$groupBy);
    }

    /**
     * @param OrderBy $orderBy
     *
     * @return string
     */
    protected function translateOrderBy(OrderBy $orderBy)
    {
        return $this->stripAlias((string)$orderBy);
    }

    /**
     * @param Composite $dqlPart
     *
     * @return CompositeExpression
     */
    protected function translateWhere(Composite $dqlPart)
    {
        $parts = array_map(function ($part) {
            return ($part instanceof Composite)
                ? $this->translateWhere($part) // Recursion!
                : $this->stripAlias((string)$part);
        }, $dqlPart->getParts());
        $type = ($dqlPart instanceof Orx)
            ? CompositeExpression::TYPE_OR
            : CompositeExpression::TYPE_AND;
        return new CompositeExpression($type, $parts);
    }

    /**
     * @param ArrayCollection $ormParameters
     *
     * @return array
     */
    protected function translateParameters($ormParameters)
    {
        $params = [];
        $paramTypes = [];
        foreach ($ormParameters as $parameter) {
            $params[$parameter->getName()] = $parameter->getValue();
            $paramTypes[$parameter->getName()] = $parameter->getType();
        }
        return ['params' => $params, 'paramTypes' => $paramTypes];
    }

    /**
     * @param string $queryPart
     *
     * @return string
     */
    protected function stripAlias($queryPart)
    {
        return str_replace(array_keys($this->aliasesReplacedMap), array_values($this->aliasesReplacedMap), $queryPart);
    }
}