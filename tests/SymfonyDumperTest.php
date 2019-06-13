<?php

namespace thamtechunit\yaml;

use Symfony\Component\Yaml\Tests\DumperTest;
use Yii;

class SymfonyDumperTest extends DumperTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->parser = Yii::createObject('thamtech\yaml\Parser');
        $this->dumper = Yii::createObject('thamtech\yaml\Dumper');
    }

    public function testIndentationInConstructor()
    {
        $this->assertTrue(true); // test does not apply to our implementation
    }

    public function testIndentationInConfig()
    {
        $dumper = Yii::createObject([
            'class' => 'thamtech\yaml\Dumper',
            'defaultIndentation' => 7,
        ]);
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

EOF;
        $this->assertEquals($expected, $dumper->dump($this->array, 4, 0));
    }

    public function testSetIndentation()
    {
        $this->assertTrue(true); // test does not apply to our implementation
    }

    public function testSpecifyDumper()
    {
        $dumper = Yii::createObject([
            'class' => 'thamtech\yaml\Dumper',
            'dumper' => new \Symfony\Component\Yaml\Dumper(7),
        ]);

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

EOF;
        $this->assertEquals($expected, $dumper->dump($this->array, 4, 0));
    }

    public function testObjectSupportEnabledPassingTrue()
    {
        $this->assertTrue(true); // test does not apply to our implementation
    }
}
