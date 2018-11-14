```bash
composer require febalist/laravel-media
php artisan vendor:publish --provider 'Febalist\Laravel\Media\MediaServiceProvider'
php artisan migrate
npm install @febalist/laravel-media
``` 

```dotenv
MEDIA_DISK=cloud
MEDIA_PATH=media
MEDIA_QUEUE=media
```

```php
    use HasMedia;

    public function mediaConvert(MediaConverter $converter)
    {
        $converter->optimize();
        $converter->conversion('thumb')->fit_crop(800, 450);
    }
```

## Vue input

```javascript
Vue.use(require('@febalist/laravel-media'));
```

```html
{{ $model->mediaInput() }}
```

```php
$model->updateMediaFromInput();
```

## JS helpers

```javascript
const media = require('@febalist/laravel-media/src/media');

media.select_images().then(files => {
  media.upload(files, {
    model_type: 'App\\User',
    model_id: 1,
    onprogress: function(progress, uploaded, event) {
      console.log({progress, uploaded, event});
    },
    onuploaded: function(result, error, file) {
      console.log({result, error, file});
    },
  }).then(results => {
    console.log(results);
  });
});
```
