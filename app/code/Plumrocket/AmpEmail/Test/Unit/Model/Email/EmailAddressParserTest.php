<?php
/**
 * @package     Plumrocket_AmpEmail
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\AmpEmail\Test\Unit\Model\Email;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Plumrocket\AmpEmail\Model\Email\EmailAddressParser;

class EmailAddressParserTest extends TestCase
{
    /**
     * @var EmailAddressParser
     */
    private $emailAddressValidator;

    protected function setUp(): void
    {
        $emailValidator = (new ObjectManager($this))->getObject(\Magento\Framework\Validator\EmailAddress::class);

        $this->emailAddressValidator = (new ObjectManager($this))->getObject(
            EmailAddressParser::class,
            ['emailValidator' => $emailValidator]
        );
    }

    /**
     * @dataProvider emailProvider
     *
     * @param string $string
     * @param array $expected
     */
    public function testGetValidEmails(string $string, array $expected)
    {
        $this->assertSame($expected, $this->emailAddressValidator->getValidEmails($string));
    }

    public function emailProvider()
    {
        yield [
            'string' => '',
            'expected' => [],
        ];

        yield [
            'string' => 'test@example.com',
            'expected' => [
                'test@example.com'
            ],
        ];

        yield [
            'string' => 'Test < test@example.com >',
            'expected' => [
                'test@example.com'
            ],
        ];

        yield [
            'string' => 'Test < a.test@example.com.te >',
            'expected' => [
                'a.test@example.com.te'
            ],
        ];

        yield [
            'string' => 'Test <test@example.com>',
            'expected' => [
                'test@example.com'
            ],
        ];

        yield [
            'string' => 'Test <test@example.com>, Test <test1@example.com>',
            'expected' => [
                'test@example.com',
                'test1@example.com',
            ],
        ];

        yield [
            'string' => 'Test <test@example.com>, Test <test@example.com>',
            'expected' => [
                'test@example.com',
            ],
        ];

        yield [
            'string' => "test@example.com\ntest@example.com",
            'expected' => [
                'test@example.com',
            ],
        ];

        yield [
            'string' => 'test@example.com
            test@example.com',
            'expected' => [
                'test@example.com',
            ],
        ];

        yield [
            'string' => 'test1@example.com
            @example.com',
            'expected' => [
                'test1@example.com',
            ],
        ];

        yield [
            'string' => 'test1@example .com',
            'expected' => [],
        ];
    }
}
