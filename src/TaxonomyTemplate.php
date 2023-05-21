<?php

namespace QuadLayers\Template;

use QuadLayers\Template\Abstracts\AbstractTemplate;
use QuadLayers\Template\Interfaces\PostTypeInterface;

class TaxonomyTemplate extends AbstractTemplate
{
    protected $taxonomy;
    protected $slug = 'taxonomy';

    public function __construct(PostTypeInterface $postType, string $filePath, string $taxonomy, string $template_title = '', string $template_desc = '')
    {

        if (!taxonomy_exists($taxonomy)) {
            throw new \Exception("The taxonomy {$taxonomy} does not exist.");
        }

        if (!is_object_in_taxonomy($postType->getPostType(), $taxonomy)) {
            throw new \Exception("The taxonomy {$taxonomy} is not associated with the post type {$postType->getPostType()}.");
        }

        parent::__construct($postType, $filePath, $template_title, $template_desc);
    }

    protected function generateSlug()
    {
        $templateType = strtolower((new \ReflectionClass($this))->getShortName());
        return "{$templateType}-{$this->postType->getPostType()}-{$this->taxonomy}";
    }

    protected function registerTemplate()
    {
        add_filter('taxonomy_template_hierarchy', array( $this, 'updateTemplateHierarchy' ), 10, 1);
    }
}
