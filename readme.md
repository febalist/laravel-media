```bash
composer require febalist/laravel-media
php artisan vendor:publish --provider Febalist\Laravel\Media\MediaServiceProvider
``` 

```dotenv
MEDIA_DISK=public
MEDIA_PATH=media
MEDIA_QUEUE=media
```

```php
    use HasMedia;

    public function mediaConvert(Media $media)
    {
        $media->optimize();

        $media->convert('thumb', function (Image $image) {
            return $image->fit_crop(400, 300);
        });
    }
```
