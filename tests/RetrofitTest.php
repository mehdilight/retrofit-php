<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retrofit;

interface TestApiService
{
    #[GET('/users')]
    public function getUsers(): array;

    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[GET('/users')]
    public function getUsersWithQuery(#[Query('page')] int $page, #[Query('limit')] int $limit): array;

    #[POST('/users')]
    public function createUser(#[Body] array $data): array;

    #[PUT('/users/{id}')]
    public function updateUser(#[Path('id')] int $id, #[Body] array $data): array;

    #[DELETE('/users/{id}')]
    public function deleteUser(#[Path('id')] int $id): bool;
}

class RetrofitTest extends TestCase
{
    private HttpClient $httpClient;
    private Retrofit $retrofit;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($this->httpClient)
            ->build();
    }

    public function testCreateThrowsForNonInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an interface');

        $this->retrofit->create(\stdClass::class);
    }

    public function testCreateReturnsImplementation(): void
    {
        $this->httpClient->method('execute')->willReturn(
            new Response(200, 'OK', [], [], '[]')
        );

        $service = $this->retrofit->create(TestApiService::class);

        $this->assertInstanceOf(TestApiService::class, $service);
    }

    public function testGetRequest(): void
    {
        $expectedUsers = [['id' => 1, 'name' => 'John']];

        $this->httpClient
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (Request $request) {
                return $request->method === 'GET'
                    && $request->url === 'https://api.example.com/users';
            }))
            ->willReturn(new Response(200, 'OK', $expectedUsers, [], ''));

        $service = $this->retrofit->create(TestApiService::class);
        $result = $service->getUsers();

        $this->assertSame($expectedUsers, $result);
    }

    public function testGetRequestWithPathParameter(): void
    {
        $expectedUser = ['id' => 42, 'name' => 'John'];

        $this->httpClient
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (Request $request) {
                return $request->method === 'GET'
                    && $request->url === 'https://api.example.com/users/42';
            }))
            ->willReturn(new Response(200, 'OK', $expectedUser, [], ''));

        $service = $this->retrofit->create(TestApiService::class);
        $result = $service->getUser(42);

        $this->assertSame($expectedUser, $result);
    }

    public function testGetRequestWithQueryParameters(): void
    {
        $expectedUsers = [['id' => 1], ['id' => 2]];

        $this->httpClient
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (Request $request) {
                return $request->method === 'GET'
                    && $request->url === 'https://api.example.com/users'
                    && $request->query === ['page' => 2, 'limit' => 10];
            }))
            ->willReturn(new Response(200, 'OK', $expectedUsers, [], ''));

        $service = $this->retrofit->create(TestApiService::class);
        $result = $service->getUsersWithQuery(2, 10);

        $this->assertSame($expectedUsers, $result);
    }

    public function testPostRequestWithBody(): void
    {
        $inputData = ['name' => 'John', 'email' => 'john@example.com'];
        $expectedResponse = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];

        $this->httpClient
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (Request $request) use ($inputData) {
                return $request->method === 'POST'
                    && $request->url === 'https://api.example.com/users'
                    && $request->body === $inputData;
            }))
            ->willReturn(new Response(201, 'Created', $expectedResponse, [], ''));

        $service = $this->retrofit->create(TestApiService::class);
        $result = $service->createUser($inputData);

        $this->assertSame($expectedResponse, $result);
    }

    public function testPutRequestWithPathAndBody(): void
    {
        $inputData = ['name' => 'John Updated'];
        $expectedResponse = ['id' => 42, 'name' => 'John Updated'];

        $this->httpClient
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (Request $request) use ($inputData) {
                return $request->method === 'PUT'
                    && $request->url === 'https://api.example.com/users/42'
                    && $request->body === $inputData;
            }))
            ->willReturn(new Response(200, 'OK', $expectedResponse, [], ''));

        $service = $this->retrofit->create(TestApiService::class);
        $result = $service->updateUser(42, $inputData);

        $this->assertSame($expectedResponse, $result);
    }

    public function testDeleteRequest(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (Request $request) {
                return $request->method === 'DELETE'
                    && $request->url === 'https://api.example.com/users/42';
            }))
            ->willReturn(new Response(204, 'No Content', true, [], ''));

        $service = $this->retrofit->create(TestApiService::class);
        $result = $service->deleteUser(42);

        $this->assertTrue($result);
    }
}
