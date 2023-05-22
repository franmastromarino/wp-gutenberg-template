<?php

namespace QuadLayers\Template\Tests;

use PHPUnit\Framework\TestCase;
use QuadLayers\Template\SingleTemplate;
use QuadLayers\Template\ArchiveTemplate;
use QuadLayers\Template\TaxonomyTemplate;
use QuadLayers\Template\PostType\Custom;
use Brain\Monkey;
use Brain\Monkey\Functions;

class TemplateTest extends TestCase
{
    private $postType;
    private $singleTemplate;
    private $archiveTemplate;
    private $taxonomyTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Set up WordPress function mocks
        $this->mockWordPressFunctions();

        // Instantiate PostType
        $this->postType = new Custom('test_post');

        // Inject the PostType instance into the templates
        $this->singleTemplate = new SingleTemplate($this->postType, __DIR__ . '/templates');
        $this->archiveTemplate = new ArchiveTemplate($this->postType, __DIR__ . '/templates');
        $this->taxonomyTemplate = new TaxonomyTemplate($this->postType, __DIR__ . '/templates', 'test_taxonomy');
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
            'name' => 'test_post',
            'description' => 'Test Post',
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

    public function testSingleTemplateCreation()
    {
        $this->assertInstanceOf(SingleTemplate::class, $this->singleTemplate);
    }

    public function testArchiveTemplateCreation()
    {
        $this->assertInstanceOf(ArchiveTemplate::class, $this->archiveTemplate);
    }

    public function testTaxonomyTemplateCreation()
    {
        $this->assertInstanceOf(TaxonomyTemplate::class, $this->taxonomyTemplate);
    }

    public function testSingleTemplateSlug()
    {
        $this->assertEquals('single-test_post', $this->singleTemplate->getTemplateSlug());
    }

    public function testArchiveTemplateSlug()
    {
        $this->assertEquals('archive-test_post', $this->archiveTemplate->getTemplateSlug());
    }

    public function testTaxonomyTemplateSlug()
    {
        $this->assertEquals('taxonomy-test_taxonomy', $this->taxonomyTemplate->getTemplateSlug());
    }
}
