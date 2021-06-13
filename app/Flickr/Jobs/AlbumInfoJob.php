<?php

namespace App\Flickr\Jobs;

use App\Models\FlickrAlbum;
use App\Services\Flickr\FlickrService;

/**
 * Get album information
 * @package App\Flickr\Jobs\
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

    public function handle(FlickrService $service)
    {
        $album = $service->getAlbumInfo($this->albumId, $this->nsid);
        if ($album->isEmpty()) {
            FlickrAlbum::where(['id' => $this->albumId, 'owner' => $this->nsid])
                ->update(['state_code' => FlickrAlbum::STATE_INFO_FAILED]);

            return;
        }

        $album = FlickrAlbum::updateOrCreate([
            'id' => $album['id'],
            'owner' => $album['owner']
        ], $album->merge(['state_code' => FlickrAlbum::STATE_INIT])->toArray());
    }
}
