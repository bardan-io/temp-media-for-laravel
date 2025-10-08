# Laravel Temp Media

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yourvendor/laravel-temp-media.svg?style=flat-square)](https://packagist.org/packages/yourvendor/laravel-temp-media)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/yourvendor/laravel-temp-media/run-tests?label=tests)](https://github.com/yourvendor/laravel-temp-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/yourvendor/laravel-temp-media.svg?style=flat-square)](https://packagist.org/packages/yourvendor/laravel-temp-media)

A Laravel package for handling temporary media uploads with automatic cleanup, built on top of Spatie Media Library.

## Features

- ✅ **Two-Phase Upload Pattern** - Upload images first, associate with models later
- ✅ **Automatic Cleanup** - TTL-based expiration and background cleanup jobs
- ✅ **Security** - Session/user-based ownership validation
- ✅ **Type Safety** - Full PHP 8.1+ type declarations with strict types
- ✅ **Events** - Comprehensive event system for monitoring and logging
- ✅ **Testing** - Complete test suite with factories and feature tests
- ✅ **Configurable** - Extensive configuration options
- ✅ **Production Ready** - Built with SOLID principles and clean architecture

## Installation

You can install the package via composer:

```bash
composer require yourvendor/laravel-temp-media
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="temp-media-migrations"
php artisan migrate
```

Optionally, you can publish the config file:

```bash
php artisan vendor:publish --tag="temp-media-config"
```

## Usage

### Basic Upload Flow

```php
// 1. Upload images individually
$uploadResponse = app(TempMediaServiceInterface::class)->uploadTempMedia(
    $uploadedFile,
    session()->getId(),
    auth()->id()
);

// 2. Create product with temp media IDs
$product = Product::create([
    'name' => 'My Product',
    'description' => 'Product description',
    // ... other fields
]);

// 3. Transfer temp media to product
$transferResult = $product->transferTempMedia(['temp-media-uuid-1', 'temp-media-uuid-2']);
```

### Using the Trait

Add the `HandlesTempMedia` trait to your models:

```php
use BardanIO\TempMedia\Traits\HandlesTempMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HandlesTempMedia;

    // Transfer temp media to default collection
    public function attachTempImages(array $tempMediaIds)
    {
        return $this->transferTempMedia($tempMediaIds, 'product_images');
    }
}
```

### API Endpoints

The package automatically registers these API routes:

```http
POST   /api/temp-media              # Upload file
GET    /api/temp-media              # List temp media
GET    /api/temp-media/{id}         # Get temp media details
DELETE /api/temp-media/{id}         # Delete temp media
POST   /api/temp-media/validate     # Validate temp media IDs
```

### Frontend Integration

```javascript
// Upload file
const formData = new FormData();
formData.append('file', file);
formData.append('session_id', sessionId);

const response = await fetch('/api/temp-media', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': csrfToken
    }
});

const result = await response.json();
// result.data contains: { id, url, thumb_url, original_name, expires_at }

// Create product with temp media
const productData = {
    name: 'Product Name',
    temp_media_ids: [result.data.id]
};

await fetch('/api/products', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify(productData)
});
```

## Configuration

```php
return [
    // TTL for temporary files (hours)
    'default_ttl_hours' => 24,
    
    // Maximum file size in bytes
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    
    // Allowed MIME types
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],
    
    // Storage disk
    'disk' => 'public',
    
    // Enable automatic cleanup
    'enable_auto_cleanup' => true,
    
    // Route configuration
    'routes' => [
        'prefix' => 'api/temp-media',
        'middleware' => ['api'],
    ],
    
    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
];
```

## Commands

### Cleanup Expired Media

```bash
# Manual cleanup
php artisan temp-media:cleanup

# The package automatically registers this to run hourly
```

## Events

The package dispatches several events you can listen to:

```php
// Listen for uploads
Event::listen(TempMediaUploaded::class, function ($event) {
    Log::info('Temp media uploaded', [
        'id' => $event->tempMedia->id,
        'user' => $event->uploadDto->userId,
    ]);
});

// Listen for transfers
Event::listen(MediaTransferred::class, function ($event) {
    Log::info('Media transferred', [
        'model' => get_class($event->targetModel),
        'count' => $event->transferDto->transferredCount,
    ]);
});
```

## Testing

```bash
composer test
```

## Security

- UUID-based media IDs prevent enumeration
- Session/user ownership validation
- File type and size validation
- Rate limiting on upload endpoints
- Automatic cleanup prevents storage bloat

## Architecture

The package follows clean architecture principles:

- **Services** - Business logic layer
- **DTOs** - Data transfer objects for type safety
- **Contracts** - Interfaces for dependency injection
- **Events** - Domain events for extensibility
- **Traits** - Reusable functionality for models

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
