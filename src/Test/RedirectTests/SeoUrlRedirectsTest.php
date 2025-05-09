<?php

namespace Scop\PlatformRedirecter\Test\RedirectTests;

use Doctrine\DBAL\Connection;
use Scop\PlatformRedirecter\Test\RedirectTestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class SeoUrlRedirectsTest extends RedirectTestCase
{

    private $seoUrlIds = [], $seoUrls = [];

    public function setUp(): void
    {
        $this->createSeoUrl("/Eine/Test/Seo/Url","/detail/%s");
        $this->createSeoUrl("/Eine/Testbare/Seo/Url","/detail/%s");
        $this->createSeoUrl("/Eine/Test/Seo/Test/Url","/detail/%s");
        $this->createNotCanonicalSeoUrl("/Eine/Alte/Test/Seo/Url");

        parent::setUp();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function createSeoUrl($seo_path, $path){

        /** @var EntityRepository $productRepo */
        $productRepo = $this->getContainer()->get('product.repository');

        $data = [
            'id' => Uuid::randomHex(),
            'name' => 'Test Product',
            'productNumber' => 'P_' . rand(0, 10000000),
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'net' => 4,
                    'gross' => 10,
                    'linked' => true
                ]
            ],
            'taxId' => $this->getContainer()->get(Connection::class)->fetchOne('SELECT LOWER(HEX(id)) FROM tax WHERE tax_rate = "19.000"'),
            'stock' => 10
        ];

        $productRepo->upsert([$data], Context::createDefaultContext());

        /** @var Connection $conn */
        $conn = $this->getContainer()->get(Connection::class);

        $result = $conn->executeQuery("SELECT HEX(id) as id FROM sales_channel ORDER BY RAND() LIMIT 1");
        self::assertTrue($result->rowCount() > 0);
        $salesChannelId = $result->fetchOne();

        $result = $conn->executeQuery("SELECT HEX(id) as id FROM product ORDER BY RAND() LIMIT 1");
        self::assertTrue($result->rowCount() > 0);
        $productid = $result->fetchOne();

        $path = sprintf($path, $productid);

        $id = Uuid::randomHex();
        try {
            $conn->executeStatement("INSERT INTO seo_url (id, language_id, sales_channel_id, foreign_key, route_name, path_info, seo_path_info, is_canonical, is_modified, is_deleted, custom_fields, created_at) VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), 'frontend.detail.page', ?, ?, true, false, false, NULL, CURRENT_TIMESTAMP())", [$id, $this->getDeDeLanguageId(), $salesChannelId, $productid, $path, $seo_path]);
        } catch (\Exception $e) {
            var_dump($seo_path);
        }
        array_push($this->seoUrlIds, $id);
        array_push($this->seoUrls, [$id, $seo_path, $path, $salesChannelId, $productid]);
    }

    private function createNotCanonicalSeoUrl($seo_path){
        /** @var Connection $conn */
        $conn = $this->getContainer()->get(Connection::class);

        $otherSeo = $this->seoUrls[0];
        $path = $otherSeo[2];
        $salesChannelId = $otherSeo[3];
        $productid = $otherSeo[4];

        $path = sprintf($path, $productid);

        $id = Uuid::randomHex();
        $conn->executeStatement("INSERT INTO seo_url (id, language_id, sales_channel_id, foreign_key, route_name, path_info, seo_path_info, is_canonical, is_modified, is_deleted, custom_fields, created_at) VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), 'frontend.detail.page', ?, ?, false, false, false, NULL, CURRENT_TIMESTAMP())", [$id, $this->getDeDeLanguageId(), $salesChannelId, $productid, $path, $seo_path]);
        array_push($this->seoUrlIds, $id);
        array_push($this->seoUrls, [$id, $seo_path, $path, $salesChannelId, $productid]);
    }

    protected function getDatabaseRedirects(): array
    {
        $array = [];
        foreach ($this->seoUrls as $seoUrl){
            array_push($array, [$seoUrl[1], "https://scope01.com", 302, true]);
        }

        return $array;
    }

    public function tearDown(): void
    {

        /** @var Connection $conn */
        $conn = $this->getContainer()->get(Connection::class);
        foreach($this->seoUrlIds as $id)
        $conn->executeStatement("DELETE FROM seo_url WHERE id = UNHEX(?)", [$id]);

        parent::tearDown();
    }

    public function testSeoUrlRedirects(){
        foreach ($this->seoUrls as $seoUrl){
            $this->checkRedirect($seoUrl[1], ["https://scope01.com"], 302);
        }
    }

    public function testSeoUrlNotRedirectingOtherSeoUrl(){
        foreach ($this->seoUrls as $seoUrl){
            $this->checkRedirect($seoUrl[2], ["https://scope01.com"], 302, true);
        }
    }
}
