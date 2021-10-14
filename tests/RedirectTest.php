<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types=1);

/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformDirecter\Tests;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use GuzzleHttp\Client;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Util\PaymentStatusUtilV2;

/**
 * Class RedirectTest
 * @package Scop\PlatformDirecter\Tests
 */
class RedirectTest extends TestCase
{

    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use OrderFixture;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;

    /**
     * @var PaymentStatusUtilV2
     */
    private $paymentStatusUtil;

    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var string $host The hostname for Requests.
     */
    public $host = "http://shopware-platform.local";

    /**
     * dummy redirects and expected results
     *
     * @var array[]
     */
    protected $set = [
        [
            "/wau",
            "/Aerodynamic-Aluminum-Wordlobster/7958b4c7e4f74220981f091454b2484e",
            302
        ],
        [
            "/details",
            "/checkout/cart/",
            301
        ],
        [
            "/test",
            "/account/",
            301
        ],
        [
            "/googling",
            "www.google.com",
            302
        ],
        [
            "/google",
            "/account/",
            302
        ],
        [
            "/men",
            "/checkout/",
            301
        ],
        [
            "/women",
            "/checkout?c=5",
            301
        ],
        [
            "/dummy",
            "/index",
            301
        ]
    ];

    /**
     * Set up test case
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository $entityRepo */
        $entityRepo = $this->getContainer()->get("scop_platform_redirecter_redirect.repository");
        foreach ($this->set as $testRedirect) {
            $data = [
                'id' => Uuid::randomHex(),
                'sourceURL' => $testRedirect[0],
                'targetURL' => $testRedirect[1],
                'httpCodes' => $testRedirect[2],
                'created_at' => (new \DateTime())
            ];
            $entityRepo->create([$data], Context::createDefaultContext());
        }
    }

    /**
     * Test redirect status codes
     */
    public function testRedirectStatusCodes(): void
    {
        $client = new Client([
            'base_uri' => $this->host,
            'http_errors' => false
        ]);

        foreach ($this->set as $testreDirect) {
            $response = $client->get($testreDirect[0], [
                'allow_redirects' => false
            ]);

            self::assertSame($response->getStatusCode(), $testreDirect[2]);
        }
    }

    /**
     * Test redirect links
     */
    public function testRedirectLinks(): void
    {
        $client = new Client([
            'base_uri' => $this->host,
            'http_errors' => false
        ]);

        foreach ($this->set as $testredirect) {
            $response = $client->get($testredirect[0], [
                'allow_redirects' => false
            ]);

            $has = $response->hasHeader("location");
            self::assertTrue($has);
            if ($has) {
                self::assertContains($response->getHeader("location")[0], [$testredirect[1], "http://" . $testredirect[1]]);
            }
        }
    }
}
