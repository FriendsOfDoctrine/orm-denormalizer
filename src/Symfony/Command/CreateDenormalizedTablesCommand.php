<?php
namespace FOD\OrmDenormalizer\Symfony\Command;


use FOD\OrmDenormalizer\DnTableGroupContainer;
use FOD\OrmDenormalizer\DnTableManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CreateDenormalizedTablesCommand extends Command
{
    /** @var  DnTableManager */
    protected $dnTableManager;
    /** @var  EntityManager */
    protected $em;
    /** @var  ContainerInterface */
    protected $container;
    /** @var  string */
    protected $connectionName;
    /** @var  Connection */
    protected $connection;

    public function __construct(DnTableManager $dnTableManager, EntityManager $entityManager)
    {
        $this->dnTableManager = $dnTableManager;
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('fod:orm-denormalizer:migrations:generate')
            ->setDescription('Generate SQL for create denormalized tables')
            ->addArgument('force', InputArgument::OPTIONAL, 'Execute showed SQL', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getMetadataFactory()->getAllMetadata();
        $this->connection = $this->container->get($this->connectionName);

        $output->writeln([
            '<info>Start console command for create denormalized Doctrine entities</info>',
            '<info>generate SQL for `' . $this->connection->getDatabasePlatform()->getName() . '` platform</info>'
        ]);

        foreach (DnTableGroupContainer::getInstance() as $dnTableGroup) {
            $output->writeln(['<comment>Create table: ' . $dnTableGroup->getTableName() . '</comment>']);
            try {
                $output->writeln(['SQL:']);
                $output->writeln('<bg=black;options=bold>' . implode(PHP_EOL, $dnTableGroup->getMigrationSQL($this->connection)) . '</>');
                if ($input->getArgument('force')) {
                    $this->dnTableManager->createTable($dnTableGroup, $this->connection);
                    $output->writeln('<info>Execute SQL</info>');
                }
            } catch (SchemaException $schemaException) {
                $output->writeln('<error>' . $schemaException->getMessage() . '</error>');
            }
        }
    }

    /**
     * @param ContainerInterface $container
     * @param string $connectionName
     */
    public function setConnection(ContainerInterface $container, $connectionName = '')
    {
        $this->container = $container;
        $this->connectionName = $connectionName;
    }
}