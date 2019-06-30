<?php
/**
 * @copyright Copyright(c) 2019 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-yaml
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace thamtech\yaml\event;

use Symfony\Component\Yaml\Tag\TaggedValue;
use yii\base\Event;

/**
 * A ValueEvent is triggered when a `!tag`-type tag is encountered during
 * parsing and is parsed into a TaggedValue object, or when an object is
 * encountered during dumping.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class ValueEvent extends Event
{
    /**
     * @var mixed initially, the value originally parsed or dumped, but it can
     * be replaced by event handlers.
     */
    private $_value;

    /**
     * This is a shortcut for [[setValue()]] and setting [[handled]] to true.
     *
     * @param mixed $value the value to set
     */
    public function handleValue($value)
    {
        $this->setValue($value);
        $this->handled = true;
    }

    /**
     * Set the value.
     *
     * @param mixed $value the value to set
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Gets the raw value exactly as it was set.
     *
     * @return mixed value
     */
    public function getRawValue()
    {
        return $this->_value;
    }

    /**
     * Get the represented value. If the set value is a [[TaggedValue]], the
     *    result of `$taggedValue->getValue()` is returned as the represented
     *    value.
     *
     * @return mixed the value
     */
    public function getValue()
    {
        if ($this->unwrapTaggedValue()) {
            return $this->_value->getValue();
        }

        return $this->_value;
    }

    /**
     * Determine whether to unwrap a taggedValue value.
     *
     * @return bool if it should be unwrapped
     */
    private function unwrapTaggedValue()
    {
        if (0 === strpos($this->name, TaggedValue::class)) {
            // if the event name is the TaggedValue class, then the event
            // handlers will be expecting a TaggedValue object, so we shouldn't
            // unwrap it
            return false;
        }

        // otherwise, we only want to attempt to unrap a TaggedValue if it
        // actually is a TaggedValue object.
        return $this->_value instanceof TaggedValue;
    }
}
