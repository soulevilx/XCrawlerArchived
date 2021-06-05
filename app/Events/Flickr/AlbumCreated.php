<?php

namespace App\Events\Flickr;

use App\Models\FlickrAlbum;

class AlbumCreated
{
    public FlickrAlbum $album;

    public function __construct(FlickrAlbum $album)
    {
        $this->album = $album;
    }
}