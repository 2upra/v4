<?php

namespace App\Services\Post;

class PostMetaHandler
{
    /**
     * Copies a batch of specified post meta from a source post to a target post.
     *
     * @param int $sourcePostId The ID of the post to copy meta from.
     * @param int $targetPostId The ID of the post to copy meta to.
     * @param array $metaKeys An array of meta keys to copy.
     * @return void
     */
    public function copyMeta(int $sourcePostId, int $targetPostId, array $metaKeys): void
    {
        foreach ($metaKeys as $key) {
            $value = get_post_meta($sourcePostId, $key, true);
            if ($value !== '') { // Only copy if meta exists and is not empty
                update_post_meta($targetPostId, $key, $value);
            }
        }
    }

    /**
     * Deletes a batch of specified post meta from a post.
     *
     * @param int $postId The ID of the post from which to delete meta.
     * @param array $metaKeys An array of meta keys to delete.
     * @return void
     */
    public function deleteMetaBatch(int $postId, array $metaKeys): void
    {
        foreach ($metaKeys as $key) {
            delete_post_meta($postId, $key);
        }
    }
}
