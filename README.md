# orm-denormalizer
denormalize your doctrine ORM entities

### setup the Symfony 2/3 project:

##### 1. describe the `@DENORM\DnTable` annotations for entities

```php
use FOD\OrmDenormalizer\Mapping\Annotation as DENORM;
```
#### @DENORM\Table
use this annotation for entity Class
```php
/**
 * DBuilding
 *
 * @ORM\Table(name="denorm_d_building")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Denorm\DBuildingRepository")
 * @DENORM\Table
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
 * @DENORM\Table(name="school", excludedFields={"title"})
 */
class DSchool
{
...
}
```
Optional attributes:

* `name` - specific part of the table name in the new generated denormalized table
* `excludeFields` - array of entity field names that will not be processed


##### 2. register services:
```yml
# app/config/services.yml
    # denormalization table manager (create table)
    fod.denorm.table_manager:
        class: FOD\OrmDenormalizer\DnTableManager
        arguments: ['@doctrine.orm.entity_manager']

    # load information about All annotated denormalized entities and write to specific connection denormalized data
    fod.denorm.listeners.events_listener:
        class: FOD\OrmDenormalizer\Symfony\DnEventsListener
        arguments: ['@annotations.reader']
        tags:
            - {name: doctrine.event_listener, event: onFlush}
            - {name: doctrine.event_listener, event: loadClassMetadata}
        calls:
            - ['setWriteConnection', ['@service_container', 'doctrine.dbal.clickhouse_connection']] # second parameter (string) is service name of doctrine connection
    
    # optional service to register symfony console command generate SQL for create denormalized tables
    fod.denorm.command.create_denormalized_tables_command:
        class: FOD\OrmDenormalizer\Symfony\Command\CreateDenormalizedTablesCommand
        arguments: ['@fod.denorm.table_manager', '@doctrine.orm.entity_manager']
        tags:
            - {name: console.command}
        calls:
            - ['setConnection', ['@service_container', 'doctrine.dbal.clickhouse_connection']] # second parameter (string) is service name of doctrine connection

```

##### 3. create denormalized tables with a console command:

```sh
$ php bin/console fod:orm-denormalizer:migrations:generate
```
\* run console command with parameter `force` if you want execute SQL

##### 4. work with your Entities as usual