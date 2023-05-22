<?php

namespace QuadLayers\Template;

class TemplateFactory
{
    private string $templatePostType;
    private string $theme = 'QuadLayers';
    private string $templateFilePath;

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function setPostType(string $templatePostType): self
    {
        $this->templatePostType = $templatePostType;
        return $this;
    }

    public function setFilePath(string $templateFilePath): self
    {
        $this->templateFilePath = $templateFilePath;
        return $this;
    }

    public function createSingle(): TemplateProperties
    {

        $properties = new TemplateProperties(
			$this->theme,
            'single',
            'Single',
            $this->templatePostType,
            $this->templateFilePath
        );

        new SingleTemplate($properties);

        return $properties;
    }

    public function createArchive(): TemplateProperties
    {

        $properties = new TemplateProperties(
			$this->theme,
            'archive',
            'Archive',
            $this->templatePostType,
            $this->templateFilePath
        );

        if (!$properties->templatePostType->hasArchive()) {
            throw new \Exception("The post type {$properties->templatePostType->getPostType()} does not have an archive.");
        }

        new ArchiveTemplate($properties);

        return $properties;
    }

    public function createTaxonomy(string $taxonomy): TemplateProperties
    {

        $properties = new TemplateProperties(
			$this->theme,
            'taxonomy',
            'Taxonomy',
            $this->templatePostType,
            $this->templateFilePath
        );


        if (!taxonomy_exists($taxonomy)) {
            throw new \Exception("The taxonomy {$taxonomy} does not exist.");
        }

        if (!is_object_in_taxonomy($properties->templatePostType->getPostType(), $taxonomy)) {
            throw new \Exception("The taxonomy {$taxonomy} is not associated with the post type {$properties->templatePostType->getPostType()}.");
        }

        $properties->templateSlug =  "{$properties->slug}-{$taxonomy}";

        new TaxonomyTemplate($properties, $taxonomy);

        return $properties;
    }
}
