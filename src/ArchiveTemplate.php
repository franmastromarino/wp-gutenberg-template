<?php

namespace QuadLayers\Template;

use QuadLayers\Template\Abstracts\BuildTemplate;

class ArchiveTemplate extends BuildTemplate
{
    protected function registerTemplate()
    {
        add_filter('archive_template_hierarchy', array( $this, 'updateTemplateHierarchy' ), 10, 1);
    }
}
