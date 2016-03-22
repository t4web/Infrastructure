# Infrastructure

Master:
[![Build Status](https://travis-ci.org/t4web/Infrastructure.svg?branch=master)](https://travis-ci.org/t4web/Infrastructure)
[![codecov.io](http://codecov.io/github/t4web/Infrastructure/coverage.svg?branch=master)](http://codecov.io/github/t4web/Infrastructure?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/t4web/Infrastructure/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/t4web/Infrastructure/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/973ae246-c9a7-4a93-b84b-24fbcafd3cda/mini.png)](https://insight.sensiolabs.com/projects/973ae246-c9a7-4a93-b84b-24fbcafd3cda)
[![Dependency Status](https://www.versioneye.com/user/projects/5639f8af1d47d400190001a6/badge.svg?style=flat)](https://www.versioneye.com/user/projects/5639f8af1d47d400190001a6)

Infrastructure layer for Domain, implementation by [t4web\domain-interface](https://github.com/t4web/DomainInterface)

## Contents
- [Installation](#instalation)
- [Quick start](#quick-start)
- [Components](#components)
- [Build criteria from array](#build-criteria-from-array)
- [Configuring](#configuring)
- [Events](#events)

## Installation

Add this project in your composer.json:

```json
"require": {
    "t4web/infrastructure": "~1.0.0"
}
```

Now tell composer to download Domain by running the command:

```bash
$ php composer.phar update
```

## Quick start

You can use `Repository` with Domain implementation [t4web\domain](https://github.com/t4web/Domain).
This implementation build on [Zend\Db](https://github.com/zendframework/zend-db) and 
[Zend\EventManager](https://github.com/zendframework/zend-eventmanager)

## Components

- `Criteria` - for creating fetch expression
  ```php
  $criteria = new T4webInfrastructure\Criteria('Task');
  $criteria->equalTo('id', 2);
  $criteria->in('type', [1,2,3]);
  $criteria->limit(20);
  $criteria->offset(10);
  $criteria->relation('Photos')
      ->equalTo('status', 3)
      ->greaterThan('created_dt', '2015-10-30');
  ```

- `CriteriaFactory` - for creating complex criteria from array
  ```php
  $criteriaFactory = new T4webInfrastructure\CriteriaFactory();
  $criteria = $criteriaFactory->build(
      'Task',
      [
          'status.equalTo' => 2,
          'dateCreate.greaterThan' => '2015-10-30',

          'relations' => [
              'User' => [
                  'status.in' => [2, 3, 4],
                  'name.like' => 'gor'
              ]
          ]
      ]
  );
  ```
  
- `Mapper` - for translate `Entity` to table row (array), and table row to `Entity`
  ```php
  $columnsAsAttributesMap = [
      'id' => 'id',
      'project_id' => 'projectId',
      'name' => 'name',
      'assignee_id' => 'assigneeId',
      'status' => 'status',
      'type' => 'type',
  ];
  $tableRow = [
      'id' => 22,
      'project_id' => 33,
      'name' => 'Some name',
      'assignee_id' => 44,
      'status' => 2,
      'type' => 1,
  ];
  $mapper = new T4webInfrastructure\Mapper($columnsAsAttributesMap, new T4webDomainInterface\EntityFactoryInterface());
  $entity = $mapper->fromTableRow($tableRow);
  $tableRow = $mapper->toTableRow($entity);
  ```

- `QueryBuilder` - for build SQL query
  ```php
  $queryBuilder = new T4webInfrastructure\QueryBuilder();
  
  $criteria = new T4webInfrastructure\Criteria('Task');
  $criteria->equalTo('id', 2);
  $criteria->relation('Photos')
      ->equalTo('status', 3);
      
  /** @var Zend\Db\Sql\Select $select */
  $select = $queryBuilder->getSelect($criteria);
  
  $tableGateway = new Zend\Db\TableGateway\TableGateway('tasks', $dbAdapter);
  $rows = $this->tableGateway->selectWith($select);
  
  $sql = $select->getSqlString($this->dbAdapter->getPlatform());
  // $sql = SELECT `tasks`.*
  //        FROM `tasks`
  //        INNER JOIN `photos` ON `photos`.`task_id` = `tasks`.`id`
  //        WHERE `tasks`.id = 2 AND `photos`.`status` = 3
  ```

## Build criteria from array

You can use `CriteriaFactory::build()` for building criteria from array (for example: 
from input filter, post\get request)

```php
$inputData = $_GET;

$criteriaFactory = new T4webInfrastructure\CriteriaFactory();
$criteria = $criteriaFactory->build(
    'Task',
    $inputData
);
```

`$inputData` must be structured like this:

```php
$inputData = [
     'status.equalTo' => 2,
     'dateCreate.greaterThan' => '2015-10-30',
     // ...
     'ATTRIBUTE.METHOD' => VALUE
 ]
```

where `ATTRIBUTE` - criteria field, `METHOD` - one of `equalTo`, `notEqualTo`, `lessThan`,
`greaterThan`, `greaterThanOrEqualTo`, `lessThanOrEqualTo`, `like`, `in`

for `isNull`, `isNotNull` use
 
```php
$inputData = [
  'ATTRIBUTE.isNull' => TRUE_EXPRESSION,
  'ATTRIBUTE.isNotNull' => TRUE_EXPRESSION,
  
  // example
  'status.isNull' => true,
  'dateCreate.isNotNull' => 1,
]
```
 
where `TRUE_EXPRESSION` can be any true expression: `true`, `1`, `'a'` etc.
 
for `between` use array as value
 
```php
$inputData = [
   'ATTRIBUTE.between' => [MIN_VALUE, MAX_VALUE],
   
   // example
   'dateCreate.between' => ['2015-10-01', '2015-11-01'],
]
```
  
for `limit`, `offset` use 

```php
$inputData = [
   'limit' => VALUE,
   'offset' => VALUE,
   
   // example
   'limit' => 20,
   'offset' => 10,
]
```

for `order` use SQL-like order expression

```php
$inputData = [
   'order' => EXPRESSION,
   
   // example
   'order' => 'dateCreate DESC',
   'order' => 'dateCreate DESC, status ASC',
]
```

Custom criteria - grouping and reusing criteria
```php
$inputData = [
    'Users\User\Criteria\Active' => true,
]
```
`Users\User\Criteria\Active` - must be invokable class (`__invoke(CriteriaInterface $criteria, $value)`)

## Configuring

For configuring `Repository` you must specify config, and use `Config` object for parsing config. `QueryBuilder` 
use `Config` for building SQL query.

```php
$entityMapConfig = [
    // Entity name
    'Task' => [
        
        // table name
        'table' => 'tasks',
        
        // map for entity attribute <=> table fields
        'columnsAsAttributesMap' => [
            
            // attribute => table field
            'id' => 'id',
            'project_id' => 'projectId',
            'name' => 'name',
            'assignee_id' => 'assigneeId',
            'status' => 'status',
            'type' => 'type',
        ],
        
        // foreign relation
        'relations' => [
        
            // relation entity name + table.field for building JOIN
            'User' => ['tasks.assignee_id', 'user.id'],
            
            // relation entity name + table.field for building JOIN
            'Tag' => ['tasks_tags_link', 'task_id', 'tag_id'],
        ],

        // for aliasing long\ugly criterias
        'criteriaMap' => [
            // alias => criteria
            'date_more' => 'dateCreate.greaterThan',
        ],
    ],
]
```

`relations` argument order - very important, `Task['relations']['User'][0]` - vust be field in current entity, `Task['relations']['User'][1]` - must be field from related entity.

## Events

`Repository` rise events when entity created or updated.
```php
$eventManager = new EventManager();
$eventManager->getSharedManager()->attach(
     'T4webInfrastructure\Repository',
     ''entity:ModuleName\EntityName\EntityName:changed'',
     function(T4webInfrastructure\Event\EntityChangedEvent $e){
        $changedEntity = $e->getChangedEntity();
        $originalEntity = $e->getOriginalEntity();
        // ...
     },
     $priority
);
```
Now `Repository` can rise:
- `entity:ModuleName\EntityName\EntityName:created` - rise after Entity just created in DB.
  In context event subscriber receive Zend\EventManager\Event.
- `entity:ModuleName\EntityName\EntityName:changed:pre` - rise before Entity update in DB.
  In context event subscriber receive T4webInfrastructure\Event\EntityChangedEvent.
- `entity:ModuleName\EntityName\EntityName:changed` - rise after Entity just updated in DB.
  In context event subscriber receive T4webInfrastructure\Event\EntityChangedEvent.
- `attribute:ModuleName\EntityName\EntityName:attribute:changed` - rise after Entity attribute updated in DB.
  In context event subscriber receive T4webInfrastructure\Event\EntityChangedEvent.
