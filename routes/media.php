<?php

Route::group([
    'middleware' => ['bindings'],
    'namespace' => 'Febalist\Laravel\Media',
    'prefix' => 'media',
    'as' => 'media.',
], function () {
    Route::get('redirect/{media}', 'MediaController@redirect')->name('redirect');
    Route::get('download/{media}', 'MediaController@download')->name('download');
    Route::get('stream/{media}', 'MediaController@stream')->name('stream');
    Route::get('view/{media}', 'MediaController@view')->name('view');

    Route::get('gallery/{ids}', 'MediaController@gallery')->name('gallery');
    Route::get('zip/{ids}/{name}', 'MediaController@zip')->name('zip');
    Route::post('upload', 'MediaController@upload')->name('upload');
});
