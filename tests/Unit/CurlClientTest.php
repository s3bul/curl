<?php
declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\HttpCode;
use CurlHandle;
use S3bul\Client\CurlClient;
use Tests\Support\UnitTester;

class CurlClientTest extends Unit
{
    const SERVICE_URI = 'https://gorest.co.in/public/v2/users';

    protected UnitTester $tester;

    protected function _before(): void
    {
    }

    public function testWhenCreateClientExpectProperlyTypesAndValues(): void
    {
        $url = uniqid('url_');
        $curl = new CurlClient();
        $curl->init($url, [CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_RETURNTRANSFER => false]);
        $this->tester->assertInstanceOf(CurlHandle::class, $curl->getHandle());
        $this->tester->assertEquals($url, $curl->getUrl());
        $this->tester->assertIsArray($curl->getHeaders());
        $this->tester->assertIsArray($curl->getCookies());
        $this->tester->assertIsArray($curl->getOptions());
        $this->tester->assertArrayHasKey(CURLOPT_URL, $curl->getOptions());
        $this->tester->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $curl->getOptions());
        $this->tester->assertArrayHasKey(CURLOPT_RETURNTRANSFER, $curl->getOptions());
        $this->tester->assertEquals(2, $curl->getOption(CURLOPT_SSL_VERIFYHOST));
        $this->tester->assertEquals(false, $curl->getOption(CURLOPT_RETURNTRANSFER));
    }

    public function testWhenGetNotExistsUsersExpectJsonStructure(): void
    {
        $curl = new CurlClient();
        $curl->init(self::SERVICE_URI . '/0')
            ->get()
            ->getResponse();
        $this->tester->assertEquals(HttpCode::NOT_FOUND, $curl->getCurlInfoHttpCode());
    }

    public function testWhenGetUsersExpectJsonStructure(): void
    {
        $curl = new CurlClient();
        $response = $curl->init(self::SERVICE_URI)
            ->get()
            ->getResponse();
        $this->tester->assertJson($response);
        $decoded = json_decode($response);
        $this->tester->assertIsArray($decoded);
    }

    private function whenCreateUserExpectEmailAsTheSame(): int
    {
        $curl = new CurlClient();
        $email = uniqid('curl_') . '@s3bul.pl';
        $response = $curl->addHeader('Authorization', 'Bearer ' . getenv('TEST_API_TOKEN'))
            ->init(self::SERVICE_URI)
            ->post([
                'email' => $email,
                'name' => 's3bul',
                'gender' => 'male',
                'status' => 'active',
            ], false)
            ->getResponse();

        $this->tester->assertJson($response);
        $decoded = json_decode($response);
        $this->tester->assertIsObject($decoded);
        $userId = $decoded->id;
        $this->tester->assertIsInt($userId);
        $this->tester->assertEquals($email, $decoded->email);
        return $userId;
    }

    private function whenDeleteUserExpectHttpCodeIsNoContent(int $userId): void
    {
        $curl = new CurlClient();
        $curl->addHeader('Authorization', 'Bearer ' . getenv('TEST_API_TOKEN'))
            ->init(self::SERVICE_URI . "/$userId")
            ->delete()
            ->getResponse();

        $this->tester->assertEquals(HttpCode::NO_CONTENT, $curl->getCurlInfoHttpCode());
    }

    public function testWhenCreateAndDeleteUserExpectEmailAsTheSameAfterCreateAndHttpCodeIs204AfterDelete(): void
    {
        $userId = $this->whenCreateUserExpectEmailAsTheSame();
        $this->whenDeleteUserExpectHttpCodeIs204($userId);
    }

}
