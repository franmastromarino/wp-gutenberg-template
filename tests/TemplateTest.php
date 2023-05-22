<?php

namespace QuadLayers\Template\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use QuadLayers\Template\TemplateFactory;

class TemplateTest extends TestCase
{
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Set up WordPress function mocks
        $this->mockWordPressFunctions();

        // Instantiate PostType
        $this->factory = (new TemplateFactory())
            ->setTemplatePostType('the_post')
            ->setTemplatePath(__DIR__ . '/templates');

        $this->factory->createSingle();
        $this->factory->createArchive();
        $this->factory->createTaxonomy('the_post_tag');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function mockWordPressFunctions()
    {
        // Mocking get_post_type_object
        Functions\when('get_post_type_object')->justReturn((object) [
            'labels' => (object) ['name' => 'Test Posts'],
            'name' => 'The Post',
            'description' => 'The post description.',
            'public' => true,
            'has_archive' => true,
            'publicly_queryable' => true,
        ]);

        Functions\when('add_filter')->alias(function ($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
            // Check if the passed values are as expected.

            // $this->assertSame(10, $priority);
            // $this->assertSame(1, $accepted_args);
            // $this->assertTrue(is_callable($function_to_add));

            // Call the function to add.
            // call_user_func($function_to_add, 'updateTemplateHierarchy');
        });

        // Mocking post_type_exists
        Functions\when('post_type_exists')->justReturn(true);

        // Mocking taxonomy_exists
        Functions\when('taxonomy_exists')->justReturn(true);

        // Mocking is_object_in_taxonomy
        Functions\when('is_object_in_taxonomy')->justReturn(true);
    }

    public function testSingleTemplateSlug()
    {
        $this->assertEquals('single-the_post', $this->factory->createSingle()->templateSlug);
    }

    public function testArchiveTemplateSlug()
    {
        $this->assertEquals('archive-the_post', $this->factory->createArchive()->templateSlug);
    }

    public function testTaxonomyTemplateSlug()
    {
        $this->assertEquals('taxonomy-the_post_tag', $this->factory->createTaxonomy('the_post_tag')->templateSlug);
    }
}
