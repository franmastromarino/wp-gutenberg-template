<?php

namespace QuadLayers\Template;

use QuadLayers\Template\Abstracts\BuildTemplate;

class TaxonomyTemplate extends BuildTemplate
{
    protected function registerTemplate()
    {
        add_filter('taxonomy_template_hierarchy', array( $this, 'updateTemplateHierarchy' ), 10, 1);
    }
}
