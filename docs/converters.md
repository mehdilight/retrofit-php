# Converters

Converters handle serialization and deserialization of request and response bodies. Retrofit PHP provides multiple converter implementations for different use cases.

## JSON Converter

The default converter handles JSON serialization and deserialization.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Retrofit;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->build();
```

### Features

- Automatic JSON encoding/decoding
- Works with arrays and objects
- Handles nested structures
- UTF-8 encoding support

### Example

```php
interface UserApi
{
    #[POST('/users')]
    public function createUser(#[Body] array $user): array;
}

// Request body is automatically converted to JSON
$api->createUser([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Sent as: {"name":"John Doe","email":"john@example.com"}
```

## XML Converter

Built-in XML support for legacy APIs and enterprise systems.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Converter\XmlConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new XmlConverterFactory(
        rootElement: 'Request',
        version: '1.0',
        encoding: 'UTF-8'
    ))
    ->build();
```

### Configuration Options

```php
$xmlConverter = new XmlConverterFactory(
    rootElement: 'Root',    // Root element name (default: 'root')
    version: '1.0',         // XML version (default: '1.0')
    encoding: 'UTF-8'       // Character encoding (default: 'UTF-8')
);
```

### Example

```php
interface OrderApi
{
    #[POST('/orders')]
    #[Headers('Content-Type: application/xml')]
    public function createOrder(#[Body] array $order): array;
}

// Request
$api->createOrder([
    'id' => '12345',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'items' => ['Widget', 'Gadget', 'Tool'],
]);

// Sent as:
// <?xml version="1.0" encoding="UTF-8"?>
// <Request>
//     <id>12345</id>
//     <customer>
//         <name>John Doe</name>
//         <email>john@example.com</email>
//     </customer>
//     <items>
//         <item>Widget</item>
//         <item>Gadget</item>
//         <item>Tool</item>
//     </items>
// </Request>
```

### Features

- Nested structures support
- XML attributes handling
- CDATA support
- Automatic array singularization (`items` → `item`)
- Special character escaping

## Symfony Serializer

Advanced serialization with groups, normalizers, and multiple format support.

### Installation

```bash
composer require symfony/serializer
```

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Converter\SymfonySerializerConverterFactory;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

// Configure Symfony Serializer
$serializer = new Serializer(
    [
        new DateTimeNormalizer(),
        new ObjectNormalizer(),
    ],
    [
        new JsonEncoder(),
    ]
);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new SymfonySerializerConverterFactory(
        $serializer,
        format: 'json'
    ))
    ->build();
```

### With Serialization Groups

```php
use Symfony\Component\Serializer\Annotation\Groups;

class User
{
    #[Groups(['user:read', 'user:write'])]
    public string $name;

    #[Groups(['user:read'])]
    public int $id;

    #[Groups(['user:write'])]
    public string $password;
}

interface UserApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): User;

    #[POST('/users')]
    public function createUser(#[Body] User $user): User;
}

// Configure with groups
$converterFactory = new SymfonySerializerConverterFactory(
    $serializer,
    format: 'json',
    context: ['groups' => ['user:read']]
);
```

### XML Format

```php
use Symfony\Component\Serializer\Encoder\XmlEncoder;

$serializer = new Serializer(
    [new ObjectNormalizer()],
    [new XmlEncoder()]
);

$converterFactory = new SymfonySerializerConverterFactory(
    $serializer,
    format: 'xml'
);
```

### Advanced Configuration

```php
$converterFactory = new SymfonySerializerConverterFactory(
    $serializer,
    format: 'json',
    context: [
        'groups' => ['user:read'],
        'datetime_format' => 'Y-m-d H:i:s',
        'json_encode_options' => JSON_PRETTY_PRINT,
        'skip_null_values' => true,
        'circular_reference_handler' => function ($object) {
            return $object->getId();
        }
    ]
);
```

### Custom Normalizers

```php
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = []): array
    {
        return [
            'amount' => $object->getAmount(),
            'currency' => $object->getCurrency()
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Money;
    }
}

$serializer = new Serializer([
    new MoneyNormalizer(),
    new DateTimeNormalizer(),
    new ObjectNormalizer(),
]);
```

### Features

- Serialization groups for field filtering
- Custom normalizers and denormalizers
- Multiple format support (JSON, XML, YAML, CSV)
- DateTime, UUID, and other built-in normalizers
- Max depth protection
- Circular reference handling
- Integration with Symfony validation

### Supported Formats

- **JSON** (default)
- **XML**
- **YAML** (requires `symfony/yaml`)
- **CSV** (requires `symfony/serializer`)

## Custom Converter

Create custom converters for specialized serialization needs.

### Implementing ConverterFactory

```php
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;

class CustomConverterFactory implements ConverterFactory
{
    public function requestBodyConverter(?ReflectionType $type): ?Converter
    {
        return new CustomRequestConverter();
    }

    public function responseBodyConverter(?ReflectionType $type): ?Converter
    {
        return new CustomResponseConverter();
    }

    public function stringConverter(?ReflectionType $type): ?Converter
    {
        return new StringConverter();
    }
}
```

### Implementing Converter

```php
use Phpmystic\RetrofitPhp\Contracts\Converter;

class CustomRequestConverter implements Converter
{
    public function convert($value): string
    {
        // Convert PHP value to request body string
        return serialize($value);
    }
}

class CustomResponseConverter implements Converter
{
    public function convert($value): mixed
    {
        // Convert response body string to PHP value
        return unserialize($value);
    }
}
```

### Example: MessagePack Converter

```php
class MessagePackConverterFactory implements ConverterFactory
{
    public function requestBodyConverter(?ReflectionType $type): ?Converter
    {
        return new class implements Converter {
            public function convert($value): string
            {
                return msgpack_pack($value);
            }
        };
    }

    public function responseBodyConverter(?ReflectionType $type): ?Converter
    {
        return new class implements Converter {
            public function convert($value): mixed
            {
                return msgpack_unpack($value);
            }
        };
    }

    public function stringConverter(?ReflectionType $type): ?Converter
    {
        return new class implements Converter {
            public function convert($value): string
            {
                return (string) $value;
            }
        };
    }
}

// Usage
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new MessagePackConverterFactory())
    ->build();
```

## Multiple Converters

Chain multiple converters to handle different content types.

```php
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\Converter\XmlConverterFactory;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new JsonConverterFactory())
    ->addConverterFactory(new XmlConverterFactory())
    ->build();

// Converters are tried in order until one succeeds
```

## Choosing a Converter

### Use JSON Converter When:
- Working with modern REST APIs
- You need simple, fast serialization
- Your API uses JSON format
- You're building a new application

### Use XML Converter When:
- Working with legacy systems
- Your API requires XML format
- Enterprise integrations (SOAP-like APIs)
- Industry-specific standards require XML

### Use Symfony Serializer When:
- You need advanced features (groups, callbacks)
- Working with complex object graphs
- You need multiple format support
- Integration with Symfony validation
- Custom normalization logic is required

### Use Custom Converter When:
- Working with proprietary formats
- Specialized serialization requirements
- Performance optimization for specific use cases
- Integration with existing serialization systems

## See Also

- [Request Body](request-body.md) - Learn about sending different types of data
- [Client Configuration](client-configuration.md) - Configure HTTP client
- [Examples](examples.md) - See complete examples with converters
