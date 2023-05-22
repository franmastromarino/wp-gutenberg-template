<?php

namespace QuadLayers\Template;

use QuadLayers\Template\PostType\Custom as PostTypeCustom;

class TemplateProperties
{
    public $theme;
    public $slug;
    public $title;
    public $templatePostType;
    public $templateSlug;
    public $templateTitle;
    public $templateDesc;
    public $templateFilePath;
    public $templateId;

    public function __construct(
		string $theme,
        string $slug,
        string $title,
        string $templatePostType,
        string $templateFilePath,
        string $templateTitle = '',
        string $templateDesc = ''
    ) {
        $this->theme = $theme;
        $this->slug = $slug;
        $this->title = $title;
        $this->templatePostType = new PostTypeCustom($templatePostType);
        $this->templateFilePath = $templateFilePath;
        $this->templateTitle = $templateTitle ? $templateTitle : "{$this->templatePostType->getTitle()}: {$title}";
        $this->templateDesc = $templateDesc ? $templateDesc : "{$this->templatePostType->getDescription()}: {$title}";
        $this->templateSlug = "{$slug}-{$this->templatePostType->getPostType()}";
        $this->templateId = Helpers::titleToSlug($this->theme) . '//' . $this->templateSlug;
    }
}
