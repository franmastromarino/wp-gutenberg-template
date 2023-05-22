<?php

namespace QuadLayers\Template;

class Helpers {

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
