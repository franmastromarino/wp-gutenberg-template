<?php

namespace QuadLayers\Template;

class Helpers
{
    public static function getTemplateFromQuery($slug, $postStatus)
    {
        $wp_query_args = array(
            'post_name__in'  => array( $slug ),
            'post_type'      => 'wp_template',
            'post_status'    => $postStatus,
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

    /**
     * Parses wp_template content and injects the current theme's
     * stylesheet as a theme attribute into each wp_template_part
     *
     * @param string $templateContent serialized wp_template content.
     *
     * @return string Updated wp_template content.
     */
    public static function injectThemeAttributeInContent($templateContent)
    {
        $hasUpdatedContent = false;
        $newContent         = '';
        $templateBlocks     = parse_blocks($templateContent);

        $blocks = Helpers::flattenBlocks($templateBlocks);
        foreach ($blocks as &$block) {
            /**
             * Make sure to remove the "theme":"xxxx" attribute from the templates
             */
            if (
                'core/template-part' === $block['blockName'] &&
                ! isset($block['attrs']['theme'])
            ) {
                $block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
                $hasUpdatedContent     = true;
            }
        }

        if ($hasUpdatedContent) {
            foreach ($templateBlocks as &$block) {
                $newContent .= serialize_block($block);
            }

            return $newContent;
        }

        return $templateContent;
    }

    /**
     * Checks to see if they are using a compatible version of WP, or if not they have a compatible version of the Gutenberg plugin installed.
     *
     * @return boolean
     */
    public static function supportsBlockTemplates()
    {
        if (
            ( ! function_exists('wp_is_block_theme') || ! wp_is_block_theme() ) &&
            ( ! function_exists('gutenberg_supports_block_templates') || ! gutenberg_supports_block_templates() )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array containing the references of
     * the passed blocks and their inner blocks.
     *
     * @param array $blocks array of blocks.
     *
     * @return array block references to the passed blocks and their inner blocks.
     */
    public static function flattenBlocks(&$blocks)
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
}
