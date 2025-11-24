<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Hydration;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Converter\ObjectHydrator;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\Address;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\Company;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\Post;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\SimpleUser;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\UserWithAddress;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\UserWithNullableAddress;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\UserWithPosts;
use Phpmystic\RetrofitPhp\Tests\Hydration\Fixtures\UserWithSerializedName;

class ObjectHydratorTest extends TestCase
{
    private ObjectHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new ObjectHydrator();
    }

    public function testHydrateSimpleObject(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->hydrator->hydrate($data, SimpleUser::class);

        $this->assertInstanceOf(SimpleUser::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    public function testHydrateWithSerializedName(): void
    {
        $data = [
            'id' => 1,
            'user_name' => 'John Doe',
            'email_address' => 'john@example.com',
            'created_at' => '2024-01-01',
        ];

        $user = $this->hydrator->hydrate($data, UserWithSerializedName::class);

        $this->assertInstanceOf(UserWithSerializedName::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('2024-01-01', $user->createdAt);
    }

    public function testHydrateWithNestedObject(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'zipcode' => '10001',
            ],
        ];

        $user = $this->hydrator->hydrate($data, UserWithAddress::class);

        $this->assertInstanceOf(UserWithAddress::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertInstanceOf(Address::class, $user->address);
        $this->assertSame('123 Main St', $user->address->street);
        $this->assertSame('New York', $user->address->city);
        $this->assertSame('10001', $user->address->zipcode);
    }

    public function testHydrateWithNullableNestedObject(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'address' => null,
        ];

        $user = $this->hydrator->hydrate($data, UserWithNullableAddress::class);

        $this->assertInstanceOf(UserWithNullableAddress::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertNull($user->address);
    }

    public function testHydrateWithNullableNestedObjectPresent(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'zipcode' => '10001',
            ],
        ];

        $user = $this->hydrator->hydrate($data, UserWithNullableAddress::class);

        $this->assertInstanceOf(UserWithNullableAddress::class, $user);
        $this->assertInstanceOf(Address::class, $user->address);
        $this->assertSame('New York', $user->address->city);
    }

    public function testHydrateWithArrayOfObjects(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'posts' => [
                ['id' => 1, 'title' => 'Post 1', 'body' => 'Body 1', 'user_id' => 1],
                ['id' => 2, 'title' => 'Post 2', 'body' => 'Body 2', 'user_id' => 1],
            ],
        ];

        $user = $this->hydrator->hydrate($data, UserWithPosts::class);

        $this->assertInstanceOf(UserWithPosts::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertCount(2, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts[0]);
        $this->assertInstanceOf(Post::class, $user->posts[1]);
        $this->assertSame('Post 1', $user->posts[0]->title);
        $this->assertSame('Post 2', $user->posts[1]->title);
        $this->assertSame(1, $user->posts[0]->userId);
    }

    public function testHydrateComplexObject(): void
    {
        $data = [
            'id' => 1,
            'company_name' => 'Acme Corp',
            'headquarters' => [
                'street' => '456 Corp Ave',
                'city' => 'San Francisco',
                'zipcode' => '94105',
            ],
            'employees' => [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@acme.com'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@acme.com'],
            ],
        ];

        $company = $this->hydrator->hydrate($data, Company::class);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertSame(1, $company->id);
        $this->assertSame('Acme Corp', $company->name);
        $this->assertInstanceOf(Address::class, $company->headquarters);
        $this->assertSame('San Francisco', $company->headquarters->city);
        $this->assertCount(2, $company->employees);
        $this->assertInstanceOf(SimpleUser::class, $company->employees[0]);
        $this->assertSame('Alice', $company->employees[0]->name);
    }

    public function testHydrateMissingOptionalProperty(): void
    {
        $data = [
            'id' => 1,
            'user_name' => 'John Doe',
            'email_address' => 'john@example.com',
            // created_at is missing
        ];

        $user = $this->hydrator->hydrate($data, UserWithSerializedName::class);

        $this->assertInstanceOf(UserWithSerializedName::class, $user);
        $this->assertNull($user->createdAt);
    }

    public function testHydrateEmptyArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'posts' => [],
        ];

        $user = $this->hydrator->hydrate($data, UserWithPosts::class);

        $this->assertInstanceOf(UserWithPosts::class, $user);
        $this->assertSame([], $user->posts);
    }

    public function testHydrateArray(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ];

        $users = $this->hydrator->hydrateArray($data, SimpleUser::class);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(SimpleUser::class, $users[0]);
        $this->assertInstanceOf(SimpleUser::class, $users[1]);
        $this->assertSame('John', $users[0]->name);
        $this->assertSame('Jane', $users[1]->name);
    }

    public function testHydrateReturnsNullForNullData(): void
    {
        $result = $this->hydrator->hydrate(null, SimpleUser::class);
        $this->assertNull($result);
    }

    public function testHydrateArrayReturnsEmptyForNullData(): void
    {
        $result = $this->hydrator->hydrateArray(null, SimpleUser::class);
        $this->assertSame([], $result);
    }
}
