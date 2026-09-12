<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Plumrocket\AmpEmail\Model\CssMinify;

class CssMinifyTest extends TestCase
{
    /**
     * @var null | \Plumrocket\AmpEmail\Model\CssMinify
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = (new ObjectManager($this))
            ->getObject(CssMinify::class);
    }

    /**
     * @dataProvider prepareLengthProvider
     *
     * @param $cssContent
     * @param $stringLength
     * @param $expectedResult
     * @throws \ReflectionException
     */
    public function testPrepareLength($cssContent, $stringLength, $expectedResult)
    {
        $testMethod = new \ReflectionMethod(
            CssMinify::class,
            'prepareLength'
        );
        $testMethod->setAccessible(true);

        $this->assertEquals($expectedResult, $testMethod->invoke($this->model, $cssContent, $stringLength));
    }

    /**
     * @return \Generator
     */
    public function prepareLengthProvider()
    {
        yield [
            'cssContent' => '.p{display:flex}',
            'stringLength' => 100,
            'expectedResult' => '.p{display:flex}',
        ];
        yield [
            'cssContent' => '.p{display:flex;justify-content:space-between} .product-view {top:5px}',
            'stringLength' => 50,
            'expectedResult' => ".p{display:flex;justify-content:space-between}\n .product-view {top:5px}",
        ];
    }
}
