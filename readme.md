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

    public function mediaConverter(Media $media)
    {
        $media->optimize();

        if ($media->collection == 'photo') {
            $media->converter('thumb', function (Image $image) {
                return $image->fit_crop(800, 450);
            });
        }
    }
```
