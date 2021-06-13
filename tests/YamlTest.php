<?php

namespace thamtechunit\yaml;

use thamtech\yaml\helpers\Yaml;
use yii\helpers\ArrayHelper;
use Yii;

class TestArrayable extends \yii\base\Model
{
    public $foo = 'foo3';
    public $bar = 'bar7';

    public function getFooBar()
    {
        return 'foobar11';
    }

    public function fields()
    {
        return array_merge(parent::fields(), [
            'fooBar' => 'fooBar',
        ]);
    }
}

class YamlTest extends \thamtechunit\yaml\TestCase
{
    protected $array;

    protected function setUp(): void
    {
        parent::setUp();
        $this->array = [
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => [],
            'bar' => [1, 'foo'],
            'foobar' => [
                'foo' => 'bar',
                'bar' => [1, 'foo'],
                'foobar' => [
                    'foo' => 'bar',
                    'bar' => [1, 'foo'],
                ],
            ],
            'myCustomObj' => Yii::createObject(['class' => 'yii\rbac\Assignment', 'roleName' => 'foo', 'userId' => 12]),
            'myCustomTag' => new \Symfony\Component\Yaml\Tag\TaggedValue('customTag', ['foo' => [1, 'bar']]),
            'myUnset' => new \yii\helpers\UnsetArrayValue(),
            'myReplace' => new \yii\helpers\ReplaceArrayValue([
                'foo' => [1, 'bar'],
            ]),
        ];
    }

    public function testDecodeNonScalar()
    {
        $this->expectException('yii\base\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid YAML data.');
        Yaml::decode([]);
    }

    public function testDecodeEmpty()
    {
        $this->assertNull(Yaml::decode(''));
    }

    public function testEncodeArrayable()
    {
        $value = new TestArrayable();
        $arr = $value->toArray();
        $this->assertEquals([
            'foo' => 'foo3',
            'bar' => 'bar7',
            'fooBar' => 'foobar11',
        ], $arr);

        $expected = <<<'EOF'
foo: foo3
bar: bar7
fooBar: foobar11

EOF;

        $this->assertEquals($expected, Yaml::encode($value, [], 4, 0));
    }

    public function testEncodeDefault()
    {
        $expected = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj:
    userId: 12
    roleName: foo
    createdAt: null
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;
        $this->assertEquals($expected, Yaml::encode($this->array, [], 4, 0));
    }

    public function testEncodeWithHandlers()
    {
        $expected = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj:
    customObjVal:
        id: 12
        role: foo
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;
        $config = [
            'on yii\rbac\Assignment' => function ($event) {
                $event->handleValue([
                    'customObjVal' => [
                        'id' => $event->value->userId,
                        'role' => $event->value->roleName,
                    ],
                ]);
            }
        ];
        $this->assertEquals($expected, Yaml::encode($this->array, $config, 4, 0));
    }

    public function testDecodeProvidedDefaultParserWithHandlers()
    {
        $this->assertFalse(Yii::$container->has('thamtech\yaml\Parser'));
        Yii::$container->set('thamtech\yaml\Parser');

        $yaml = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj:
    userId: 12
    roleName: foo
    createdAt: null
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;

        $expected = [
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => [],
            'bar' => [1, 'foo'],
            'foobar' => [
                'foo' => 'bar',
                'bar' => [1, 'foo'],
                'foobar' => [
                    'foo' => 'bar',
                    'bar' => [1, 'foo'],
                ],
            ],
            'myCustomObj' => [
                'userId' => 12,
                'roleName' => 'foo',
                'createdAt' => null,
            ],
            'myCustomTag' => new \Symfony\Component\Yaml\Tag\TaggedValue('customTag', ['foo' => [1, 'bar']]),
            'myUnset' => new \yii\helpers\UnsetArrayValue(),
            'myReplace' => new \Symfony\Component\Yaml\Tag\TaggedValue('yii/helpers/ReplaceArrayValue', ['foo' => [1, 'bar']]),
        ];

        $this->assertEquals($expected, Yaml::decode($yaml, [
            'on yii/helpers/UnsetArrayValue' => function ($event) {
                $event->handleValue(new \yii\helpers\UnsetArrayValue());
            },
        ]));
    }

    public function testDecodeProvidedDefaultParser()
    {
        $this->assertFalse(Yii::$container->has('thamtech\yaml\Parser'));
        Yii::$container->set('thamtech\yaml\Parser');

        $yaml = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj:
    userId: 12
    roleName: foo
    createdAt: null
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;

        $expected = [
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => [],
            'bar' => [1, 'foo'],
            'foobar' => [
                'foo' => 'bar',
                'bar' => [1, 'foo'],
                'foobar' => [
                    'foo' => 'bar',
                    'bar' => [1, 'foo'],
                ],
            ],
            'myCustomObj' => [
                'userId' => 12,
                'roleName' => 'foo',
                'createdAt' => null,
            ],
            'myCustomTag' => new \Symfony\Component\Yaml\Tag\TaggedValue('customTag', ['foo' => [1, 'bar']]),
            'myUnset' => new \Symfony\Component\Yaml\Tag\TaggedValue('yii/helpers/UnsetArrayValue', []),
            'myReplace' => new \Symfony\Component\Yaml\Tag\TaggedValue('yii/helpers/ReplaceArrayValue', ['foo' => [1, 'bar']]),
        ];

        $this->assertEquals($expected, Yaml::decode($yaml, []));
    }

