<?php

namespace QuadLayers\Template\Interfaces;

interface PostTypeInterface
{
    public function getPostType(): string;
    public function getTitle(): string;
    public function getDescription(): string;
    public function hasArchive(): bool;
}
