<?php
/**
 * @copyright Copyright(c) 2019 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-yaml
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace thamtech\yaml;

use thamtech\yaml\event\ValueEvent;
use Symfony\Component\Yaml\Parser as SymfonyParser;
use Symfony\Component\Yaml\Tag\TaggedValue;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use Yii;

/**
 * Parser acts as an extension to [[Symfony\Component\Yaml\Parser]] to support handling custom tags.
 *
 * Technically, it is implemented as a wrapper for an underlying
 * [[Symfony\Component\Yaml\Parser]] instance. After using the underlying
 * instance to parse Yaml data, this class further processes the result
 * looking for instances of [[Symfony\Component\Yaml\Tag\TaggedValue]] that
 * we might want to replace.
 *
 * You can attach event handlers to a Parser object. The event
 * name is the portion after the `!` in a custom yaml tag. The handler
 * will be given a [[thamtech\yaml\event\ValueEvent]] object containing
 * the parsed value associated with the custom yaml tag. The handler may
 * process the `TaggedValue` object or replace it entirely in
 * `$event->value` and mark the event as handled (`$event->handled = true;`).
 *
 * All of this additional processing is performed AFTER the yaml has been
 * fully parsed, so there will be a performance penalty to use this
 * version of the Parser. The tradeoff in return is an easier way to support
 * custom tags.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class Parser extends Component
{
    /**
     * @var SymfonyParser the underlying parser to which we will delegate
     */
    private $_parser;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (empty($this->_parser)) {
            $this->setParser(new SymfonyParser());
        }
    }

    /**
     * Set the underlying parser
     *
     * @param SymfonyParser $parser the parser
     */
    public function setParser(SymfonyParser $parser)
    {
        $this->_parser = $parser;
    }

    /**
     * Parses a YAML file into a PHP value
     *
     * @param string $filename The path to the YAML file to be parsed
     * @param int $flags A bit field of Symfony\Component\Yaml\Yaml::PARSE_*
     *     constants to customize the YAML parser behavior
     *
     * @return mixed The YAML converted to a PHP value
     *
     * @throws ParseException If the file could not be read or the YAML is not valid
     */
    public function parseFile($filename, $flags = 0)
    {
        return $this->postProcess($this->_parser->parseFile($filename, $flags));
    }

    /**
     * Parses a YAML string to a PHP value.
     *
     * @param string $value A YAML string
     * @param int $flags A bit field of Symfony\Component\Yaml\Yaml::PARSE_*
     *     constants to customize the YAML parser behavior
     *
     * @return mixed A PHP value
     *
     * @throws ParseException If the YAML is not valid
     */
    public function parse($value, $flags = 0)
    {
        return $this->postProcess($this->_parser->parse($value, $flags));
    }

    /**
     * Recursively process the value to handle any TaggedValue objects.
     *
     * @param  mixed $value a value to process
     *
     * @return mixed the value with any TaggedValue objects having been
     *     processed and possibly replaced.
     */
    protected function postProcess($value)
    {
        if ($value instanceof TaggedValue) {
            $value = $this->processTaggedValue($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $k=>&$v) {
            $v = $this->postProcess($v);
        }

        return $value;
    }

    /**
     * Process the TaggedValue object.
     *
     * Fire any events necessary to handle the TaggedVale object which may
     * result in a modified or replacement value.
     *
     * @param  TaggedValue $value [description]
     *
     * @return mixed the value after processing. This may or may not be a
     * TaggedValue instance.
     */
    protected function processTaggedValue(TaggedValue $value)
    {
        $tag = $value->getTag();

        if ($this->hasEventHandlers($tag)) {
            $event = Yii::createObject([
                'class' => ValueEvent::class,
                'value' => $value,
            ]);
            $this->trigger($tag, $event);
            if ($event->handled) {
                $value = $event->getRawValue();
            }
        }

        return $value;
    }
}
