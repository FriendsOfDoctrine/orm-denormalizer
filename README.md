# doctrine-orm-denormalize
denormalized doctrine ORM entities

```php
use Argayash\DenormalizedOrm\Mapping\Annotation as DENORM;
```
#### @DENORM\DnTable
use this annotation for entity Class
```php
/**
 * DBuilding
 *
 * @ORM\Table(name="denorm_d_building")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Denorm\DBuildingRepository")
 * @DENORM\DnTable
 */
class DBuilding
{
...
}

/**
 * DSchool
 *
 * @ORM\Table(name="denorm_d_school")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Denorm\DSchoolRepository")
 * @DENORM\DnTable(name="school", excludedFields={"title"})
 */
class DSchool
{
...
}
```
Optional attributes:

* `name` - specific table part name in new generated denormalized table
* `excludeFields` - array of entity field names that will not be processed


### install to Symfony 2/3:
##### register service:
```yml
# app/config/services.yml
    # denormalization table manager (create table)
    denorm.table_manager:
        class: Argayash\DenormalizedOrm\DnTableManager
        arguments: ['@doctrine.orm.entity_manager']

    # load information about All annotated denormalized entities and write to specific connection denormalized data
    denorm.listeners.events_listener:
        class: Argayash\DenormalizedOrm\Symfony\DnEventsListener
        arguments: ['@annotations.reader']
        tags:
            - {name: doctrine.event_listener, event: onFlush}
            - {name: doctrine.event_listener, event: loadClassMetadata}
        calls:
            - ['setWriteConnection', ['@service_container', 'doctrine.dbal.clickhouse_connection']] # second parameter (string) is name of doctrine connection
```
example console command for scan and create all denormalized entities
```php
<?php
namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DenormalizedOrmCommand extends ContainerAwareCommand
{
    /** @var EntityManager */
    protected $em;

    protected $loadedClasses = [];

    protected function configure()
    {
        $this
            ->setName('app:generate-denormalize-tables')
            ->setHelp('Generate and execute SQL for create denormalized tables')
            ->addArgument('connectionName', InputArgument::OPTIONAL, 'Connection name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dnTableManager = $this->getContainer()->get('denorm.table_manager');

        $this->getContainer()->get('doctrine.orm.entity_manager')->getMetadataFactory()->getAllMetadata();

        $connectionName = $input->getArgument('connectionName') ?: 'default';

        $output->writeln([
            '<info>Start console command for create denormalized Doctrine entities</info>',
            '<info>use `' . $connectionName . '` connection</info>'
        ]);

        try {
            $connection = $this->getContainer()->get('doctrine.dbal.' . $connectionName . '_connection');
        } catch (\Exception $exception) {
            $connection = $this->getContainer()->get('database_connection');
        }

        foreach ($this->getContainer()->get('denorm.dn_table_group.container') as $dnTableGroup) {
            $output->writeln(['Create table: ' . $dnTableGroup->getTableName()]);
            $output->writeln($dnTableGroup->getMigrationSQL($connection));
            $dnTableManager->createTable($dnTableGroup, $connection);
        }
    }
}
```
