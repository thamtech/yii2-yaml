<?php
/**
 * @copyright Copyright(c) 2019 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-yaml
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace thamtech\yaml;

use thamtech\yaml\event\ValueEvent;
use Symfony\Component\Yaml\Dumper as SymfonyDumper;
use Symfony\Component\Yaml\Tag\TaggedValue;
use yii\base\Component;
use Yii;

/**
 * Dumper acts as an extension to [[Symfony\Component\Yaml\Dumper]] to support dumping custom tags.
 *
 * Technically, it is implemented as a wrapper for an underlying
 * [[Symfony\Component\Yaml\Dumper]] instance. Before the underlying
 * instance dumps data to Yaml, this class pre-processes the data
 * looking for PHP objects that we might want to replace.
 *
 * You can attach event handlers to a Dumper object. The event name
 * is the class name of an object you want to handle. The handler will be given
 * a [[thamtech\yaml\event\ValueEvent]] object containing the matching object.
 * The handler may process the object or replace it entirely in
 * `$event->value` and mark the event as handled (`$event->handled = true;`).
 *
 * A PHP object that was not handled by a named or wildcard event will trigger
 * an EVENT_UNHANDLED_OBJECT event. This can be used as a catch-all to make one
 * last attempt to serialize an object.
 *
 * All of this additional processing is performed BEFORE the yaml is dumped,
 * so there will be a performance penalty to use this version of the Dumper.
 * The tradeoff in return is an easier way to support dumping objects and
 * custom tags.
 *
 * After the yaml is dumped, the EVENT_AFTER_DUMP event is triggered with the
 * yaml string as the event value. The resulting event value is returned as the
 * final yaml string.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class Dumper extends Component
{
    const EVENT_UNHANDLED_OBJECT = '__unhandledObject';
    const EVENT_AFTER_DUMP = '__afterDump';

    /**
     * @var int the default indentation to use when setting the default dumper
     */
    public $defaultIndentation = 4;

    /**
     * @var SymfonyDumper the underlying dumper to which we will delegate
     */
    private $_dumper;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (empty($this->_dumper)) {
            $this->setDumper(new SymfonyDumper($this->defaultIndentation));
        }
    }

    /**
     * Set the underlying dumper
     *
     * @param SymfonyDumper $dumper the dumper
     */
    public function setDumper(SymfonyDumper $dumper)
    {
        $this->_dumper = $dumper;
    }

    /**
     * Dumps a PHP value to YAML.
     *
     * @param mixed $input  The PHP value
     *
     * @param int $inline The level where you switch to inline YAML
     *
     * @param int $indent The level of indentation (used internally)
     *
     * @param int $flags  A bit field of Symfony\Component\Yaml\Yaml::DUMP_*
     *     constants to customize the dumped YAML string
     *
     * @return string The YAML representation of the PHP value
     */
    public function dump($input, $inline = 0, $indent = 0, $flags = 0)
    {
        $yaml = $this->_dumper->dump($this->preProcess($input), $inline, $indent, $flags);
        return $this->postProcess($yaml);
    }

    /**
     * Post-process dumped yaml
     *
     * @param  string $yaml dumped yaml
     *
     * @return string the resulting yaml
     */
    public function postProcess($yaml)
    {
        if ($this->hasEventHandlers(self::EVENT_AFTER_DUMP)) {
            $event = Yii::createObject([
                'class' => ValueEvent::class,
                'value' => $yaml,
            ]);
            $this->trigger(self::EVENT_AFTER_DUMP, $event);
            return $event->value;
        }

        return $yaml;
    }

    /**
     * Recursively process the value to handle any PHP objects.
     *
     * @param  mixed $value a value to process
     *
     * @return mixed the value with any PHP objects having been processed
     *     and possibly replaced.
     */
    public function preProcess($value)
    {
        if (is_object($value)) {
            $handled = false;
            if ($this->hasEventHandlers(get_class($value))) {
                $event = Yii::createObject([
                    'class' => ValueEvent::class,
                    'value' => $value,
                ]);
                $this->trigger(get_class($value), $event);
                if ($event->handled) {
                    $value = $event->getRawValue();
                    $handled = true;
                }
            }

            if ($value instanceof TaggedValue) {
                // we don't want to consider this an "unhandled object"
                // if someone wants to pre-process these, they should add
                // an explicit handler for the TaggedValue object
                $handled = true;
            }

            if (!$handled && $this->hasEventHandlers(self::EVENT_UNHANDLED_OBJECT)) {
                $event = Yii::createObject([
                    'class' => ValueEvent::class,
                    'value' => $value,
                ]);
                $this->trigger(self::EVENT_UNHANDLED_OBJECT, $event);
                if ($event->handled) {
                    $value = $event->getRawValue();
                }
            }
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $k=>&$v) {
            $v = $this->preProcess($v);
        }

        return $value;
    }
}
