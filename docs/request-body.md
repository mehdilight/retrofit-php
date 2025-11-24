# Request Body

Retrofit PHP supports multiple ways to send data in request bodies.

## JSON Body

The default format for sending data is JSON. Use the `#[Body]` attribute to send data as JSON.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;

interface UserApi
{
    #[POST('/users')]
    public function createUser(#[Body] array $user): array;
}

// Usage
$api = $retrofit->create(UserApi::class);

$user = $api->createUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Sends:
// Content-Type: application/json
// {"name":"John Doe","email":"john@example.com","age":30}
```

### With Objects

```php
class User
{
    public function __construct(
        public string $name,
        public string $email,
        public int $age
    ) {}
}

interface UserApi
{
    #[POST('/users')]
    public function createUser(#[Body] User $user): array;
}

// Usage
$user = new User('John Doe', 'john@example.com', 30);
$result = $api->createUser($user);
```

## Form Encoded

Send data as `application/x-www-form-urlencoded` using the `#[FormUrlEncoded]` attribute.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\FormUrlEncoded;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Field;

interface AuthApi
{
    #[POST('/login')]
    #[FormUrlEncoded]
    public function login(
        #[Field('username')] string $username,
        #[Field('password')] string $password,
        #[Field('remember')] bool $remember = false
    ): array;
}

// Usage
$api = $retrofit->create(AuthApi::class);

$result = $api->login('john', 'secret123', true);

// Sends:
// Content-Type: application/x-www-form-urlencoded
// username=john&password=secret123&remember=1
```

### Optional Fields

```php
#[POST('/register')]
#[FormUrlEncoded]
public function register(
    #[Field('email')] string $email,
    #[Field('password')] string $password,
    #[Field('newsletter')] ?bool $newsletter = null
): array;

// If $newsletter is null, it won't be included in the form data
$api->register('john@example.com', 'secret123');
// Sends: email=john@example.com&password=secret123

$api->register('john@example.com', 'secret123', true);
// Sends: email=john@example.com&password=secret123&newsletter=1
```

## Field Map

Send multiple form fields as an associative array.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Parameter\FieldMap;

interface AuthApi
{
    #[POST('/login')]
    #[FormUrlEncoded]
    public function login(#[FieldMap] array $credentials): array;
}

// Usage
$api = $retrofit->create(AuthApi::class);

$result = $api->login([
    'username' => 'john',
    'password' => 'secret123',
    'remember' => true,
    'device' => 'mobile'
]);

// Sends:
// Content-Type: application/x-www-form-urlencoded
// username=john&password=secret123&remember=1&device=mobile
```

### Combining Field and FieldMap

```php
#[POST('/login')]
#[FormUrlEncoded]
public function login(
    #[Field('username')] string $username,
    #[Field('password')] string $password,
    #[FieldMap] array $options = []
): array;

// Usage
$result = $api->login('john', 'secret123', [
    'remember' => true,
    'device' => 'mobile'
]);

// Sends: username=john&password=secret123&remember=1&device=mobile
```

## Multipart Form Data

For file uploads and mixed content, see the [File Handling](file-handling.md) documentation.

### Basic Example

```php
use Phpmystic\RetrofitPhp\Attributes\Multipart;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;

interface FileApi
{
    #[POST('/upload')]
    #[Multipart]
    public function uploadDocument(
        #[Part('file')] FileUpload $file,
        #[Part('title')] string $title,
        #[Part('description')] string $description
    ): array;
}
```

## Complete Example

```php
use Phpmystic\RetrofitPhp\Attributes\Http\{GET, POST, PUT};
use Phpmystic\RetrofitPhp\Attributes\Parameter\{Path, Body, Field, FieldMap};
use Phpmystic\RetrofitPhp\Attributes\FormUrlEncoded;

interface BlogApi
{
    // JSON body
    #[POST('/posts')]
    public function createPost(#[Body] array $post): array;

    // JSON body with update
    #[PUT('/posts/{id}')]
    public function updatePost(
        #[Path('id')] int $id,
        #[Body] array $post
    ): array;

    // Form encoded
    #[POST('/posts/{id}/like')]
    #[FormUrlEncoded]
    public function likePost(
        #[Path('id')] int $id,
        #[Field('user_id')] int $userId
    ): array;

    // Form with field map
    #[POST('/posts/{id}/comment')]
    #[FormUrlEncoded]
    public function addComment(
        #[Path('id')] int $id,
        #[FieldMap] array $comment
    ): array;
}

// Usage examples
$api = $retrofit->create(BlogApi::class);

// Create post with JSON
$post = $api->createPost([
    'title' => 'My First Post',
    'content' => 'This is the content...',
    'tags' => ['php', 'retrofit']
]);

// Update post with JSON
$updated = $api->updatePost(1, [
    'title' => 'Updated Title',
    'content' => 'Updated content...'
]);

// Like post with form data
$api->likePost(1, userId: 42);

// Add comment with field map
$api->addComment(1, [
    'author' => 'John Doe',
    'text' => 'Great post!',
    'rating' => 5
]);
```

## Key Points

### JSON Body (`#[Body]`)
- Default content type is `application/json`
- Works with arrays and objects
- Automatically serialized by the converter
- Best for structured data and APIs

### Form Encoded (`#[FormUrlEncoded]` + `#[Field]`)
- Content type is `application/x-www-form-urlencoded`
- Traditional HTML form submission format
- Use for simple key-value pairs
- Required for some legacy APIs

### Field Map (`#[FieldMap]`)
- Useful for dynamic form fields
- Can be combined with `#[Field]` parameters
- All values are automatically encoded
- Null values are omitted

### Multipart (`#[Multipart]`)
- Content type is `multipart/form-data`
- Required for file uploads
- Supports mixed content (files + fields)
- See [File Handling](file-handling.md) for details

## See Also

- [HTTP Methods](http-methods.md) - Learn about different HTTP methods
- [URL Parameters](parameters.md) - Learn about path and query parameters
- [File Handling](file-handling.md) - Learn about uploading files
- [Converters](converters.md) - Learn about custom data serialization
