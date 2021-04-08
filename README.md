Yii2 Yaml
=========

Yii2 Yaml provides a Yaml helper and a Yaml parser/dumper extension.

The Yaml helper provdes`encode()`, `decode()` and `errorSummary()`
similar to [yii\helpers\Json](https://www.yiiframework.com/doc/api/2.0/yii-helpers-json).

Yii2 Yaml also includes an extension of the
[symfony/yaml](https://github.com/symfony/yaml) library that supports custom
yaml tags representing [UnsetArrayValue](https://www.yiiframework.com/doc/api/2.0/yii-helpers-unsetarrayvalue)
and [ReplaceArrayValue](https://www.yiiframework.com/doc/api/2.0/yii-helpers-replacearrayvalue),
as well as allowing you to attach events to handle any other custom yaml tags.

For license information check the [LICENSE](LICENSE.md)-file.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

```
php composer.phar require --prefer-dist thamtech/yii2-yaml
```

or add

```
"thamtech/yii2-yaml": "*"
```

to the `require` section of your `composer.json` file.

Background
----------

The goal of this Yii2 Yaml extension is to support Yaml in Yii2 the way support
for JSON is built-in. A `Yaml` helper is introduced to match the API of Yii2's
built-in Json helper.

Furthermore, we have extended the `symfony/yaml` library to support decoding
and encoding of `ReplaceArrayValue` and `UnsetArrayValue` objects for use with
Yii's [ArrayHelper::merge()](https://www.yiiframework.com/doc/api/2.0/yii-helpers-basearrayhelper#merge%28%29-detail)
method. You can use Yii2 event handlers to process custom tags as they
are parsed/decoded and pre-process objects before they are dumped/encoded.

Yii2 uses encode/decode terminology in the Json helper, while `symfony/yaml`
uses dump/parse terminology. Our helper is consistent with the Json helper
in using the encode/decode terminology.

Usage
-----

### Decoding/Parsing

Example Yaml:

```yaml
people:
    john:
        id: 1
        name: John
    # A value must be associated with a tag: either a block value (indented
    # section under the key) or an inline value.
    # Here, we demsonstrate a block value following the !yii/helpers/ReplaceArrayValue/tag:
    bob: !yii/helpers/ReplaceArrayValue
        id: 1001
        name: Bob
    # The `unsetArrayValue` handler requires that the associated value be
    # empty, so inline `{}` or `[]` values just fine.
    jane: !yii/helpers/UnsetArrayValue {}
    susan: !lookupIdFromEmployeeNumber
        employee_number: 1234
        name: Susan
```

Example decoding using the Yaml helper:

```php
<?php
use thamtech\yaml\helpers\Yaml;

$data = Yaml::decode($yaml);
print_r($data);

# Array
# (
#     [people] => Array
#         (
#           [john] => Array
#               (
#                   [id] => 1
#                   [name] => John
#               )
#           [bob] => yii\helpers\ReplaceArrayValue Object
#               (
#                   [value] => Array
#                       (
#                           [id] => 1001
#                           [name] => Bob
#                       )
#               )
#           [jane] => yii\helpers\UnsetArrayValue Object
#               (
#               )
#           [susan] => Symfony\Component\Yaml\Tag\TaggedValue Object
#               (
#                   [tag:Symfony\Component\Yaml\Tag\TaggedValue:private] => lookupIdFromEmployeeNumber
#                   [value:Symfony\Component\Yaml\Tag\TaggedValue:private] => Array
#                       (
#                           [employee_number] => 1234
#                           [name] => Susan
#                       )
#               )
#         )
# )
```

In the example above, you can see that the keys tagged with `!yii/helpers/ReplaceArrayValue`
and `!yii/helpers/UnsetArrayValue` were automatically replaced with the helper objects
ready for use with the `ArrayHelper::merge()` method.

In order to add your own handlers for tags like the
`!lookupIdFromEmployeeNumber` tag, you can specify them in a `thamtech\yaml\Parser`
configuration array that you pass to the `Yaml::decode()` method:

```php
<?php
use thamtech\yaml\helpers\Yaml;

$data = Yaml::decode($yaml, [
    'on lookupIdFromEmployeeNumber' => function ($event) {
        // get the value associated with the `!lookupIdFromEmployeeNumber` tag
        $value = $event->value;
        
        // find the person's id and add it to the value
        $value['id'] = Employee::find()
            ->select(['id'])
            ->where(['employee_number' => $value['employee_number']])
            ->scalar();
        
        // set the updated value in the event; the value set in `value` will
        // replace the `TaggedValue` object in the parsed yaml data as long as we
        // mark that the event was handled
        $event->value = $value;
        $event->handled = true;
        
        // as a shortcut, the following is equivalent to the previous two lines:
        $event->handleValue($value);
    },
]);

print_r($data);
# Array
# (
#     [people] => Array
#         (
#           [john] => Array
#               (
#                   [id] => 1
#                   [name] => John
#               )
#           [bob] => yii\helpers\ReplaceArrayValue Object
#               (
#                   [value] => Array
#                       (
#                           [id] => 1001
#                           [name] => Bob
#                       )
#               )
#           [jane] => yii\helpers\UnsetArrayValue Object
#               (
#               )
#           [susan] => Array
#               (
#                   [employee_number] => 1234
#                   [name] => Susan
#                   [id] => 1004
#               )
#         )
# )
```

### Encoding/Dumping

Example data to encode:

```php
<?php
print_r($data);
# Array
# (
#     [people] => Array
#         (
#           [john] => Array
#               (
#                   [id] => 1
#                   [name] => John
#               )
#           [bob] => yii\helpers\ReplaceArrayValue Object
#               (
#                   [value] => Array
#                       (
#                           [id] => 1001
#                           [name] => Bob
#                       )
#               )
#           [jane] => yii\helpers\UnsetArrayValue Object
#               (
#               )
#           [susan] => Some\Package\EmployeeWithoutId Object
#               (
#                   [employee_number:Some\Package\EmployeeWithoutId:private] => 1234
#                   [name:Some\Package\EmployeeWithoutId:private] => Susan
#               )
#         )
# )
```

Dumping with the standard `symfony/yaml` library:

```php
<?php
use Symfony\Component\Yaml\Yaml;

$yaml = Yaml::dump($data);
echo $yaml;
# people:
#     john:
#         id: 1
#         name: John
#     bob: null
#     jane: null
#     susan: null
```

Dumping with our default Yaml helper:

```php
<?php
use Symfony\Component\Yaml\Yaml;

$yaml = Yaml::dump($data);
echo $yaml;
# people:
#     john:
#         id: 1
#         name: John
#     bob: !yii/helpers/ReplaceArrayValue
#         id: 1001
#         name: Bob
#     jane: !yii/helpers/UnsetArrayValue {}
#     susan: null
```

In the example above, you can see that the keys tagged with `!yii/helpers/ReplaceArrayValue`
and `!yii/helpers/UnsetArrayValue` were automatically encoded from the `ReplaceArrayValue`
and `UnsetArrayValue` objects.

In order to add your own handlers for values like the
`EmployeeWithoutId` object, you can specify them in a `thamtech\yaml\Dumper`
configuration array that you pass to the `Yaml::decode()` method:

```php
<?php
use thamtech\yaml\helpers\Yaml;
use Symfony\Component\Yaml\Tag\TaggedValue;

$yaml = Yaml::encode($data, [
    'on Some\Package\EmployeeWithoutId' => function ($event) {
        // get the EmployeeWithoutId object
        $value = $event->value;
        
        // decode the object into a TaggedValue object
        $event->value = new TaggedValue('lookupIdFromEmployeeNumber', [
            'employee_number' => $value->getEmployeeNumber(),
            'name' => $value->getName(),
        ]);
        $event->handled = true;
        
        // as a shortcut, the following is equivalent to setting `$event->value`
        // and setting `$event->handled = true`.
        $event->handleValue(
            new TaggedValue('lookupIdFromEmployeeNumber', [
                'employee_number' => $value->getEmployeeNumber(),
                'name' => $value->getName(),
            ])
        );
    },
]);

echo $yaml;
# people:
#     john:
#         id: 1
#         name: John
#     bob: !yii/helpers/ReplaceArrayValue/
#         id: 1001
#         name: Bob
#     jane: !yii/helpers/UnsetArrayValue {}
#     susan: !lookupIdFromEmployeeNumber
#         employee_number: 1234
#         name: Susan
```

### Configuring Default Handlers

You may not want to have to send a configuration array with one or more
custom handlers every time you call `Yaml::encode()` or `Yaml::decode()`.
The `Yaml` component sets its default `Parser` and `Dumper` definitions into
`Yii::$container`. You can set your own default `Parser` and `Dumper`
definitions there with your own handlers.

To not use any handlers by default (not even the `Yaml` component's default
handlers):

```php
<?php
use Yii;

Yii::$container->set('thamtech\yaml\Parser');
Yii::$container->set('thamtech\yaml\Dumper');

// alternatively, in your application configuration:
[
    'container' => [
        'definitions' => [
            'thamtech\yaml\Parser' => [],
            'thamtech\yaml\Dumper' => [],
        ],
    ],
];
```

However, it is probably more likely that you will want to start with the `Yaml`
component's default handlers and override them or add your own. The
`Yaml::getDumperDefinition()` and `Yaml::getParserDefinition()` methods are
a convenient way to get Parser and Dumper definitions ready for setting
in the `Yii::$container`.

```php
use Yii;
use thamtech\yaml\helpers\Yaml;

Yii::$container->setDefinitions([
    'thamtech\yaml\Parser' => Yaml::getParserDefinition([
        // example: we are calling getParserDefinition() to use `Yaml`'s default
        // definitions as a base, but these lines shows how we can alter those
        // default definitions. In this case, we remove the 'ReplaceArrayValue'
        // and 'UnsetArrayValue' handlers by unsetting their array keys:
        'on yii/helpers/ReplaceArrayValue' => new \yii\helpers\UnsetArrayValue(),
        'on yii/helpers/UnsetArrayValue' => new \yii\helpers\UnsetArrayValue(),
        
        // example: adding your own handler
        'on lookupIdFromEmployeeNumber' => function ($event) {
            // get the value associated with the `!lookupIdFromEmployeeNumber` tag
            $value = $event->value;
            
            // find the person's id and add it to the value
            $value['id'] = Employee::find()
                ->select(['id'])
                ->where(['employee_number' => $value['employee_number']])
                ->scalar();
            
            // set the updated value in the event; the value set in `value` will
            // replace the `TaggedValue` object in the parsed yaml data as long as we
            // mark that the event was handled
            $event->value = $value;
            $event->handled = true;
            
            // as a shortcut, the following is equivalent to the previous two lines:
            $event->handleValue($value);
        },
    ]),
    'thamtech\yaml\Dumper' => Yaml::getDumperDefinition([
        // example: we are calling getDumperDefinition() to use `Yaml`'s default
        // definitions as a base, but these lines shows how we can alter those
        // default definitions. In this case, we remove the 'ReplaceArrayValue'
        // and 'UnsetArrayValue' handlers by unsetting their array keys:
        'on yii/helpers/ReplaceArrayValue' => new \yii\helpers\UnsetArrayValue(),
        'on yii/helpers/UnsetArrayValue' => new \yii\helpers\UnsetArrayValue(),
        
        // example: adding your own handler
        'on Some\Package\EmployeeWithoutId' => function ($event) {
            // get the EmployeeWithoutId object
            $value = $event->value;
            
            // decode the object into a TaggedValue object
            $event->value = new TaggedValue('lookupIdFromEmployeeNumber', [
                'employee_number' => $value->getEmployeeNumber(),
                'name' => $value->getName(),
            ]);
            $event->handled = true;
            
            // as a shortcut, the following is equivalent to setting `$event->value`
            // and setting `$event->handled = true`.
            $event->handleValue(
                new TaggedValue('lookupIdFromEmployeeNumber', [
                    'employee_number' => $value->getEmployeeNumber(),
                    'name' => $value->getName(),
                ])
            );
        },
    ]),
]);


// alternatively, in your application configuration:
[
    'container' => [
        'definitions' => [
            'thamtech\yaml\Parser' => Yaml::getParserDefinition([
                // ...
            ]),
            'thamtech\yaml\Dumper' => Yaml::getDumperDefinition([
                // ...
            ]),
        ],
    ],
];
```

### Yaml Response Formatter

You can add the `YamlResponseFormatter` as a `yaml` formatter in your
`yii\web\Response` component to add support for returning yaml responses.

```php
<?php
// ... config ...
return [
    'response' => [
        'formatters' => [
            'yaml' => [
                'class' => 'thamtech\yaml\web\YamlResponseFormatter',
                
                // you can define your own dumper config like the earlier
                // examples:
                'dumper' => [
                    // `'class' => 'thamtech\yaml\Dumper'` is assumed, but can
                    // be overridden if you extend 'thamtech\yaml\Dumper'
                    
                    'on Some\Package\EmployeeWithoutId' => function ($event) {
                        // ... handle event, see earlier example ...
                    },
                ],
            ],
        ],
    ],
];
```

See Also
--------

* [Symfony Yaml component](https://symfony.com/components/Yaml)
