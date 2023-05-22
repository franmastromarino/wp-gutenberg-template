<?php

namespace QuadLayers\Template\Abstracts;

use InvalidArgumentException;
use QuadLayers\Template\Interfaces\PostTypeInterface;
use QuadLayers\Template\Helpers;

abstract class AbstractTemplate
{
    private $templatePostType  = 'wp_template';
    private $templateTheme = 'test';
    protected $slug;
    protected $title;
    protected $postType;
    protected $templateSlug;
    protected $templateTitle;
    protected $templateDesc;
    protected $templateFilePath;
    protected $templateId;

    public function __construct(PostTypeInterface $postType, string $filePath, string $templateTitle = '', string $templateDesc = '')
    {

        if (!$postType->getPostType()) {
            throw new InvalidArgumentException('Post type not found.');
            return;
        }

        $this->postType = $postType;
        $this->templateSlug = $this->getTemplateSlug();
        $this->templateId = $this->getTemplateId();

        if (is_dir($filePath)) {
            $filePath = trailingslashit($filePath) . $this->templateSlug . '.html';
        }

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Template file not found: ' . $filePath);
        }

        $this->templateFilePath = $filePath;
        $this->templateTitle = $templateTitle ? $templateTitle : "{$postType->getTitle()}: {$this->title}";
        $this->templateDesc = $templateDesc ? $templateDesc : "{$postType->getDescription()}: {$this->title}";

        add_filter('get_block_templates', array( $this, 'getBlockTemplates' ), 10, 3);
        add_filter('pre_get_block_template', array( $this, 'preGetBlockTemplate' ), 10, 3);
        add_filter('pre_get_block_templates', array( $this, 'preGetBlockTemplates' ), 10, 3);
        add_filter('pre_get_block_file_template', array( $this, 'preGetBlockFileTemplate' ), 10, 3);

        $this->registerTemplate();
    }

    public function getTemplateSlug()
    {
        return "{$this->slug}-{$this->postType->getPostType()}";
    }

    public function getTemplateId()
    {
        return $this->templateTheme . '//' . $this->templateSlug;
    }

    abstract protected function registerTemplate();

    public function updateTemplateHierarchy($templates)
    {
        if (get_post_type() === $this->postType->getPostType()) {
            return array( $this->templateSlug );
        }
        return $templates;
    }

    public function preGetBlockTemplate($template, $id, $templatePostType)
    {
        if ($id == $this->templateId) {
            $template = Helpers::getTemplateFromQuery($this->templateSlug, array( 'auto-draft', 'draft', 'publish', 'trash' ));

            if ($template) {
                return $template;
            }

            $template = $this->getTemplate();
        }

        return $template;
    }


    public function preGetBlockTemplates($template, $query, $templatePostType)
    {
        if (isset($query['slug__in']) && in_array($this->templateSlug, $query['slug__in'])) {
            $template = Helpers::getTemplateFromQuery($this->templateSlug, array( 'auto-draft', 'draft', 'publish' ));

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
        list($templateId, $templateSlug) = $templateNameParts;
        if (
            $this->templateTheme == $templateId &&
            $this->templateSlug == $templateSlug
        ) {
            $template = $this->getTemplate();
        }
        return $template;
    }

    protected function getTemplate()
    {

        $templateContent = file_get_contents($this->templateFilePath);

        $template                 = new \WP_Block_Template();
        $template->id             = $this->templateId;
        $template->theme          = $this->templateTheme;
        $template->content        = Helpers::injectThemeAttributeInContent($templateContent);
        $template->source         = 'plugin';
        $template->author         = null;
        $template->origin         = 'plugin';
        $template->area           = 'uncategorized';
        $template->slug           = $this->templateSlug;
        $template->type           = $this->templatePostType;
        $template->title          = $this->templateTitle;
        $template->description    = $this->templateDesc;
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
                    return $queryResultTemplate->slug === $this->templateSlug;
                }
            )
        ) {
            return $queryResult;
        }

        $template = Helpers::getTemplateFromQuery($this->templateSlug, array( 'auto-draft', 'draft', 'publish' ));

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
