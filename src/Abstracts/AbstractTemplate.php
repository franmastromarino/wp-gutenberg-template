<?php

namespace QuadLayers\Template\Abstracts;

use InvalidArgumentException;
use QuadLayers\Template\Interfaces\PostTypeInterface;

abstract class AbstractTemplate
{
    private $template_type  = 'wp_template';
    private $template_theme = 'test';
    protected $slug;
    protected $postType;
    protected $template_slug;
    protected $template_title;
    protected $template_desc;
    protected $template_file_path;
    protected $template_id;

    public function __construct(PostTypeInterface $postType, string $filePath, string $template_title = '', string $template_desc = '')
    {

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Template file not found: ' . $filePath);
        }

        $this->postType = $postType;
        $this->template_slug = $this->getTemplateSlug();
        $this->template_file_path = $filePath;
        $this->template_title = $template_title ? $template_title : ($postType->getTitle() ? $postType->getTitle() : $this->template_title);
        $this->template_desc = $template_desc ? $template_desc : ($postType->getDescription() ? $postType->getDescription() : $this->template_desc);

        add_filter('get_block_templates', array( $this, 'get_block_templates' ), 10, 3);
        add_filter('pre_get_block_template', array( $this, 'pre_get_block_template' ), 10, 3);
        add_filter('pre_get_block_templates', array( $this, 'pre_get_block_templates' ), 10, 3);
        add_filter('pre_get_block_file_template', array( $this, 'pre_get_block_file_template' ), 10, 3);

