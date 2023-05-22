<?php

namespace QuadLayers\Template\Abstracts;

use InvalidArgumentException;
use QuadLayers\Template\Helpers;
use QuadLayers\Template\TemplateProperties;

abstract class BuildTemplate
{
	protected $properties;

    public function __construct(TemplateProperties $properties)
    {

		$this->properties = $properties;

        if (!$properties->templatePostType->getPostType()) {
            throw new InvalidArgumentException('Post type not found.');
            return;
        }

        if (is_dir($this->properties->templateFilePath)) {
             $this->properties->templateFilePath= trailingslashit( $this->properties->templateFilePath) . $this->properties->templateSlug . '.html';
        }

        if (!file_exists( $this->properties->templateFilePath)) {
            throw new InvalidArgumentException('Template file not found: ' .  $this->properties->templateFilePath);
        }

        add_filter('get_block_templates', array( $this, 'getBlockTemplates' ), 10, 3);
        add_filter('pre_get_block_template', array( $this, 'preGetBlockTemplate' ), 10, 3);
        add_filter('pre_get_block_templates', array( $this, 'preGetBlockTemplates' ), 10, 3);
        add_filter('pre_get_block_file_template', array( $this, 'preGetBlockFileTemplate' ), 10, 3);

        $this->registerTemplate();
    }

    abstract protected function registerTemplate();

    public function updateTemplateHierarchy($templates)
    {
        if (get_post_type() === $this->properties->templatePostType->getPostType()) {
            return array( $this->properties->templateSlug );
        }
        return $templates;
    }

    public function preGetBlockTemplate($template, $id, $templatePostType)
    {
        if ($id == $this->properties->templateId) {
            $template = Helpers::getTemplateFromQuery($this->properties->templateSlug, array( 'auto-draft', 'draft', 'publish', 'trash' ));

            if ($template) {
                return $template;
            }

            $template = $this->getTemplate();
        }

        return $template;
    }


    public function preGetBlockTemplates($template, $query, $templatePostType)
    {
        if (isset($query['slug__in']) && in_array($this->properties->templateSlug, $query['slug__in'])) {
            $template = Helpers::getTemplateFromQuery($this->properties->templateSlug, array( 'auto-draft', 'draft', 'publish' ));

            if ($template) {
                return array( $template );
            }

            $template = array( $this->getTemplate() );
        }

        return $template;
    }


    public function preGetBlockFileTemplate($template, $id, $templatePostType)
    {
        $templateNameParts = explode('//', $id);
        if (count($templateNameParts) < 2) {
            return $template;
        }
        list($theme, $templateSlug) = $templateNameParts;
        if (
            $this->properties->theme == $theme &&
            $this->properties->templateSlug == $templateSlug
        ) {
            $template = $this->getTemplate();
        }
        return $template;
    }

    protected function getTemplate()
    {

        $templateContent = file_get_contents( $this->properties->templateFilePath);

        $template                 = new \WP_Block_Template();
        $template->id             = $this->properties->templateId;
        $template->theme          = $this->properties->theme;
        $template->content        = Helpers::injectThemeAttributeInContent($templateContent);
        $template->source         = 'plugin';
        $template->author         = null;
        $template->origin         = 'plugin';
        $template->area           = 'uncategorized';
        $template->slug           = $this->properties->templateSlug;
        $template->type           = 'wp_template';
        $template->title          = $this->properties->templateTitle;
        $template->description    = $this->properties->templateDesc;
        $template->status         = 'publish';
        $template->has_theme_file = true;
        $template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
        $template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

        return $template;
    }

    public function getBlockTemplates($queryResult, $query, $templatePostType)
    {

        if (! Helpers::supportsBlockTemplates()) {
            return $queryResult;
        }

        // Avoid adding the same template if it's already in the array of $queryResult.
        if (
            array_filter(
                $queryResult,
                function ($queryResultTemplate) {
                    return $queryResultTemplate->slug === $this->properties->templateSlug;
                }
            )
        ) {
            return $queryResult;
        }

        $template = Helpers::getTemplateFromQuery($this->properties->templateSlug, array( 'auto-draft', 'draft', 'publish' ));

        if (! $template) {
            $template = $this->getTemplate();
        }

        $fitsSlugQuery = ! isset($query['slug__in']) || in_array($template->slug, $query['slug__in'], true);

        $fitsAreaQuery = ! isset($query['area']) || $template->area === $query['area'];

        $should_include = $fitsSlugQuery && $fitsAreaQuery;

        if ($should_include) {
            $queryResult[] = $template;
        }

        return $queryResult;
    }
}
