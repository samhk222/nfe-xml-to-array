<?php

namespace Yousee\Marketplace\Tests;

use Yousee\Marketplace\Config;
use Yousee\Marketplace\Sample;

/**
 * Class SampleTest
 *
 * @category Test
 * @package  Yousee\Marketplace\Tests
 * @author   Mahmoud Zalt <mahmoud@zalt.me>
 */
class SampleTest extends TestCase
{

    public function testSayHello()
    {
        $config = new Config();
        $sample = new Sample($config);

        $name = 'Mahmoud Zalt';

        $result = $sample->sayHello($name);

        $expected = $config->get('greeting') . ' ' . $name;

        $this->assertEquals($result, $expected);

    }

}
