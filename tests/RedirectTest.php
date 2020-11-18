<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types = 1);
namespace Scop\PlatformDirecter\Tests;

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use GuzzleHttp\Client;

/**
 *  SQL doesn't work. But if you insert the required Redirects by hand, the tests work sucessfully.
 */
class RedirectTest extends TestCase
{

    use AdminFunctionalTestBehaviour;

    /** @var Connection $connection **/
    public $connection;

    /**
     *
     * @var string $host The hostname for Requests.
     */
    public $host = "http://localhost";

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

    public function setUp(): void
    {
        parent::setUp();
        $this->clearCacheData();
        $this->connection = $this->getContainer()->get(Connection::class);
        $createString = "";
        foreach ($this->set as $testredirect) {
            $createString = $createString . ", (" . rand(100000, 1000000) . ", '" . $testredirect[0] . "', '" . $testredirect[1] . "', " . $testredirect[2] . ", Current_Timestamp())";
        }
        $createString = substr($createString, 2);

        $sql = <<<SQL
        INSERT INTO shopware.scop_platform_redirecter_redirect (id, sourceURL, targetURL, httpCode, created_at) VALUES $createString;
SQL;
        $rows = $this->connection->executeUpdate($sql);
        self::assertCount($rows, $this->set);
        $this->clearCacheData();
    }

    /**
     *
     */
    public function testRedirectStatusCodes(): void
    {
        $client = new Client([
            'base_uri' => $this->host,
            'http_errors' => false
        ]);

        foreach ($this->set as $testredirect) {
            $response = $client->get($testredirect[0], [
                'allow_redirects' => false
            ]);

            self::assertSame($response->getStatusCode(), $testredirect[2]);
        }
    }

    /**
     *
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
            if($has){
                self::assertContains($response->getHeader("location")[0], [$testredirect[1], "http://" . $testredirect[1]]);
            }
        }
    }
}
