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


/**
 * DSchool
 *
 * @ORM\Table(name="denorm_d_school")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Denorm\DSchoolRepository")
 * @DENORM\DnTable
 */
class DSchool
{
...

```
Optional attributes:

`name` - specific table part name in new generated denormalized table
`excludeFields` - array of entity field names that will not be processed


### Symfony example
register service:
```yml
# app/config/services.yml

    denorm.driver.annotation:
        class: AppBundle\DenormalizedOrm\Mapping\Driver\AnnotationDriver
        arguments: ['@annotations.reader']    

    denorm.class_metadata_factory.annotation:
        class: AppBundle\DenormalizedOrm\Mapping\DnClassMetadataFactory
        arguments: ['@denorm.driver.annotation']

    denorm.table_manager:
        class: AppBundle\DenormalizedOrm\DnTableManager
        arguments: ['@doctrine.orm.entity_manager', '@denorm.class_metadata_factory.annotation']
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

        foreach ($dnTableManager->getDnTableGroups() as $dnTableGroup) {
            $output->writeln(['Create table: ' . $dnTableGroup->getTableName()]);
            $dnTableManager->createTable($dnTableGroup, $connection);
        }
    }
}
```

by default usage doctrine default connection. you may set custom connection class in second operator in function $dnTableManager->createTable($dnTableGroup, $customConnection);