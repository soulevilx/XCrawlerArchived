<?php

namespace App\Jobs\Flickr;

use App\Jobs\Traits\HasUnique;
use App\Models\FlickrAlbum;
use App\Models\FlickrContact;
use App\Services\Flickr\FlickrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Get albums of contact
 * @package App\Jobs\Flickr
 */
class ContactAlbumbsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use HasUnique;

    private FlickrContact $contact;

    public function __construct(FlickrContact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addHours(6);
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->getUnique([$this->contact->nsid]);
    }

    public function handle()
    {
        $this->contact->updateState(FlickrContact::STATE_ALBUM_PROCESSING);
        $albums = app(FlickrService::class)->getContactAlbums($this->contact->nsid);

        if ($albums->isEmpty()) {
            $this->contact->updateState(FlickrContact::STATE_ALBUM_FAILED);
            return;
        }

        $albums->each(function ($albums) {
            foreach ($albums['photoset'] as $album) {
                $album['title']= isset($album['title']) ? $album['title']['_content'] : null;
                $album['description']= isset($album['description']) ? $album['description']['_content'] : null;
                FlickrAlbum::updateOrCreate([
                    'id' => $album['id'],
                    'owner' => $album['owner'],
                ], array_merge($album, ['state_code' => FlickrAlbum::STATE_INIT]));
            }
        });

        $this->contact->updateState(FlickrContact::STATE_ALBUM_COMPLETED);
    }
}
