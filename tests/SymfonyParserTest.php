<?php

namespace thamtechunit\yaml;

use Symfony\Component\Yaml\Tests\ParserTest;
use Yii;

class SymfonyParserTest extends ParserTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = Yii::createObject('thamtech\yaml\Parser');
    }
}
