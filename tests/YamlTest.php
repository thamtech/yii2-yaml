<?php

namespace thamtechunit\yaml;

use thamtech\yaml\helpers\Yaml;
use yii\helpers\ArrayHelper;
use Yii;

class YamlTest extends \thamtechunit\yaml\TestCase
{
    protected $array;

    protected function setUp()
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
}
