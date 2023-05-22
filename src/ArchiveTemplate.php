<?php

namespace QuadLayers\Template;

use QuadLayers\Template\Abstracts\AbstractTemplate;
use QuadLayers\Template\Interfaces\PostTypeInterface;

class ArchiveTemplate extends AbstractTemplate
{
    protected $slug = 'archive';
    protected $title = 'Archive';

    public function __construct(PostTypeInterface $postType, string $filePath, string $template_title = '', string $template_desc = '')
    {

        if (!$postType->hasArchive()) {
            return;
        }

        parent::__construct($postType, $filePath, $template_title, $template_desc);
    }

    protected function registerTemplate()
    {
        add_filter('archive_template_hierarchy', array( $this, 'updateTemplateHierarchy' ), 10, 1);
    }

	public function getTemplateTitle()
    {
		if($this->templateTitle) {
			return $this->templateTitle;
		}

        return "{$this->postType->getTitle()}: {$this->title}";
    }
}
