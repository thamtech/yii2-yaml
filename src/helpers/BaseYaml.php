<?php
/**
 * @copyright Copyright(c) 2016-2019 Thamtech, LLC
 * @link https://github.com/thamtech/yii2-yaml
 * @license https://opensource.org/licenses/BSD-3-Clause
**/

namespace thamtech\yaml\helpers;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;
use thamtech\yaml\Dumper;
use thamtech\yaml\Parser;
use yii\base\InvalidArgumentException;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\ReplaceArrayValue;
use yii\helpers\UnsetArrayValue;
use Yii;

/**
 * BaseYaml provides concrete implementation for [[Yaml]].
 *
 * Do not use BaseYaml. Use [[Yaml]] instead.
 *
 * @author Tyler Ham <tyler@thamtech.com>
 */
class BaseYaml
{
    /**
     * Encodes the given value into a Yaml string using the [[thamtech\yaml\Dumper]] extension.
     *
     * @param mixed $value the data to be encoded.
     *
     * @param thamtech\yaml\Dumper|array $dumper the dumper or configuration array
     *
     * @param int $inline The level where the dumper switches to inline YAML
     *
     * @param int $flags  A bit field of Symfony\Component\Yaml\Yaml::DUMP_*
     *     constants to customize the dumped YAML string
     *
     * @return string the encoding result.
     *
     * @throws InvalidArgumentException if there is any encoding error.
     */
    public static function encode($value, $dumper = [], $inline = 2, $flags = 0)
    {
        $dumper = Instance::ensure(ArrayHelper::merge(static::defaultDumperConfig(), $dumper), Dumper::class);
        $yaml = $dumper->dump($value, $inline, 0, $flags);
        return $yaml;
    }

    /**
     * Decodes the given Yaml string into a PHP data structure using the [[thamtech\yaml\Parser]] extension.
     *
     * @param  string $yaml the Yaml string to be decoded
     *
     * @param thamtech\yaml\Pasrer|array $parser the parser or configuration array
     *
     * @param int $flags A bit field of Symfony\Component\Yaml\Yaml::PARSE_*
     *     constants to customize the YAML parser behavior.
     *     The default is PARSE_CUSTOM_TAGS.
     *
     * @return mixed the PHP data
     *
     * @throws InvalidArgumentException if there is any decoding error
     */
    public static function decode($yaml, $parser = [], $flags = 512)
    {
        $parser = Instance::ensure(ArrayHelper::merge(static::defaultParserConfig(), $parser), Parser::class);

        if (!is_scalar($yaml)) {
            throw new InvalidArgumentException('Invalid YAML data.');
        } elseif ($yaml === null || $yaml === '') {
            return null;
        }

        try {
            return $parser->parse($yaml, $flags);
        } catch (ParseException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Generates a summary of the validation errors.
     *
     * Based on [[\yii\helpers\BaseJson::errorSummary()]]
     * @copyright Copyright (c) 2008 Yii Software LLC
     * @license http://www.yiiframework.com/license/
     *
     * @param Model|Model[] $models the model(s) whose validation errors are to be displayed.
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - showAllErrors: boolean, if set to true every error message for each attribute will be shown otherwise
     *   only the first error message for each attribute will be shown. Defaults to `false`.
     *
     * @return string the generated error summary
     */
    public static function errorSummary($models, $options = [])
    {
        $showAllErrors = ArrayHelper::remove($options, 'showAllErrors', false);
        $lines = self::collectErrors($models, $showAllErrors);

        return static::encode($lines);
    }

    /**
     * Get the default parser config
     *
     * @return array
     */
    protected static function defaultParserConfig()
    {
        return [
            'class' => Parser::class,
            'on yii/helpers/UnsetArrayValue' => function ($event) {
                $taggedValue = $event->value;
                if (!empty($taggedValue->getValue())) {
                    throw new InvalidArgumentException(sprintf('A !yii/helpers/UnsetArrayValue tag cannot contain a value. The value provided was %s', json_encode($taggedValue->getValue())));
                }
                $event->value = new UnsetArrayValue();
                $event->handled = true;
            },
            'on yii/helpers/ReplaceArrayValue' => function ($event) {
                $taggedValue = $event->value;
                $event->value = new ReplaceArrayValue($taggedValue->getValue());
                $event->handled = true;
            }
        ];
    }

    /**
     * Get the default dumper config
     *
     * @return array
     */
    protected static function defaultDumperConfig()
    {
        $expPrefix = uniqid('', true);
        $expressions = [];

        return [
            'class' => Dumper::class,
            'on yii\helpers\UnsetArrayValue' => function ($event) {
                $event->value = new TaggedValue('yii/helpers/UnsetArrayValue', []);
                $event->handled = true;
            },
            'on yii\helpers\ReplaceArrayValue' => function ($event) {
                $replacement = $event->value->value;
                $event->value = new TaggedValue('yii/helpers/ReplaceArrayValue', $replacement);
                $event->handled = true;
            },
            'on yii\web\JsExpression' => function ($event) use (&$expressions, $expPrefix) {
                $data = $event->value;
                $token = "!{[$expPrefix=" . count($expressions) . ']}!';
                $expressions['"' . $token . '"'] = $data->expression;

                $event->value = $token;
                $event->handled = true;
            },
            'on ' . Dumper::EVENT_AFTER_DUMP => function ($event) use (&$expressions) {
                $yaml = $event->value;
                $event->value = $expressions === [] ? $yaml : strtr($yaml, $expressions);
            },
            'on JsonSerializable' => function ($event) {
                $event->value = Json::decode($event->value->jsonSerialize(), true);
                if ($event->value === []) {
                    $event->value = new \stdClass();
                }
                $event->handled = true;
            },
            'on yii\base\Arrayable' => function ($event) {
                $event->value = $event->value->toArray();
                if ($event->value === []) {
                    $event->value = new \stdClass();
                }
                $event->handled = true;
            },
            'on SimpleXMLElement' => function ($event) {
                $event->value = (array) $event->value;
                if ($event->value === []) {
                    $event->value = new \stdClass();
                }
                $event->handled = true;
            },
            'on ' . Dumper::EVENT_UNHANDLED_OBJECT => function ($event) {
                $result = [];
                foreach ($event->value as $name => $value) {
                    $result[$name] = $value;
                }
                $event->value = $result;

                if ($event->value === []) {
                    $event->value = new \stdClass();
                }
                $event->handled = true;
            }
        ];
    }

    /**
     * Return array of the validation errors
     *
     * From [[\yii\helpers\BaseJson::collectErrors()]]
     * @copyright Copyright (c) 2008 Yii Software LLC
     * @license http://www.yiiframework.com/license/
     *
     * @param Model|Model[] $models the model(s) whose validation errors are to be displayed.
     * @param $showAllErrors boolean, if set to true every error message for each attribute will be shown otherwise
     * only the first error message for each attribute will be shown.
     * @return array of the validation errors
     */
    private static function collectErrors($models, $showAllErrors)
    {
        $lines = [];
        if (!is_array($models)) {
            $models = [$models];
        }

        foreach ($models as $model) {
            $lines = array_unique(array_merge($lines, $model->getErrorSummary($showAllErrors)));
        }

        return $lines;
    }
}
