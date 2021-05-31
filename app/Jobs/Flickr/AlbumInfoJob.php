<?php

namespace App\Jobs\Flickr;

use App\Models\FlickrAlbum;
use App\Services\Flickr\FlickrService;

/**
 * Get album information
 * @package App\Jobs\Flickr
 */
class AlbumInfoJob extends AbstractFlickrJob
{
    private string $albumId;
    private string $nsid;

    public function __construct(string $albumId, string $nsid)
    {
        $this->albumId = $albumId;
        $this->nsid = $nsid;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->getUnique([$this->albumId, $this->nsid]);
    }

    public function handle()
    {
        if (!$album = app(FlickrService::class)->getAlbumInfo($this->albumId, $this->nsid)) {
            FlickrAlbum::where(['id' => $this->albumId, 'owner' => $this->nsid])
                ->update(['state_code' => FlickrAlbum::STATE_INFO_FAILED]);

            return;
        }

        FlickrAlbum::updateOrCreate([
            'id' => $album['id'],
            'owner' => $album['owner']
        ], array_merge($album, ['state_code' => FlickrAlbum::STATE_INIT]));
    }
}
