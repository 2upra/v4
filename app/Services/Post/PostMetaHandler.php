<?php

namespace App\Services\Post;

// This class will be fully implemented in Task RF-PMH-002.
// For now, it's a placeholder to satisfy dependency injection.
class PostMetaHandler
{
    public function __construct()
    {
        // Constructor for PostMetaHandler
    }

    // Placeholder methods for future implementation
    public function copyMeta(int $sourcePostId, int $targetPostId, array $metaKeys): void
    {
        // Logic to be implemented in RF-PMH-002
    }

    public function deleteMetaBatch(int $postId, array $metaKeys): void
    {
        // Logic to be implemented in RF-PMH-002
    }
}
