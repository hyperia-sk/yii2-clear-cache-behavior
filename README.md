# Yii2 clear cache behavior
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/hyperia-sk/yii2-clear-cache-behavior/master/LICENSE) 
> The behavior for Yii2 to clearing cache on specific events

## Instalation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```shell
composer require hyperia/yii2-clear-cache-behavior:"*"
```

or add

```
"hyperia/yii2-clear-cache-behavior": "*"
```

to the require section of your composer.json.

## Configuration (usage)
In ActiveRecord Model class to invalidate tag after insert, update or delete
```php

use yii\db\ActiveRecord;
use hyperia\behaviors\ClearCacheBehavior;

class Model extends ActiveRecord
{
    const CACHE_KEY = '~model~';

    public function behaviors()
    {
        return [
            ...
            'clearCache' => [
                'class' => ClearCacheBehavior::class,
                'events' => [
                    ActiveRecord::EVENT_AFTER_INSERT,
                    ActiveRecord::EVENT_AFTER_UPDATE,
                    ActiveRecord::EVENT_AFTER_DELETE
                ],
                'type' => ClearCacheBehavior::TYPE_INVALIDATE_TAG,
                'value' => static::CACHE_KEY
            ],
        ];
    }
}
```

## Parameter description

### events
*array*
Determinantes on which event would be cache deleted. When you want set up Event with same settings.

Default value:
[
    ActiveRecord::EVENT_AFTER_INSERT,
    ActiveRecord::EVENT_AFTER_UPDATE,
    ActiveRecord::EVENT_AFTER_DELETE
]

### cache
*string*
Name of cache component in yii components configuration
**Default: "cache"**

### value 
*string | array | Closure*
Determinantes which part of cache would be deleted **ONLY WHEN EVENTS IS SET**


### type
*string*
Sets how the cache will be deleted **ONLY WHEN EVENTS IS SET**
Types:
 - TYPE_INVALIDATE_TAG : Calls yii\caching\TagDependency::invalidate($cacheObject, $value);
 - TYPE_FLUSH : Calls flush() method on cache object (by value of cache parameter)
 - TYPE_DELETE : Calls delete($value) method on cache object (by value of cache parameter)

### events_with_settings
*array*
Array which represents setting of multiple events. Determinantes on which event would be cache deleted. When you want set up multiple Events
Simple example:
```php
    'events_with_settings' => [
          \yii\web\Controller::EVENT_BEFORE_ACTION => [
              'type' => ClearCacheBehavior::TYPE_INVALIDATE_TAG,
              'value' => static::CACHE_KEY
          ],
          \yii\web\Controller::EVENT_AFTER_ACTION => [
              'type' => ClearCacheBehavior::TYPE_DELETE,
              'value' => function($event) use ($model) {
                  return $model->id;
              }
          ]
    ],
```