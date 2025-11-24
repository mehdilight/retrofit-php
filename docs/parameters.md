# URL Parameters

Retrofit PHP provides several ways to pass parameters in URLs.

## Path Parameters

Path parameters replace segments in the URL path dynamically.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;

interface GitHubApi
{
    #[GET('/users/{user}')]
    public function getUser(#[Path('user')] string $username): array;

    #[GET('/users/{user}/repos/{repo}')]
    public function getRepo(
        #[Path('user')] string $user,
        #[Path('repo')] string $repo
    ): array;
}

// Usage
$api = $retrofit->create(GitHubApi::class);

// Calls: GET /users/octocat
$user = $api->getUser('octocat');

// Calls: GET /users/octocat/repos/Hello-World
$repo = $api->getRepo('octocat', 'Hello-World');
```

### Key Points

- Path parameters are defined using curly braces `{parameter}` in the URL
- The `#[Path]` attribute maps function parameters to URL segments
- Path parameters are automatically URL-encoded
- Path parameters are required (cannot be null)

## Query Parameters

Query parameters are appended to the URL as query strings.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;

interface SearchApi
{
    #[GET('/users')]
    public function searchUsers(
        #[Query('q')] string $query,
        #[Query('page')] int $page = 1,
        #[Query('per_page')] int $perPage = 10
    ): array;
}

// Usage
$api = $retrofit->create(SearchApi::class);

// Calls: GET /users?q=john&page=2&per_page=20
$users = $api->searchUsers('john', page: 2, perPage: 20);

// Calls: GET /users?q=jane (uses default values for page and per_page)
$users = $api->searchUsers('jane');
```

### Optional Query Parameters

```php
#[GET('/search')]
public function search(
    #[Query('q')] string $query,
    #[Query('filter')] ?string $filter = null
): array;

// If $filter is null, it won't be included in the query string
$results = $api->search('test');         // GET /search?q=test
$results = $api->search('test', 'new');  // GET /search?q=test&filter=new
```

### Key Points

- Query parameters are appended as `?key=value&key2=value2`
- Support default values
- Nullable parameters are omitted if null
- Values are automatically URL-encoded

## Query Map

Pass multiple query parameters as an associative array.

### Basic Usage

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\QueryMap;

interface SearchApi
{
    #[GET('/search')]
    public function search(#[QueryMap] array $params): array;
}

// Usage
$api = $retrofit->create(SearchApi::class);

// Calls: GET /search?q=test&sort=date&order=desc&limit=50
$results = $api->search([
    'q' => 'test',
    'sort' => 'date',
    'order' => 'desc',
    'limit' => 50
]);
```

### Combining Query Parameters and Query Map

```php
#[GET('/search')]
public function search(
    #[Query('q')] string $query,
    #[QueryMap] array $filters = []
): array;

// Calls: GET /search?q=test&category=books&price=10
$results = $api->search('test', [
    'category' => 'books',
    'price' => 10
]);
```

### Key Points

- Useful for dynamic or optional query parameters
- Can be combined with regular `#[Query]` parameters
- All array values are automatically URL-encoded
- Null values in the map are omitted

## Complete Example

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\{Path, Query, QueryMap};

interface ProductApi
{
    // Path parameters only
    #[GET('/products/{id}')]
    public function getProduct(#[Path('id')] int $id): array;

    // Path and query parameters
    #[GET('/categories/{category}/products')]
    public function getCategoryProducts(
        #[Path('category')] string $category,
        #[Query('page')] int $page = 1,
        #[Query('sort')] string $sort = 'name'
    ): array;

    // Multiple path parameters with query map
    #[GET('/stores/{storeId}/products/{productId}')]
    public function getStoreProduct(
        #[Path('storeId')] int $storeId,
        #[Path('productId')] int $productId,
        #[QueryMap] array $options = []
    ): array;
}

// Usage examples
$api = $retrofit->create(ProductApi::class);

// GET /products/123
$product = $api->getProduct(123);

// GET /categories/electronics/products?page=2&sort=price
$products = $api->getCategoryProducts('electronics', page: 2, sort: 'price');

// GET /stores/5/products/123?include=reviews&include=ratings
$storeProduct = $api->getStoreProduct(5, 123, [
    'include' => ['reviews', 'ratings']
]);
```

## See Also

- [HTTP Methods](http-methods.md) - Learn about different HTTP methods
- [Request Body](request-body.md) - Learn about sending data in request bodies
- [Headers](headers.md) - Learn about adding headers to requests
