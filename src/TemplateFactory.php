<?php

namespace QuadLayers\Template;

use QuadLayers\Template\PostType\Custom;

class TemplateFactory
{
    private Custom $postType;
    private string $templateParent;
    private string $templatePath;

    public function setTemplateParent(string $templateParent): self
    {
        $this->templateParent = $templateParent;
        return $this;
    }

    public function setTemplatePostType(string $postType): self
    {
        $this->postType = new Custom($postType);
        return $this;
    }

    public function setTemplatePath(string $templatePath): self
    {
        $this->templatePath = $templatePath;
        return $this;
    }

    public function createSingle(): SingleTemplate
    {
        return new SingleTemplate($this->postType, $this->templatePath);
    }

    public function createArchive(): ArchiveTemplate
    {
        return new ArchiveTemplate($this->postType, $this->templatePath);
    }

    public function createTaxonomy(string $taxonomy): TaxonomyTemplate
    {
        return new TaxonomyTemplate($this->postType, $this->templatePath, $taxonomy);
    }
}
