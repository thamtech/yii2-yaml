<?php
/**
 * @copyright Copyright(c) 2019 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-yaml
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace thamtech\yaml\event;

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
    public $value;
}
