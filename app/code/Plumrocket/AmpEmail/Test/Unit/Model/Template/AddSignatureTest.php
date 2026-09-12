<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\AmpEmail\Test\Unit\Model\Template;

use Magento\Framework\Filter\Template\SignatureProvider;
use PHPUnit\Framework\TestCase;
use Plumrocket\AmpEmail\Model\Magento\VersionProvider;
use Plumrocket\AmpEmail\Model\Template\AddSignature;

/**
 * @since 2.2.0
 */
class AddSignatureTest extends TestCase
{

    /**
     * @var (\Plumrocket\AmpEmail\Model\Magento\VersionProvider&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $versionProviderMock;

    /**
     * @var \Plumrocket\AmpEmail\Model\Template\AddSignature
     */
    private $model;

    protected function setUp(): void
    {
        $this->versionProviderMock = $this->getMockBuilder(VersionProvider::class)
             ->onlyMethods(['getMagentoVersion'])
             ->disableOriginalConstructor()
             ->getMock();

        $signatureProviderMock = $this->createMock(SignatureProvider::class);
        $signatureProviderMock->method('get')->willReturn('9s9AfxZYdCrHe5cLWJmPZGSjqocDGlOY');

        $this->model = $this->getMockBuilder(AddSignature::class)
                            ->onlyMethods(['getSignatureProvider'])
                            ->setConstructorArgs([$this->versionProviderMock])
                            ->getMock();
        $this->model->method('getSignatureProvider')->willReturn($signatureProviderMock);
    }

    /**
     * @return void
     */
    public function testOlderMagentoVersion()
    {
        $this->versionProviderMock->method('getMagentoVersion')->willReturn('2.4.5');
        self::assertSame('{{view a=1}}', $this->model->execute('{{view a=1}}', 'view'));
    }

    /**
     * @return void
     */
    public function testNotExistingDirective()
    {
        $this->versionProviderMock->method('getMagentoVersion')->willReturn('2.4.6');
        self::assertSame('{{view a=1}}', $this->model->execute('{{view a=1}}', 'test'));
    }

    /**
     * @return void
     */
    public function testSinging()
    {
        $this->versionProviderMock->method('getMagentoVersion')->willReturn('2.4.6');
        self::assertSame(
            '9s9AfxZYdCrHe5cLWJmPZGSjqocDGlOY{{view a=1}}9s9AfxZYdCrHe5cLWJmPZGSjqocDGlOY',
            $this->model->execute('{{view a=1}}', 'view')
        );
    }
}
