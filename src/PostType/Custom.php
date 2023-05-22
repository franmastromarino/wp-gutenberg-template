<?php

namespace QuadLayers\Template\PostType;

use QuadLayers\Template\Interfaces\PostTypeInterface;

class Custom implements PostTypeInterface
{
    private $postType;
    private $postTypeObject;

    public function __construct(string $postType)
    {
        if (!post_type_exists($postType)) {
            return;
        }

        $this->postType = $postType;
        $this->postTypeObject = get_post_type_object($this->postType);

        if (!$this->postTypeObject->publicly_queryable) {
            throw new \InvalidArgumentException("The post type {$this->postType} is not publicly queryable.");
        }
    }

    public function getPostType(): string
    {
        return $this->postType ?? '';
    }

    public function getTitle(): string
    {
        // Fetch the label for the title from the stored post type object
        return $this->postTypeObject->labels->name ?? '';
    }

    public function getDescription(): string
    {
        // Fetch the label for the description from the stored post type object
        return $this->postTypeObject->description ?? '';
    }

    public function hasArchive(): bool
    {
        return $this->postTypeObject->has_archive;
    }
}