    public function testDecodeProvidedCustomParser()
    {
        $this->assertFalse(Yii::$container->has('thamtech\yaml\Parser'));
        Yii::$container->setDefinitions([
            'thamtech\yaml\Parser' => Yaml::getParserDefinition([
                'on customTag' => function ($event) {
                    $event->handleValue(new \yii\helpers\UnsetArrayValue());
                },
                'on yii/helpers/UnsetArrayValue' => new \yii\helpers\UnsetArrayValue(),
            ]),
        ]);

        $yaml = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj:
    userId: 12
    roleName: foo
    createdAt: null
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;

        $expected = [
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => [],
            'bar' => [1, 'foo'],
            'foobar' => [
                'foo' => 'bar',
                'bar' => [1, 'foo'],
                'foobar' => [
                    'foo' => 'bar',
                    'bar' => [1, 'foo'],
                ],
            ],
            'myCustomObj' => [
                'userId' => 12,
                'roleName' => 'foo',
                'createdAt' => null,
            ],
            'myCustomTag' => new \yii\helpers\UnsetArrayValue(),
            'myUnset' => new \Symfony\Component\Yaml\Tag\TaggedValue('yii/helpers/UnsetArrayValue', []),
            'myReplace' => new \yii\helpers\ReplaceArrayValue([
                'foo' => [1, 'bar'],
            ]),
        ];

        $this->assertEquals($expected, Yaml::decode($yaml, []));
    }

    public function testDecodeDefault()
    {
        $yaml = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj:
    userId: 12
    roleName: foo
    createdAt: null
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;

        $expected = [
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => [],
            'bar' => [1, 'foo'],
            'foobar' => [
                'foo' => 'bar',
                'bar' => [1, 'foo'],
                'foobar' => [
                    'foo' => 'bar',
                    'bar' => [1, 'foo'],
                ],
            ],
            'myCustomObj' => [
                'userId' => 12,
                'roleName' => 'foo',
                'createdAt' => null,
            ],
            'myCustomTag' => new \Symfony\Component\Yaml\Tag\TaggedValue('customTag', ['foo' => [1, 'bar']]),
            'myUnset' => new \yii\helpers\UnsetArrayValue(),
            'myReplace' => new \yii\helpers\ReplaceArrayValue([
                'foo' => [1, 'bar'],
            ]),
        ];

        $this->assertEquals($expected, Yaml::decode($yaml, []));
    }

    public function testDecodeWithHandlers()
    {
        $yaml = <<<'EOF'
'': bar
foo: '#bar'
'foo''bar': {  }
bar:
    - 1
    - foo
foobar:
    foo: bar
    bar:
        - 1
        - foo
    foobar:
        foo: bar
        bar:
            - 1
            - foo
myCustomObj: !rbacAssignment
    userId: 12
    roleName: foo
    createdAt: null
myCustomTag: !customTag { foo: [1, bar] }
myUnset: !yii/helpers/UnsetArrayValue {  }
myReplace: !yii/helpers/ReplaceArrayValue { foo: [1, bar] }

EOF;

        $expected = [
            '' => 'bar',
            'foo' => '#bar',
            'foo\'bar' => [],
            'bar' => [1, 'foo'],
            'foobar' => [
                'foo' => 'bar',
                'bar' => [1, 'foo'],
                'foobar' => [
                    'foo' => 'bar',
                    'bar' => [1, 'foo'],
                ],
            ],
            'myCustomObj' => Yii::createObject([
                'class' => 'yii\rbac\Assignment',
                'userId' => 12,
                'roleName' => 'foo',
                'createdAt' => null,
            ]),
            'myCustomTag' => new \Symfony\Component\Yaml\Tag\TaggedValue('customTag', ['foo' => [1, 'bar']]),
            'myUnset' => new \yii\helpers\UnsetArrayValue(),
            'myReplace' => new \yii\helpers\ReplaceArrayValue([
                'foo' => [1, 'bar'],
            ]),
        ];
        $config = [
            'on rbacAssignment' => function ($event) {
                $event->handleValue(
                    Yii::createObject(
                        ArrayHelper::merge([
                            'class' => 'yii\rbac\Assignment',
                        ], $event->value)
                    )
                );
            }
        ];

        $this->assertEquals($expected, Yaml::decode($yaml, $config));
    }

    public function testDecodeWrappedValueProcessingWithInnerTagHandler()
    {
        $yaml = <<<'EOF'
root:
    tag: !tagged
        foo: bar
        inner: !yii/helpers/ReplaceArrayValue
            abc: def

EOF;

        $expected = [
            'root' => [
                'tag' => new \Symfony\Component\Yaml\Tag\TaggedValue('tagged', [
                    'foo' => 'bar',
                    'inner' => new \yii\helpers\ReplaceArrayValue([
                        'abc' => 'def',
                    ]),
                ]),
            ],
        ];

        $this->assertEquals($expected, Yaml::decode($yaml));
    }

    public function testDecodeWrappedValueProcessingWithInnerAndOuterTagHandlers()
    {
        $yaml = <<<'EOF'
root:
    control: !join
        - abc
        - 123
    item: !yii/helpers/ReplaceArrayValue
        foo: bar
        experimental: !join
            - def
            - 456

EOF;

        $expected = [
            'root' => [
                'control' => 'abc-123',
                'item' => new \yii\helpers\ReplaceArrayValue([
                    'foo' => 'bar',
                    'experimental' => 'def-456',
                ])
            ],
        ];

        $eventValue = null;

        $config = [
            'on join' => function ($event) {
                $event->handleValue(join('-', $event->value));
            },
            'on Symfony\Component\Yaml\Tag\TaggedValue-join' => function ($event) use (&$eventValue) {
                $eventValue = $event->getValue();
            },
        ];

        $this->assertEquals($expected, Yaml::decode($yaml, $config));

        $this->assertNotEmpty($eventValue);
        $this->assertInstanceOf(\Symfony\Component\Yaml\Tag\TaggedValue::class, $eventValue);
    }
}
