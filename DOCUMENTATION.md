# Laravel Temp Media - Documentation

## Introduction

Laravel Temp Media is a package designed to solve the common problem of handling file uploads in multi-step processes. It provides a robust solution for temporary file storage and seamless transfer to models using Spatie's Laravel MediaLibrary.

This documentation provides a comprehensive guide to using the Laravel Temp Media package, including installation, configuration, usage examples, and API reference.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Core Concepts](#core-concepts)
- [Usage Examples](#usage-examples)
  - [Basic Usage](#basic-usage)
  - [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
  - [Services](#services)
  - [DTOs](#dtos)
  - [Traits](#traits)
  - [Events](#events)
- [Commands](#commands)
- [Troubleshooting](#troubleshooting)

## Installation

### Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher
- Spatie Laravel MediaLibrary 10.0 or higher

### Composer Installation

You can install the package via composer:

```bash
composer require add/laravel-temp-media
```

### Publishing Assets

After installing the package, you should publish and run the migrations:

```bash
php artisan vendor:publish --tag="temp-media-migrations"
php artisan migrate
```

Optionally, you can publish the configuration file:

```bash
php artisan vendor:publish --tag="temp-media-config"
```

## Configuration

The package comes with a comprehensive configuration file that allows you to customize its behavior. Here's a detailed explanation of each configuration option:

### Time to Live (TTL)

```php
'default_ttl_hours' => env('TEMP_MEDIA_TTL_HOURS', 24),
```

This setting determines how long temporary media files should be kept before being eligible for cleanup. Files older than this will be automatically removed during the cleanup process.

### File Validation

```php
'max_file_size' => env('TEMP_MEDIA_MAX_SIZE', 10 * 1024 * 1024),

'allowed_mime_types' => [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
],
```

These settings control the validation rules for uploaded files:
- `max_file_size`: Maximum file size in bytes (default: 10MB)
- `allowed_mime_types`: Array of allowed MIME types. If empty, all types are allowed.

### Storage

```php
'disk' => env('TEMP_MEDIA_DISK', 'public'),
```

The disk where temporary media files should be stored. This should match one of your configured filesystems in `config/filesystems.php`.

### Cleanup

```php
'enable_auto_cleanup' => env('TEMP_MEDIA_AUTO_CLEANUP', true),

'cleanup_schedule' => [
    'frequency' => env('TEMP_MEDIA_CLEANUP_FREQUENCY', 'hourly'),
    'without_overlapping' => env('TEMP_MEDIA_CLEANUP_NO_OVERLAP', true),
    'run_in_background' => env('TEMP_MEDIA_CLEANUP_BACKGROUND', true),
    'timeout' => env('TEMP_MEDIA_CLEANUP_TIMEOUT', 300), // 5 minutes
],
```

These settings control the automatic cleanup of expired temporary media:
- `enable_auto_cleanup`: Whether to automatically register the cleanup command in the scheduler
- `cleanup_schedule`: Configuration for when and how the cleanup command should run

### Routes

```php
'auto_discovery' => env('TEMP_MEDIA_AUTO_DISCOVERY', true),

'routes' => [
    'prefix' => 'api/v1/temp-media',
    'middleware' => ['api'],
    'name_prefix' => 'temp-media.',
],
```

These settings control the API routes:
- `auto_discovery`: Whether to automatically register routes and other components
- `routes`: Configuration for the package's API routes

### Security

```php
'validate_session' => env('TEMP_MEDIA_VALIDATE_SESSION', true),

'rate_limiting' => [
    'enabled' => env('TEMP_MEDIA_RATE_LIMIT', true),
    'max_attempts' => env('TEMP_MEDIA_RATE_LIMIT_ATTEMPTS', 60),
    'decay_minutes' => env('TEMP_MEDIA_RATE_LIMIT_DECAY', 1),
],
```

These settings control security features:
- `validate_session`: Whether to validate that temporary media belongs to the current session
- `rate_limiting`: Rate limiting configuration for temporary media uploads

### Media Library Integration

```php
'generate_conversions' => env('TEMP_MEDIA_GENERATE_CONVERSIONS', false),
'collection_name' => 'temp_files',
```

These settings control how the package integrates with Spatie's MediaLibrary:
- `generate_conversions`: Whether to generate thumbnails and other conversions for temporary media
- `collection_name`: The default media collection name for temporary media files

### Events

```php
'dispatch_events' => env('TEMP_MEDIA_DISPATCH_EVENTS', true),
```

This setting controls whether to dispatch events for temporary media operations.

### Queue

```php
'queue' => [
    'connection' => env('TEMP_MEDIA_QUEUE_CONNECTION', 'default'),
    'queue' => env('TEMP_MEDIA_QUEUE', 'default'),
],
```

These settings control the queue configuration for background jobs like cleanup and media processing.

## Core Concepts

### Temporary Media Lifecycle

1. **Upload**: Files are uploaded and stored as temporary media with a TTL (Time To Live)
2. **Storage**: Files are stored using Spatie's MediaLibrary with a unique identifier
3. **Transfer**: When needed, files are transferred from temporary storage to a model
4. **Cleanup**: Expired or processed files are automatically cleaned up

### Data Transfer Objects (DTOs)

The package uses DTOs to ensure type safety and provide a clean API:

- `TempMediaUploadDTO`: Contains information about an uploaded temporary media file
- `TempMediaTransferDTO`: Contains information about temporary media files to be transferred
- `TempMediaItemDTO`: Represents a single temporary media item with optional ordering
- `MediaTransferDTO`: Contains the result of a transfer operation

### Services

The package provides two main services:

- `TempMediaService`: Handles uploading, validating, and managing temporary media
- `MediaTransferService`: Handles transferring temporary media to models

### Traits

The package provides a trait that can be used by models:

- `HandlesTempMedia`: Provides methods for transferring temporary media to models

## Usage Examples

### Basic Usage

#### Uploading Temporary Files

```php
use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use Illuminate\Http\Request;

public function upload(Request $request, TempMediaServiceInterface $tempMediaService)
{
    $file = $request->file('file');
    $sessionId = $request->session()->getId();
    
    $result = $tempMediaService->uploadTempMedia($file, $sessionId);
    
    return response()->json([
        'id' => $result->id,
        'url' => $result->url,
        'original_name' => $result->originalName,
        'expires_at' => $result->expiresAt,
    ]);
}
```

#### Preparing Your Model

```php
use BardanIO\TempMedia\Traits\HandlesTempMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HandlesTempMedia;
    
    // Define your media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_images')
            ->useDisk('public');
    }
}
```

#### Transferring Temporary Files to a Model

```php
use BardanIO\TempMedia\DTOs\TempMediaTransferDTO;

public function store(Request $request)
{
    $product = Product::create($request->validated());
    
    // Get temporary media IDs from the request
    $tempMediaItems = $request->input('images', []);
    
    // Create a DTO from the array
    $transferDto = TempMediaTransferDTO::fromArray($tempMediaItems);
    
    // Transfer the temporary media to the product
    $result = $product->transferTempMedia($transferDto, 'product_images');
    
    return response()->json([
        'product' => $product,
        'media' => $result->transferredMedia,
    ]);
}
```

### Advanced Usage

#### Handling Media with Order

```php
$tempMediaItems = [
    [
        'id' => 'temp-media-id-1',
        'order_column' => 1
    ],
    [
        'id' => 'temp-media-id-2',
        'order_column' => 2
    ]
];

$transferDto = TempMediaTransferDTO::fromArray($tempMediaItems);
$result = $product->transferTempMedia($transferDto, 'product_images');
```

#### Validating Ownership

```php
use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;

public function validateOwnership(
    Request $request, 
    MediaTransferServiceInterface $transferService
)
{
    $tempMediaIds = $request->input('media_ids', []);
    $sessionId = $request->session()->getId();
    $userId = auth()->id();
    
    $isValid = $transferService->validateOwnership($tempMediaIds, $sessionId, $userId);
    
    if (!$isValid) {
        return response()->json(['error' => 'Unauthorized media access'], 403);
    }
    
    // Continue with the operation
}
```

#### Handling Transfer Results

```php
$transferDto = TempMediaTransferDTO::fromArray($tempMediaItems);
$result = $product->transferTempMedia($transferDto, 'product_images');

if ($result->hasFailures()) {
    // Handle failures
    foreach ($result->failedTransfers as $failure) {
        Log::warning('Failed to transfer media', $failure);
    }
}

// Get URLs of transferred media
$mediaUrls = $product->getMediaUrlsFromTransfer($result);
```

#### Complete Controller Example

```php
use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use BardanIO\TempMedia\DTOs\TempMediaTransferDTO;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function upload(Request $request, TempMediaServiceInterface $tempMediaService)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $sessionId = $request->session()->getId();
        
        $result = $tempMediaService->uploadTempMedia($file, $sessionId);
        
        return response()->json([
            'id' => $result->id,
            'url' => $result->url,
            'original_name' => $result->originalName,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'images' => 'sometimes|array',
            'images.*.id' => 'required|string',
            'images.*.order_column' => 'sometimes|integer|min:0',
        ]);
        
        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
        ]);
        
        if (isset($validated['images']) && !empty($validated['images'])) {
            $transferDto = TempMediaTransferDTO::fromArray($validated['images']);
            $result = $product->transferTempMedia($transferDto, 'product_images');
            
            if ($result->hasFailures()) {
                // Log failures
                foreach ($result->failedTransfers as $failure) {
                    Log::warning('Failed to transfer media', $failure);
                }
            }
        }
        
        return response()->json([
            'product' => $product,
            'media' => $product->getMedia('product_images')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'original_name' => $media->name,
                    'order' => $media->order_column,
                ];
            }),
        ]);
    }
    
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'images' => 'sometimes|array',
            'images.*.id' => 'required|string',
            'images.*.order_column' => 'sometimes|integer|min:0',
        ]);
        
        $product->update($validated);
        
        if (isset($validated['images'])) {
            // Clear existing images if needed
            // $product->clearMediaCollection('product_images');
            
            $transferDto = TempMediaTransferDTO::fromArray($validated['images']);
            $product->transferTempMedia($transferDto, 'product_images');
        }
        
        return response()->json([
            'product' => $product,
            'media' => $product->getMedia('product_images')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'original_name' => $media->name,
                    'order' => $media->order_column,
                ];
            }),
        ]);
    }
}
```

## API Reference

### Services

#### TempMediaService

```php
use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;

public function __construct(TempMediaServiceInterface $tempMediaService)
{
    $this->tempMediaService = $tempMediaService;
}
```

Methods:

- `uploadTempMedia(UploadedFile $file, ?string $sessionId = null, ?int $ttlHours = null): TempMediaUploadDTO`
  - Uploads a file as temporary media
  - Returns a DTO with information about the uploaded file

- `getTempMedia(string $id): ?TempMedia`
  - Gets a temporary media record by ID
  - Returns null if not found or expired

- `validateTempMediaIds(array $ids): array`
  - Validates that all IDs exist and are active
  - Throws an exception if any ID is invalid
  - Returns an array of TempMedia records

- `deleteTempMedia(string $id): bool`
  - Deletes a temporary media record
  - Returns true if successful, false otherwise

- `markAsProcessed(array $ids): void`
  - Marks temporary media records as processed

- `cleanupExpired(): int`
  - Cleans up expired temporary media records
  - Returns the number of records cleaned up

#### MediaTransferService

```php
use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;

public function __construct(MediaTransferServiceInterface $mediaTransferService)
{
    $this->mediaTransferService = $mediaTransferService;
}
```

Methods:

- `transferTempMediaToModel(HasMedia $model, TempMediaTransferDTO $tempMediaTransferDTO, string $collectionName = 'default'): MediaTransferDTO`
  - Transfers temporary media to a model
  - Returns a DTO with information about the transfer

- `cleanupProcessedTempMedia(): int`
  - Cleans up processed temporary media records
  - Returns the number of records cleaned up

- `validateOwnership(array $tempMediaIds, ?string $sessionId = null, ?string $userId = null): bool`
  - Validates that temporary media belongs to the specified session or user
  - Returns true if valid, false otherwise

- `getTransferStats(): array`
  - Gets statistics about temporary media transfers
  - Returns an array with counts of total, active, processed, and expired records

### DTOs

#### TempMediaUploadDTO

Properties:
- `id`: The ID of the uploaded temporary media
- `url`: The URL of the uploaded file
- `originalName`: The original name of the file
- `mimeType`: The MIME type of the file
- `size`: The size of the file in bytes
- `expiresAt`: When the temporary media will expire
- `sessionId`: The session ID associated with the upload

#### TempMediaTransferDTO

Methods:
- `fromArray(array $items): self`: Creates a DTO from an array of items
- `getItems(): array`: Gets the items in the DTO
- `getTempMediaIds(): array`: Gets the IDs of the temporary media items
- `isEmpty(): bool`: Checks if the DTO is empty
- `count(): int`: Gets the number of items in the DTO
- `toArray(): array`: Converts the DTO to an array

#### TempMediaItemDTO

Properties:
- `tempMediaId`: The ID of the temporary media
- `orderColumn`: The order of the media item (optional)

Methods:
- `fromArray(array $data): self`: Creates a DTO from an array
- `toArray(): array`: Converts the DTO to an array

#### MediaTransferDTO

Properties:
- `transferredMedia`: Array of transferred media items
- `failedTransfers`: Array of failed transfers
- `modelClass`: The class of the model
- `modelId`: The ID of the model
- `collectionName`: The name of the collection

Methods:
- `successful(array $transferredMedia, string $modelClass, string $modelId, string $collectionName): self`: Creates a successful transfer DTO
- `withFailures(array $transferredMedia, array $failedTransfers, string $modelClass, string $modelId, string $collectionName): self`: Creates a transfer DTO with failures
- `hasFailures(): bool`: Checks if the transfer had failures
- `toArray(): array`: Converts the DTO to an array

### Traits

#### HandlesTempMedia

Methods:
- `transferTempMedia(TempMediaTransferDTO $tempMediaTransferDTO, string $collectionName = 'default'): MediaTransferDTO`
  - Transfers temporary media to the model
  - Returns a DTO with information about the transfer

- `transferTempMediaAsProductImages(TempMediaTransferDTO $tempMediaTransferDTO): MediaTransferDTO`
  - Transfers temporary media to the 'product_images' collection
  - Returns a DTO with information about the transfer

- `getMediaUrlsFromTransfer(MediaTransferDTO $transferDto): array`
  - Gets URLs of transferred media
  - Returns an array of media information including URLs

### Events

#### TempMediaUploaded

Properties:
- `tempMedia`: The temporary media record
- `uploadDto`: The upload DTO

#### TempMediaExpired

Properties:
- `tempMedia`: The temporary media record

#### MediaTransferred

Properties:
- `targetModel`: The model that received the media
- `transferDto`: The transfer DTO

## Commands

### Cleanup Command

```bash
php artisan temp-media:cleanup
```

This command cleans up expired temporary media files. It is automatically scheduled to run hourly if auto-cleanup is enabled in the configuration.

Options:
- `--force`: Force cleanup of all temporary media, including non-expired ones
- `--processed`: Clean up only processed temporary media
- `--dry-run`: Show what would be cleaned up without actually deleting anything

## Troubleshooting

### Common Issues

#### Files Not Being Transferred

If files are not being transferred to models, check:
1. The temporary media IDs are valid and not expired
2. The model implements the `HasMedia` interface
3. The model uses the `HandlesTempMedia` trait
4. The collection name is correct

#### Expired Files Not Being Cleaned Up

If expired files are not being cleaned up, check:
1. The scheduler is running (`php artisan schedule:run`)
2. Auto-cleanup is enabled in the configuration
3. The cleanup command is registered in the scheduler

#### Permission Issues

If you encounter permission issues, check:
1. The storage directory is writable by the web server
2. The correct disk is configured in the configuration
3. The disk is properly configured in `config/filesystems.php`

### Debugging

You can enable event dispatching to help debug issues:

```php
// In your AppServiceProvider or a dedicated listener

Event::listen(TempMediaUploaded::class, function ($event) {
    Log::debug('Temp media uploaded', [
        'id' => $event->tempMedia->id,
        'session_id' => $event->tempMedia->session_id,
    ]);
});

Event::listen(MediaTransferred::class, function ($event) {
    Log::debug('Media transferred', [
        'model' => get_class($event->targetModel),
        'model_id' => $event->targetModel->getKey(),
        'transferred' => count($event->transferDto->transferredMedia),
        'failed' => count($event->transferDto->failedTransfers),
    ]);
});
```

You can also check the transfer statistics:

```php
use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;

$stats = app(MediaTransferServiceInterface::class)->getTransferStats();
Log::debug('Temp media stats', $stats);
```
