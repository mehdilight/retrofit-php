# HTTP Methods

Retrofit PHP supports all standard HTTP methods using dedicated attributes.

## Supported Methods

### GET

Used to retrieve resources from the server.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;

#[GET('/users')]
public function getUsers(): array;

#[GET('/users/{id}')]
public function getUser(#[Path('id')] int $id): array;
```

### POST

Used to create new resources on the server.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;

#[POST('/users')]
public function createUser(#[Body] array $data): array;
```

### PUT

Used to update or replace existing resources.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\PUT;

#[PUT('/users/{id}')]
public function updateUser(#[Path('id')] int $id, #[Body] array $data): array;
```

### DELETE

Used to delete resources from the server.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\DELETE;

#[DELETE('/users/{id}')]
public function deleteUser(#[Path('id')] int $id): array;
```

### PATCH

Used to partially update existing resources.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\PATCH;

#[PATCH('/users/{id}')]
public function patchUser(#[Path('id')] int $id, #[Body] array $data): array;
```

### HEAD

Used to retrieve headers without the response body.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\HEAD;

#[HEAD('/users/{id}')]
public function headUser(#[Path('id')] int $id): array;
```

### OPTIONS

Used to describe communication options for the target resource.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\OPTIONS;

#[OPTIONS('/users')]
public function optionsUsers(): array;
```

## Complete Example

```php
use Phpmystic\RetrofitPhp\Attributes\Http\{GET, POST, PUT, DELETE, PATCH};
use Phpmystic\RetrofitPhp\Attributes\Parameter\{Path, Body};

interface UserApi
{
    #[GET('/users')]
    public function listUsers(): array;

    #[GET('/users/{id}')]
    public function getUser(#[Path('id')] int $id): array;

    #[POST('/users')]
    public function createUser(#[Body] array $user): array;

    #[PUT('/users/{id}')]
    public function updateUser(#[Path('id')] int $id, #[Body] array $user): array;

    #[PATCH('/users/{id}')]
    public function patchUser(#[Path('id')] int $id, #[Body] array $updates): array;

    #[DELETE('/users/{id}')]
    public function deleteUser(#[Path('id')] int $id): void;
}
```

## See Also

- [URL Parameters](parameters.md) - Learn about path and query parameters
- [Request Body](request-body.md) - Learn about sending data in request bodies
- [Headers](headers.md) - Learn about adding headers to requests
