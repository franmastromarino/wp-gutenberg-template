<?php

namespace QuadLayers\Template;

use QuadLayers\Template\Abstracts\AbstractTemplate;

class SingleTemplate extends AbstractTemplate
{
    protected $slug = 'single';
    protected $title = 'Single';

    protected function registerTemplate()
    {
        add_filter('single_template_hierarchy', array( $this, 'updateTemplateHierarchy' ), 10, 1);
    }
}
