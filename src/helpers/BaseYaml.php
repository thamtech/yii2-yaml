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
use yii\base\InvalidConfigException;
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
        static::ensureDumperDefined();
        $dumper = Instance::ensure($dumper, Dumper::class);
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
        static::ensureParserDefined();
        $parser = Instance::ensure($parser, Parser::class);

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
     * Get a definition of the parser that can be registered
     * with [[Container::setDefinitions()]].
     *
     * @param  array $config a configuration array to be merged with our
     *     default configuration
     *
     * @return array the definition array
     */
    public static function getParserDefinition(array $config = [])
    {
        return static::getDependencyDefinition(Parser::class, 'defaultParserConfig', $config);
    }

    /**
     * Get a definition of the dumper that can be registered
     * with [[Container::setDefinitions()]].
     *
     * @param  array $config a configuration array to be merged with our
     *     default configuration
     *
     * @return array the definition array
     */
    public static function getDumperDefinition(array $config = [])
    {
        return static::getDependencyDefinition(Dumper::class, 'defaultDumperConfig', $config);
    }

    /**
     * @internal
     *
     * Builds a Dumper or Parser dependency
     *
     * @param  \yii\di\Container $container
     *
     * @param  array $params parameters
     *    - `class` key - (required) the class of dependency to build
     *    - `baseConfigs` key - array of configuration arrays to merge
     *
     * @param  array $config additional configuration array to merge
     *
     * @return Dumper|Parser the dependency object, depending on the specified
     *     class parameter.
     */
    public static function buildDependency($container, $params = [], $config = [])
    {
        if (empty($params['class'])) {
            throw new InvalidConfigException('A "class" parameter must be provided to build the dependency.');
        }

        $class = $params['class'];
        unset($params['class']);

        $combinedConfig = [];
        if (is_array($params['baseConfigs'])) {
            foreach ($params['baseConfigs'] as $baseConfig) {
                if (is_callable($baseConfig)) {
                    $baseConfig = call_user_func($baseConfig);
                }
                $combinedConfig = ArrayHelper::merge($combinedConfig, $baseConfig);
            }
        }
        unset($params['baseConfigs']);

        $combinedConfig = ArrayHelper::merge($combinedConfig, $config);

        // We want to delegate to container set constructor/configurable params,
        // but we can't use the provided $container or Yii::$container since
        // they will lead us straight back to this method in an infinite loop.
        // So we create an empty container that doesn't know about $class so it
        // can just build it normally with the provided $params and
        // $combinedConfig
        $nullContainer = Yii::createObject('yii\di\Container');
        return $nullContainer->get($class, $params, $combinedConfig);
    }

    /**
     * Get a definition of the specified dependency that can be registered
     * with [[Container::setDefinitions()]].
     *
     * @param  string $class class name of the dependency
     *
     * @param  string $defaultConfigMethod name of static method that provides
     *     our default configuration array for the dependency.
     *
     * @param  array $config a configuration array to be merged with our
     *     default configuration
     *
     * @return array the definition array
     */
    protected static function getDependencyDefinition($class, $defaultConfigMethod, array $config = [])
    {
        return [
            [static::class, 'buildDependency'],
            [
                'class' => $class,
                'baseConfigs' => [
                    static::class => [static::class, $defaultConfigMethod],
                    __METHOD__ => $config,
                ],
            ],
        ];
    }

    /**
     * Get the default parser config
     *
     * @return array
     */
    protected static function defaultParserConfig()
    {
        return [
            'on yii/helpers/UnsetArrayValue' => function ($event) {
                if (!empty($event->value)) {
                    throw new InvalidArgumentException(sprintf('A !yii/helpers/UnsetArrayValue tag cannot contain a value. The value provided was %s', json_encode($event->value)));
                }
                $event->handleValue(new UnsetArrayValue());
            },
            'on yii/helpers/ReplaceArrayValue' => function ($event) {
                $event->handleValue(new ReplaceArrayValue($event->value));
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
            'on yii\helpers\UnsetArrayValue' => function ($event) {
                $event->handleValue(new TaggedValue('yii/helpers/UnsetArrayValue', null));
            },
            'on yii\helpers\ReplaceArrayValue' => function ($event) {
                $replaceArrayValue = $event->value;
                $event->handleValue(new TaggedValue('yii/helpers/ReplaceArrayValue', $replaceArrayValue->value));
            },
            'on yii\web\JsExpression' => function ($event) use (&$expressions, $expPrefix) {
                $data = $event->value;
                $token = "!{[$expPrefix=" . count($expressions) . ']}!';
                $expressions['"' . $token . '"'] = $data->expression;

                $event->handleValue($token);
            },
            'on ' . Dumper::EVENT_AFTER_DUMP => function ($event) use (&$expressions) {
                $yaml = $event->value;
                $event->value = $expressions === [] ? $yaml : strtr($yaml, $expressions);
            },
            'on JsonSerializable' => function ($event) {
                $value = Json::decode($event->value->jsonSerialize(), true);
                if ($value === []) {
                    $value = new \stdClass();
                }
                $event->handleValue($value);
            },
            'on yii\base\Arrayable' => function ($event) {
                $value = $event->value->toArray();
                if ($value === []) {
                    $value = new \stdClass();
                }
                $event->handleValue($value);
            },
            'on SimpleXMLElement' => function ($event) {
                $value = (array) $event->value;
                if ($value === []) {
                    $value = new \stdClass();
                }
                $event->handleValue($value);
            },
            'on ' . Dumper::EVENT_UNHANDLED_OBJECT => function ($event) {
                $result = [];
                foreach ($event->value as $name => $value) {
                    $result[$name] = $value;
                }

                if ($result === []) {
                    $result = new \stdClass();
                }
                $event->handleValue($result);
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

    /**
     * Ensure that a definition has been set for the specified dependency
     *
     * @param  string $class class name of the dependency
     *
     * @param  string $defaultConfigMethod name of static method that provides
     *     our default configuration array for the dependency.
     */
    private static function ensureDependencyDefined($class, $defaultConfigMethod)
    {
        if (Yii::$container->has($class)) {
            return;
        }

        Yii::$container->setDefinitions([
            $class => static::getDependencyDefinition($class, $defaultConfigMethod),
        ]);
    }

    /**
     * Ensure that a definition has been set for the dumper in `Yii::$container`
     */
    private static function ensureDumperDefined()
    {
        static::ensureDependencyDefined(Dumper::class, 'defaultDumperConfig');
    }

    /**
     * Ensure that a definition has been set for the parser in `Yii::$container`
     */
    private static function ensureParserDefined()
    {
        static::ensureDependencyDefined(Parser::class, 'defaultParserConfig');
    }
}
