```bash
composer require febalist/laravel-media
php artisan vendor:publish --provider Febalist\Laravel\Media\MediaServiceProvider
php artisan migrate
``` 

```dotenv
MEDIA_DISK=cloud
MEDIA_PATH=media
MEDIA_QUEUE=media
```

```php
    use HasMedia;

    public function mediaConvert(Media $media)
    {
        $media->optimize();

        $media->converter('thumb', function (Image $image) {
            return $image->fit_crop(800, 450);
        });
    }
```