        $this->registerTemplate();
    }

    public function getTemplateSlug()
    {
        return "{$this->slug}-{$this->postType->getPostType()}";
    }

    abstract protected function registerTemplate();

    protected function updateTemplateHierarchy($templates)
    {
        if (get_post_type() === $this->postType->getPostType()) {
            return array( $this->template_slug );
        }
        return $templates;
    }

    public function get_template_from_query($slug, $post_status)
    {
        $wp_query_args = array(
            'post_name__in'  => array( $slug ),
            'post_type'      => $this->template_type,
            'post_status'    => $post_status,
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            // 'tax_query'      => array(
            // array(
            // 'taxonomy' => 'wp_theme',
            // 'field'    => 'name',
            // 'terms'    => $theme,
            // ),
            // ),
        );

        $template_query = new \WP_Query($wp_query_args);
        $posts          = $template_query->posts;

        if (count($posts) > 0) {
            $template                 = _build_block_template_result_from_post($posts[0]);
            $template->origin         = 'plugin';
            $template->is_custom      = false;
            $template->post_types     = array();
            $template->area           = 'uncategorized';
            $template->has_theme_file = true;
            $template->author         = null;
            if (! is_wp_error($template)) {
                return $template;
            }
        }
        return false;
    }

    public function pre_get_block_template($template, $id, $template_type)
    {
        if ($id == $this->template_id) {
            $template = $this->get_template_from_query($this->template_slug, array( 'auto-draft', 'draft', 'publish', 'trash' ));

            if ($template) {
                return $template;
            }

            $template = $this->get_template();
        }

        return $template;
    }


    public function pre_get_block_templates($template, $query, $template_type)
    {
        if (isset($query['slug__in']) && in_array($this->template_slug, $query['slug__in'])) {
            $template = $this->get_template_from_query($this->template_slug, array( 'auto-draft', 'draft', 'publish' ));

            if ($template) {
                return array( $template );
            }

            $template = array( $this->get_template() );
        }

        return $template;
    }


    public function pre_get_block_file_template($template, $id, $template_type)
    {
        $template_name_parts = explode('//', $id);
        if (count($template_name_parts) < 2) {
            return $template;
        }
        list($template_id, $template_slug) = $template_name_parts;
        if (
            $this->template_theme == $template_id &&
            $this->template_slug == $template_slug
        ) {
            $template = $this->get_template();
        }
        return $template;
    }

    /**
     * Converts template slugs into readable titles.
     *
     * @param string $template_slug The templates slug (e.g. single-product).
     * @return string Human friendly title converted from the slug.
     */
    protected function convert_slug_to_title($template_slug)
    {
        // Replace all hyphens and underscores with spaces.
        return ucwords(preg_replace('/[\-_]/', ' ', $template_slug));
    }
    /**
     * Returns an array containing the references of
     * the passed blocks and their inner blocks.
     *
     * @param array $blocks array of blocks.
     *
     * @return array block references to the passed blocks and their inner blocks.
     */
    public static function flatten_blocks(&$blocks)
    {
        $all_blocks = array();
        $queue      = array();
        foreach ($blocks as &$block) {
            $queue[] = &$block;
        }
        $queue_count = count($queue);

        while ($queue_count > 0) {
            $block = &$queue[0];
            array_shift($queue);
            $all_blocks[] = &$block;

            if (! empty($block['innerBlocks'])) {
                foreach ($block['innerBlocks'] as &$inner_block) {
                    $queue[] = &$inner_block;
                }
            }

            $queue_count = count($queue);
        }

        return $all_blocks;
    }
    /**
     * Parses wp_template content and injects the current theme's
     * stylesheet as a theme attribute into each wp_template_part
     *
     * @param string $template_content serialized wp_template content.
     *
     * @return string Updated wp_template content.
     */
    public static function inject_theme_attribute_in_content($template_content)
    {
        $has_updated_content = false;
        $new_content         = '';
        $template_blocks     = parse_blocks($template_content);

        $blocks = self::flatten_blocks($template_blocks);
        foreach ($blocks as &$block) {
            /**
             * Make sure to remove the "theme":"xxxx" attribute from the templates
             */
            if (
                'core/template-part' === $block['blockName'] &&
                ! isset($block['attrs']['theme'])
            ) {
                $block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
                $has_updated_content     = true;
            }
        }

        if ($has_updated_content) {
            foreach ($template_blocks as &$block) {
                $new_content .= serialize_block($block);
            }

            return $new_content;
        }

        return $template_content;
    }

    protected function get_template()
    {
        $template_title = $this->template_title ? $this->template_title : $this->convert_slug_to_title($this->template_slug);

        $template_file_path = $this->template_file_path . '.html';

        $template_content = file_get_contents($template_file_path);

        $template                 = new \WP_Block_Template();
        $template->id             = $this->template_id;
        $template->theme          = $this->template_theme;
        $template->content        = self::inject_theme_attribute_in_content($template_content);
        $template->source         = 'plugin';
        $template->author         = null;
        $template->origin         = 'plugin';
        $template->area           = 'uncategorized';
        $template->slug           = $this->template_slug;
        $template->type           = $this->template_type;
        $template->title          = $template_title;
        $template->description    = $this->template_desc;
        $template->status         = 'publish';
        $template->has_theme_file = true;
        $template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
        $template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.

        return $template;
    }

    /**
     * Checks to see if they are using a compatible version of WP, or if not they have a compatible version of the Gutenberg plugin installed.
     *
     * @return boolean
     */
    public static function supports_block_templates()
    {
        if (
            ( ! function_exists('wp_is_block_theme') || ! wp_is_block_theme() ) &&
            ( ! function_exists('gutenberg_supports_block_templates') || ! gutenberg_supports_block_templates() )
        ) {
            return false;
        }

        return true;
    }

    public function get_block_templates($query_result, $query, $template_type)
    {

        if (! self::supports_block_templates()) {
            return $query_result;
        }

        // Avoid adding the same template if it's already in the array of $query_result.
        if (
            array_filter(
                $query_result,
                function ($query_result_template) {
                    return $query_result_template->slug === $this->template_slug;
                }
            )
        ) {
            return $query_result;
        }

        $template = $this->get_template_from_query($this->template_slug, array( 'auto-draft', 'draft', 'publish' ));

        if (! $template) {
            $template = $this->get_template();
        }

        $fits_slug_query = ! isset($query['slug__in']) || in_array($template->slug, $query['slug__in'], true);

        $fits_area_query = ! isset($query['area']) || $template->area === $query['area'];

        $should_include = $fits_slug_query && $fits_area_query;

        if ($should_include) {
            $query_result[] = $template;
        }

        return $query_result;
    }
}
