<?php

namespace QuadLayers\Template;

use QuadLayers\Template\Abstracts\AbstractTemplate;
use QuadLayers\Template\Interfaces\PostTypeInterface;

class TaxonomyTemplate extends AbstractTemplate
{
    protected $taxonomy;
    protected $slug = 'taxonomy';
    protected $title = 'Taxonomy';

    public function __construct(PostTypeInterface $postType, string $filePath, string $taxonomy, string $templateTitle = '', string $template_desc = '')
    {

        if (!taxonomy_exists($taxonomy)) {
            throw new \Exception("The taxonomy {$taxonomy} does not exist.");
        }

        if (!is_object_in_taxonomy($postType->getPostType(), $taxonomy)) {
            throw new \Exception("The taxonomy {$taxonomy} is not associated with the post type {$postType->getPostType()}.");
        }

		$this->taxonomy = $taxonomy;

        parent::__construct($postType, $filePath, $templateTitle, $template_desc);
    }

    public function getTemplateSlug()
    {
        return "{$this->slug}-{$this->taxonomy}";
    }

	public function getTemplateTitle()
    {
		if($this->templateTitle) {
			return $this->templateTitle;
		}

        return "{$this->postType->getTitle()}: {$this->title}";
    }

    protected function registerTemplate()
    {
        add_filter('taxonomy_template_hierarchy', array( $this, 'updateTemplateHierarchy' ), 10, 1);
    }
}
