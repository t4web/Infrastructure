# Infrastructure

Master:
[![Build Status](https://travis-ci.org/t4web/Infrastructure.svg?branch=master)](https://travis-ci.org/t4web/Infrastructure)
[![codecov.io](http://codecov.io/github/t4web/Infrastructure/coverage.svg?branch=master)](http://codecov.io/github/t4web/Infrastructure?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/t4web/Infrastructure/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/t4web/Infrastructure/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/973ae246-c9a7-4a93-b84b-24fbcafd3cda/mini.png)](https://insight.sensiolabs.com/projects/973ae246-c9a7-4a93-b84b-24fbcafd3cda)
[![Dependency Status](https://www.versioneye.com/user/projects/563887a1e93564001a000200/badge.svg?style=flat)](https://www.versioneye.com/user/projects/563887a1e93564001a000200)

Infrastructure layer for Domain, implementation by [t4web\domain-interface](https://github.com/t4web/DomainInterface)

## Contents
- [Installation](#instalation)
- [Quick start](#quick-start)
- [Components](#components)

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
  ``php
  $criteria = new Criteria('Task');
  $criteria->equalTo('id', 2);
  $criteria->in('type', [1,2,3]);
  $criteria->limit(20);
  $criteria->offset(10);
  $criteria->relation('Photos')
      ->equalTo('status', 3)
      ->greaterThan('created_dt', '2015-10-30');
  ``

- `CriteriaFactory` - for creating complex criteria from array
  ``php
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
  ``
