<?php
namespace hyperia\behaviors;

use Yii;
use Closure;
use yii\base\Event;
use yii\base\Behavior;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;

class ClearCacheBehavior extends Behavior
{
    public const TYPE_INVALIDATE_TAG = 'invalidate';
    public const TYPE_FLUSH = 'flush';
    public const TYPE_DELETE = 'delete';
    
    private const DEFAULT_FUNCTION = 'process';
    
    /**
     * Array which represents setting of multiple events
     * Determinantes on which event would be cache deleted.
     * When you want set up multiple Events
     * <br>
     * Simple example:
     * <pre><code>
     * 'eventsWithSettings' => [
     *      \yii\web\Controller::EVENT_BEFORE_ACTION => [
     *          'type' => ClearCacheBehavior::TYPE_INVALIDATE_TAG,
     *          'value' => static::CACHE_KEY
     *      ],
     *      \yii\web\Controller::EVENT_AFTER_ACTION => [
     *          'type' => ClearCacheBehavior::TYPE_DELETE,
     *          'value' => function($event) use ($model) {
     *              return $model->id;
     *          }
     *      ]
     * ],
     * </pre></code>
     * @var array
     */
    public $eventsWithSettings;
    
    /**
     * Determinantes on which event would be cache deleted.
     * When you want set up Event with same settings
     *
     * @var array
     */
    public $events = [
        ActiveRecord::EVENT_AFTER_INSERT,
        ActiveRecord::EVENT_AFTER_UPDATE,
        ActiveRecord::EVENT_AFTER_DELETE
    ];
    
    
    public $only;
    
    /**
     * Sets how the cache will be deleted
     * ONLY WHEN EVENTS IS SET
     *
     * @var string
     */
    public $type;
    
    /**
     * Determinantes which part of cache would be deleted
     * ONLY WHEN EVENTS IS SET
     *
     * @var string | array | Closure
     */
    public $value;
    
    /**
     * Name of cache component in yii components configuration
     *
     * @var string
     */
    public $cache = 'cache';
    
    /**
     * Cache object component
     *
     * @var \yii\base\Component
     */
    private $cacheObject;
    
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        try {
            $this->cacheObject = Yii::$app->get($this->cache);
        } catch (\yii\base\Exception $ex) {
            throw new \yii\base\InvalidArgumentException("Component $this->cache not found " . $ex->getMessage());
        }
    }
    
    /**
     * Clear cache on current event in the selected way
     * @param Event $event
     */
    public function process(Event $event)
    {
        if (!empty($this->only) && isset($event->action) && !in_array($event->action->id, $this->only)) {
            return;
        }
        
        if (!empty($this->eventsWithSettings) && array_key_exists($event->name, $this->eventsWithSettings)) {
            $type = $this->eventsWithSettings[$event->name]['type'];
            $value = $this->getValue($event, $this->eventsWithSettings[$event->name]['value']);
            $this->$type($event, $value);
        }
        
        if (!empty($this->events) && in_array($event->name, $this->events)) {
            $type = $this->type;
            $this->$type($event, $this->getValue($event, $this->value));
        }
    }
    
    /**
     * Invalidates all of the cached data items that are associated with any of the specified [[tags]].
     * @param Event $event
     * @param mixed $value
     */
    public function invalidate(Event $event, $value)
    {
        TagDependency::invalidate($this->cacheObject, $value);
    }
    
    /**
     * Deletes a value with the specified key from cache
     * @param Event $event
     * @param type $value
     */
    public function delete(Event $event, $value)
    {
        $this->cacheObject->delete($value);
    }
    
    /**
     * Deletes all values from cache.
     * @param Event $event
     * @param type $value
     */
    public function flush(Event $event, $value)
    {
        $this->cacheObject->flush();
    }

    /**
     * @PHPUnitGen\AssertNotEmpty()
     * @PHPUnitGen\AssertInternalType('array')
     * @return array
     */
    public function events()
    {
        return [
            \yii\web\Controller::EVENT_AFTER_ACTION => static::DEFAULT_FUNCTION,
            \yii\web\Controller::EVENT_BEFORE_ACTION => static::DEFAULT_FUNCTION,
            \yii\db\BaseActiveRecord::EVENT_AFTER_INSERT => static::DEFAULT_FUNCTION,
            \yii\db\BaseActiveRecord::EVENT_AFTER_DELETE => static::DEFAULT_FUNCTION,
            \yii\db\BaseActiveRecord::EVENT_AFTER_UPDATE => static::DEFAULT_FUNCTION,
            \yii\db\BaseActiveRecord::EVENT_AFTER_REFRESH => static::DEFAULT_FUNCTION
        ];
    }
    
    /**
     * Returns the value for the current type of deletion.
     *
     * @PHPUnitGen\AssertNotEmpty(:{null, 'foo'})
     * @param Event $event the event that triggers delete of cache.
     * @return mixed the attribute value
     */
    protected function getValue($event, $value)
    {
        if ($value instanceof Closure || (is_array($value) && is_callable($value))) {
            return call_user_func($value, $event);
        }
        
        return $value;
    }
}
