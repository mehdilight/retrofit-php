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

## Typed JSON Converter (DTO Hydration)

The TypedJsonConverterFactory provides automatic object hydration, converting JSON responses into strongly-typed PHP objects (DTOs).

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Retrofit;

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new TypedJsonConverterFactory())
    ->build();
```

### Defining DTO Classes

```php
class User
{
    public int $id;
    public string $name;
    public string $email;
}

interface UserApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): User;
}

// Returns a fully hydrated User object
$user = $api->getUser(1);
echo $user->name; // Type-safe access with IDE autocomplete
```

### Features

#### 1. Nested Objects

Automatically hydrates nested object structures.

```php
class Address
{
    public string $street;
    public string $city;
    public string $zipcode;
}

class Company
{
    public string $name;
    public string $catchPhrase;
}

class User
{
    public int $id;
    public string $name;
    public Address $address;    // Nested object
    public Company $company;     // Nested object
}

// JSON response:
// {
//   "id": 1,
//   "name": "John",
//   "address": {"street": "Main St", "city": "NYC", "zipcode": "10001"},
//   "company": {"name": "Acme Inc", "catchPhrase": "Innovation"}
// }

$user = $api->getUser(1);
echo $user->address->city;      // "NYC" - fully typed nested access
echo $user->company->name;       // "Acme Inc"
```

#### 2. Field Name Mapping with #[SerializedName]

Map JSON field names to different property names using the `#[SerializedName]` attribute.

```php
use Phpmystic\RetrofitPhp\Attributes\SerializedName;

class Post
{
    public int $id;

    #[SerializedName('userId')]
    public int $authorId;  // Maps from "userId" in JSON to "authorId" property

    public string $title;
    public string $body;
}

// JSON response: {"id": 1, "userId": 42, "title": "Hello", "body": "..."}
$post = $api->getPost(1);
echo $post->authorId;  // 42 (from JSON's "userId")
```

#### 3. Arrays of Objects with #[ArrayType]

Use the `#[ArrayType]` attribute on properties to define arrays of typed objects.

```php
use Phpmystic\RetrofitPhp\Attributes\ArrayType;

class Post
{
    public int $id;
    public string $title;
}

class UserWithPosts
{
    public int $id;
    public string $name;

    #[ArrayType(Post::class)]
    public array $posts = [];  // Array of Post objects
}

// JSON response:
// {
//   "id": 1,
//   "name": "John",
//   "posts": [
//     {"id": 1, "title": "First Post"},
//     {"id": 2, "title": "Second Post"}
//   ]
// }

$user = $api->getUserWithPosts(1);
foreach ($user->posts as $post) {
    echo $post->title;  // Each $post is a Post object
}
```

#### 4. Array Responses with #[ResponseType]

For methods that return arrays of objects, use the `#[ResponseType]` attribute at the method level.

```php
use Phpmystic\RetrofitPhp\Attributes\ResponseType;

interface PostApi
{
    // Without ResponseType - returns array of arrays
    #[GET('/posts')]
    public function getPosts(): array;

    // With ResponseType - returns array of Post objects
    #[GET('/posts')]
    #[ResponseType(Post::class, isArray: true)]
    public function getPostsTyped(): array;
}

// Without ResponseType
$posts = $api->getPosts();
echo $posts[0]['title'];  // Array access

// With ResponseType
$posts = $api->getPostsTyped();
echo $posts[0]->title;    // Object access - each item is a Post
```

### Complete Example

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use Phpmystic\RetrofitPhp\Attributes\SerializedName;
use Phpmystic\RetrofitPhp\Attributes\ArrayType;

// Define DTOs
class Address
{
    public string $street;
    public string $city;
    public string $zipcode;
}

class Post
{
    #[SerializedName('userId')]
    public int $authorId;

    public int $id;
    public string $title;
    public string $body;
}

class User
{
    public int $id;
    public string $name;
    public string $email;
    public Address $address;  // Nested object

    #[ArrayType(Post::class)]
    public array $posts = [];  // Array of Post objects
}

// Define API
interface BlogApi
{
    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): User;

    #[GET('/posts')]
    #[ResponseType(Post::class, isArray: true)]
    public function getAllPosts(): array;
}

// Setup
$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->addConverterFactory(new TypedJsonConverterFactory())
    ->build();

$api = $retrofit->create(BlogApi::class);

// Usage - fully typed responses
$user = $api->getUser(1);
echo $user->name;                    // Type-safe
echo $user->address->city;           // Nested object access
foreach ($user->posts as $post) {    // Typed array iteration
    echo $post->title;
}

$posts = $api->getAllPosts();        // Array of Post objects
echo $posts[0]->authorId;            // Mapped from "userId"
```

### Benefits

- **Type Safety**: IDE autocomplete and static analysis support
- **Clean Code**: Work with objects instead of arrays
- **Refactoring**: Easy to rename properties and find usages
- **Documentation**: DTOs serve as self-documenting API contracts
- **Validation**: Leverage PHP type hints for basic validation

### When to Use Typed JSON Converter

- You want type-safe API responses
- Your API has complex nested structures
- You value IDE autocomplete and refactoring support
- You prefer object-oriented code over array manipulation
- You want clear documentation of API response structures

### Comparison with JSON Converter

| Feature | JsonConverterFactory | TypedJsonConverterFactory |
|---------|---------------------|---------------------------|
| Return Type | `array` | Custom DTO classes |
| Nested Data | `$data['address']['city']` | `$user->address->city` |
| IDE Support | Limited | Full autocomplete |
| Array Responses | `array<array>` | `array<Post>` with `#[ResponseType]` |
| Field Mapping | Manual | `#[SerializedName]` attribute |

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
- You need simple, fast serialization without type safety
- Your API uses JSON format and you prefer working with arrays
- You're building simple integrations or scripts

### Use Typed JSON Converter When:
- You want type-safe API responses with IDE autocomplete
- Your API has complex nested structures
- You value refactoring support and static analysis
- You prefer object-oriented code over array manipulation
- You want self-documenting API contracts through DTOs

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
