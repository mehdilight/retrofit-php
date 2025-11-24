<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phpmystic\RetrofitPhp\Retrofit;
use Phpmystic\RetrofitPhp\Http\GuzzleHttpClient;
use Phpmystic\RetrofitPhp\Converter\TypedJsonConverterFactory;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\SerializedName;
use Phpmystic\RetrofitPhp\Attributes\ArrayType;

// ============================================
// Define DTO Classes for Paginated API
// ============================================

class Pagination
{
    public int $total;

    #[SerializedName('per_page')]
    public int $perPage;

    #[SerializedName('current_page')]
    public int $currentPage;

    #[SerializedName('last_page')]
    public int $lastPage;

    public ?int $from;
    public ?int $to;

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }
}

class Category
{
    public int $id;
    public ?string $name = null;
    public ?string $slug = null;
    public ?string $description = null;

    #[SerializedName('is_active')]
    public ?bool $isActive = null;
}

class Breed
{
    public int $id;
    public ?string $name = null;
}

class Media
{
    public int $id;

    #[SerializedName('file_name')]
    public ?string $fileName = null;

    public ?string $url = null;

    #[SerializedName('thumbnail_path')]
    public ?string $thumbnailPath = null;

    #[SerializedName('mime_type')]
    public ?string $mimeType = null;

    public ?int $size = null;
    public ?int $order = null;
}

class ListingUser
{
    public int $id;
    public ?string $name = null;
    public ?string $username = null;
    public ?string $email = null;

    #[SerializedName('account_type')]
    public ?string $accountType = null;

    #[SerializedName('is_active')]
    public ?bool $isActive = null;

    public ?string $avatar = null;
    public ?string $bio = null;
}

class Listing
{
    public int $id;
    public ?string $title = null;
    public ?string $slug = null;
    public ?string $description = null;
    public ?string $price = null;
    public ?string $type = null;       // "sale" or "adoption"
    public ?string $gender = null;     // "male", "female", "unknown"

    #[SerializedName('age_months')]
    public ?int $ageMonths = null;

    #[SerializedName('health_info')]
    public ?string $healthInfo = null;

    public ?string $status = null;

    #[SerializedName('is_featured')]
    public ?bool $isFeatured = null;

    #[SerializedName('published_at')]
    public ?string $publishedAt = null;

    #[SerializedName('views_count')]
    public ?int $viewsCount = null;

    // Nested objects
    public ?Category $category = null;
    public ?Breed $breed = null;
    public ?ListingUser $user = null;

    #[ArrayType(Media::class)]
    public array $media = [];

    #[SerializedName('created_at')]
    public ?string $createdAt = null;

    #[SerializedName('updated_at')]
    public ?string $updatedAt = null;
}

class PaginatedListings
{
    public bool $success;
    public string $message;
    public Pagination $pagination;

    #[ArrayType(Listing::class)]
    public array $data = [];
}

// ============================================
// Define API Interface
// ============================================

interface PetAmisApi
{
    #[GET('/api/v1/listings')]
    public function getListings(
        #[Query('page')] int $page = 1,
        #[Query('per_page')] int $perPage = 10,
        #[Query('include')] string $include = 'media,category,breed,user',
    ): PaginatedListings;

    #[GET('/api/v1/listings/{id}')]
    public function getListing(
        #[Path('id')] int $id,
        #[Query('include')] string $include = 'media,category,breed,user',
    ): array;
}

// ============================================
// Create Retrofit Instance
// ============================================

$retrofit = Retrofit::builder()
    ->baseUrl('https://www.petamis.shop')
    ->client(GuzzleHttpClient::create())
    ->addConverterFactory(new TypedJsonConverterFactory())
    ->build();

/** @var PetAmisApi $api */
$api = $retrofit->create(PetAmisApi::class);


echo "=== Pagination Example (petamis.shop) ===\n\n";

// ============================================
// Example 1: Get First Page
// ============================================
echo "1. Get First Page of Listings:\n";
$response = $api->getListings(page: 1, perPage: 3);

echo "   Success: " . ($response->success ? 'true' : 'false') . "\n";
echo "   Message: {$response->message}\n";
echo "   Type: " . get_class($response) . "\n\n";

echo "   Pagination Info:\n";
echo "     - Total: {$response->pagination->total}\n";
echo "     - Per Page: {$response->pagination->perPage}\n";
echo "     - Current Page: {$response->pagination->currentPage}\n";
echo "     - Last Page: {$response->pagination->lastPage}\n";
echo "     - Has More: " . ($response->pagination->hasMorePages() ? 'yes' : 'no') . "\n\n";

echo "   Listings:\n";
foreach ($response->data as $index => $listing) {
    echo "   [{$index}] " . get_class($listing) . "\n";
    echo "       Title: {$listing->title}\n";
    echo "       Type: {$listing->type} | Gender: {$listing->gender}\n";
    echo "       Price: " . ($listing->price ?? 'N/A') . "\n";

    if ($listing->category) {
        echo "       Category: {$listing->category->name} (" . get_class($listing->category) . ")\n";
    }

    if ($listing->breed) {
        echo "       Breed: {$listing->breed->name} (" . get_class($listing->breed) . ")\n";
    }

    if ($listing->user) {
        echo "       Owner: {$listing->user->name} ({$listing->user->accountType})\n";
    }

    if (!empty($listing->media)) {
        echo "       Media: " . count($listing->media) . " files\n";
        $firstMedia = $listing->media[0];
        echo "         First: {$firstMedia->fileName} (" . get_class($firstMedia) . ")\n";
    }

    echo "       Views: {$listing->viewsCount}\n";
    echo "\n";
}

// ============================================
// Example 2: Iterate Through All Pages
// ============================================
echo "2. Paginating Through All Pages:\n";

$page = 1;
$allListings = [];

do {
    $response = $api->getListings(page: $page, perPage: 5);
    $allListings = array_merge($allListings, $response->data);

    echo "   Page {$response->pagination->currentPage}/{$response->pagination->lastPage}";
    echo " - Got " . count($response->data) . " items\n";

    $page++;
} while ($response->pagination->hasMorePages() && $page <= 3); // Limit to 3 pages for demo

echo "\n   Total collected: " . count($allListings) . " listings\n";
echo "   All are typed: " . (empty($allListings) ? 'N/A' : get_class($allListings[0])) . "\n";

echo "\n=== Done ===\n";
