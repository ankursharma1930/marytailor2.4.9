<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */
declare(strict_types=1);

namespace Plumrocket\AmpEmail\Test\Unit\Model\Component\Parts\State;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class WishListTest extends TestCase
{
    /**
     * @var null | \Plumrocket\AmpEmail\Model\Component\Parts\State\Wishlist
     */
    private $block;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $wishlistProductsResolver = $this->createMock(
            \Plumrocket\AmpEmail\Model\Component\WishlistProductsResolver::class
        );
        $componentDataLocator = $this->createMock(\Plumrocket\AmpEmailApi\Api\ComponentDataLocatorInterface::class);
        $layout = $this->createMock(\Magento\Framework\View\LayoutInterface::class);

        $serializer = $objectManager->getObject(\Magento\Framework\Serialize\Serializer\Json::class);

        $this->block = (new ObjectManager($this))
            ->getObject(\Plumrocket\AmpEmail\Model\Component\Parts\State\Wishlist::class, [
                'wishlistProductsResolver' => $wishlistProductsResolver,
                'componentDataLocator' => $componentDataLocator,
                'layout' => $layout,
                'serializer' => $serializer,
            ]);
    }

    /**
     * @dataProvider convertToJsonObjectProvider
     *
     * @param $prefix
     * @param $productIds
     * @param $expectedResult
     * @throws \ReflectionException
     */
    public function testConvertToJsonObject($prefix, $productIds, $expectedResult)
    {
        $testMethod = new \ReflectionMethod(
            \Plumrocket\AmpEmail\Model\Component\Parts\State\Wishlist::class,
            'convertToJsonObject'
        );
        $testMethod->setAccessible(true);

        $this->assertEquals($expectedResult, $testMethod->invoke($this->block, $prefix, $productIds));
    }

    /**
     * @return \Generator
     */
    public function convertToJsonObjectProvider()
    {
        yield [
            'prefix' => 'p',
            'productIds' => [1,15,65],
            'expectedResult' => '{"p1":1,"p15":1,"p65":1}',
        ];
        yield [
            'prefix' => 't',
            'productIds' => [1,15,65],
            'expectedResult' => '{"t1":1,"t15":1,"t65":1}',
        ];
        yield [
            'prefix' => '',
            'productIds' => [1,15,65],
            'expectedResult' => '{}',
        ];
        yield [
            'prefix' => 'p',
            'productIds' => [],
            'expectedResult' => '{}',
        ];
    }
}
