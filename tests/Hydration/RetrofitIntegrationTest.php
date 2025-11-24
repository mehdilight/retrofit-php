<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\SimpleUser;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\UserWithAddress;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\UserWithSerializedName;

interface TestUserApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): SimpleUser;

    #[GET('/users')]
    public function getUsers(): array;

    #[GET('/users')]
    #[ResponseType(SimpleUser::class, isArray: true)]
    public function getUsersTyped(): array;

    #[GET('/users/{id}')]
    public function getUserWithAddress(#[Path('id')] int $id): UserWithAddress;

    #[GET('/users/{id}')]
    public function getUserWithSerializedName(#[Path('id')] int $id): UserWithSerializedName;

    #[POST('/users')]
    public function createUser(#[Body] SimpleUser $user): SimpleUser;
}

class RetrofitIntegrationTest extends TestCase
{
    public function testGetUserReturnsTypedObject(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ])),
        ]);

        $retrofit = $this->createRetrofit($mock);
        $api = $retrofit->create(TestUserApi::class);

        $user = $api->getUser(1);

        $this->assertInstanceOf(SimpleUser::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    public function testGetUsersReturnsArrayOfObjects(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
            ])),
        ]);

        $retrofit = $this->createRetrofit($mock);
        $api = $retrofit->create(TestUserApi::class);

        $users = $api->getUsers();

        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        // Note: Without type hint, returns array (not typed objects)
        // This tests backward compatibility
    }

    public function testGetUsersTypedWithResponseTypeAttribute(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
                ['id' => 3, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ])),
        ]);

        $retrofit = $this->createRetrofit($mock);
        $api = $retrofit->create(TestUserApi::class);

        $users = $api->getUsersTyped();

        $this->assertIsArray($users);
        $this->assertCount(3, $users);

        // With ResponseType attribute, each item should be hydrated to SimpleUser
        $this->assertInstanceOf(SimpleUser::class, $users[0]);
        $this->assertInstanceOf(SimpleUser::class, $users[1]);
        $this->assertInstanceOf(SimpleUser::class, $users[2]);

        $this->assertSame(1, $users[0]->id);
        $this->assertSame('John', $users[0]->name);
        $this->assertSame('john@example.com', $users[0]->email);

        $this->assertSame(2, $users[1]->id);
        $this->assertSame('Jane', $users[1]->name);
    }

    public function testGetUserWithNestedObject(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'id' => 1,
                'name' => 'John Doe',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                    'zipcode' => '10001',
                ],
            ])),
        ]);

        $retrofit = $this->createRetrofit($mock);
        $api = $retrofit->create(TestUserApi::class);

        $user = $api->getUserWithAddress(1);

        $this->assertInstanceOf(UserWithAddress::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('New York', $user->address->city);
    }

    public function testGetUserWithSerializedName(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([
                'id' => 1,
                'user_name' => 'John Doe',
                'email_address' => 'john@example.com',
                'created_at' => '2024-01-01',
            ])),
        ]);

        $retrofit = $this->createRetrofit($mock);
        $api = $retrofit->create(TestUserApi::class);

        $user = $api->getUserWithSerializedName(1);

        $this->assertInstanceOf(UserWithSerializedName::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('2024-01-01', $user->createdAt);
    }

    public function testPostUserWithDtoBody(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(201, [], json_encode([
                'id' => 99,
                'name' => 'New User',
                'email' => 'new@example.com',
            ])),
        ]);

        $retrofit = $this->createRetrofit($mock);
        $api = $retrofit->create(TestUserApi::class);

        $inputUser = new SimpleUser();
        $inputUser->id = 0;
        $inputUser->name = 'New User';
        $inputUser->email = 'new@example.com';

        $createdUser = $api->createUser($inputUser);

        $this->assertInstanceOf(SimpleUser::class, $createdUser);
        $this->assertSame(99, $createdUser->id);
        $this->assertSame('New User', $createdUser->name);
    }

    private function createRetrofit(MockHandler $mock): Retrofit
    {
        return Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client(new GuzzleHttpClient(new Client(['handler' => HandlerStack::create($mock)])))
            ->addConverterFactory(new TypedJsonConverterFactory())
            ->build();
    }
}
