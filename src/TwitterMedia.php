<?php
/**
 * TwitterMedia.php
 * Created By: nico
 * Created On: 12/17/2015
 */

namespace AWC\SocialPledge;

/**
 * Responsible for obtaining a twitter URL (t.co) for a given picture.
 *
 * @package AWC\SocialPledge
 */
class TwitterMedia
{
    /**
     * Retrieve the t.co URL for an image stored in WP.
     * If necessary, this will post the image to Twitter and extract the URL from the resulting status.
     *
     * @param $imageId
     * @return mixed|string
     */
    public function getTwitterUrl($imageId, $imageUrl)
    {
        $url = get_post_meta($imageId, 'twitter_url', true);
        // wonder if we should test it to make sure it is still OK?
        if (!$url) {
            $path = SocialImages::getImagePath($imageUrl);
            $url = $this->uploadPicture($imageId, $path);
            update_post_meta($imageId, 'twitter_url', $url);
        }
        return $url;
    }

    /**
     * Upload picture to Twitter media and create post to retrieve the t.co URL
     *
     * @param int $imageId
     * @return string
     */
    private function uploadPicture($imageId, $path)
    {
        $title = get_the_title($imageId);
        $connection = (new TwitterLogin())->getTwitterConnection();
        $media = $connection->upload('media/upload', ['media' => $path]);
        if ($connection->getLastHttpCode() != 200) {
            error_log('Error uploading media to twitter: ' . $connection->getLastHttpCode());
            return '';
        }
        $status = $connection->post('statuses/update', [
            'status' => $title,
            'media_ids' => $media->media_id_string
        ]);
        if ($connection->getLastHttpCode() != 200) {
            error_log('Error posting image to twitter: ' . $connection->getLastHttpCode());
            return '';
        }
        return $this->extractImageUrl($status->text);
    }

    private function extractImageUrl($statusText)
    {
        $matches = [];
        if (preg_match('#https://t.co/\w+#', $statusText, $matches)) {
            return $matches[0];
        } else {
            error_log('Could not find t.co reference in ' . $statusText);
            return '';
        }
    }
}
