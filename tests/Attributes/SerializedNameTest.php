<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Attributes;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\SerializedName;
use ReflectionClass;

class SerializedNameTest extends TestCase
{
    public function testSerializedNameAttribute(): void
    {
        $attr = new SerializedName('user_name');
        $this->assertSame('user_name', $attr->name);
    }

    public function testSerializedNameOnProperty(): void
    {
        $class = new class {
            #[SerializedName('user_name')]
            public string $name;

            #[SerializedName('email_address')]
            public string $email;

            public int $id;
        };

        $reflection = new ReflectionClass($class);

        // Check name property
        $nameProp = $reflection->getProperty('name');
        $attrs = $nameProp->getAttributes(SerializedName::class);
        $this->assertCount(1, $attrs);
        $this->assertSame('user_name', $attrs[0]->newInstance()->name);

        // Check email property
        $emailProp = $reflection->getProperty('email');
        $attrs = $emailProp->getAttributes(SerializedName::class);
        $this->assertCount(1, $attrs);
        $this->assertSame('email_address', $attrs[0]->newInstance()->name);

        // Check id property (no attribute)
        $idProp = $reflection->getProperty('id');
        $attrs = $idProp->getAttributes(SerializedName::class);
        $this->assertCount(0, $attrs);
    }
}
