<?php

namespace Drupal\Tests\graphql_twig\Kernel;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test dynamic routes added by GraphQL Twig.
 *
 * @group graphql_twig
 */
class BlockTest extends GraphQLTestBase {
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'graphql_twig',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['graphql_twig']);

    $themeName = 'graphql_twig_test_theme';

    /** @var \Drupal\Core\Extension\ThemeHandler $themeHandler */
    $themeHandler = $this->container->get('theme_handler');
    /** @var \Drupal\Core\Theme\ThemeInitialization $themeInitialization */
    $themeInitialization = $this->container->get('theme.initialization');
    /** @var \Drupal\Core\Theme\ThemeManager $themeManager */
    $themeManager = $this->container->get('theme.manager');

    $themeHandler->install([$themeName]);
    $theme = $themeInitialization->initTheme($themeName);
    $themeManager->setActiveTheme($theme);

    $this->mockField('shout', [
      'name' => 'shout',
      'type' => 'String',
      'arguments' => [
        'word' => 'String!',
      ],
    ], function ($value, $args) {
      yield strtoupper($args['word']);
    });

    // Rebuild routes to include theme routes.
    $this->container->get('router.builder')->rebuild();
  }

  protected function placeGraphQLBlock($id, $arguments = []) {
    $parameters = [];
    foreach ($arguments as $key => $value) {
      $parameters[] = [
        'key' => $key,
        'value' => $value,
      ];
    }
    $block = $this->placeBlock('graphql_twig:' . $id, [
      'region' => 'content',
      'theme' => 'graphql_twig_test_theme',
      'graphql_block' => $parameters,
    ]);
    $block->save();
  }

  /**
   * Test block without arguments.
   */
  public function testNoArguments() {
    $this->placeGraphQLBlock('block_no_arguments');
    $result = $this->container->get('http_kernel')->handle(Request::create('/static'));
    $content = $result->getContent();
    $this->assertContains('<p>This block shouts: DRUPAL</p>', $content);
  }

  /**
   * Test block with one argument.
   */
  public function testOneArgument() {
    $this->placeGraphQLBlock('block_one_argument', [
      'first' => 'drupal',
    ]);
    $result = $this->container->get('http_kernel')->handle(Request::create('/static'));
    $content = $result->getContent();
    $this->assertContains('<p>This block shouts: DRUPAL</p>', $content);
  }

  /**
   * Test block with multiple arguments.
   */
  public function testMultipleArguments() {
    $this->placeGraphQLBlock('block_multiple_arguments', [
      'first' => 'drupal',
      'second' => 'graphql',
    ]);
    $result = $this->container->get('http_kernel')->handle(Request::create('/static'));
    $content = $result->getContent();
    $this->assertContains('<p>This block shouts: DRUPAL and GRAPHQL</p>', $content);
  }


  /**
   * Test static block.
   */
  public function testStatic() {
    $this->placeGraphQLBlock('block_static');
    $result = $this->container->get('http_kernel')->handle(Request::create('/static'));
    $content = $result->getContent();
    $this->assertContains('<p>This is a static block.</p>', $content);
  }

  /**
   * Test missing block template.
   */
  public function testMissing() {
    $this->placeGraphQLBlock('block_missing');
    $result = $this->container->get('http_kernel')->handle(Request::create('/static'));
    $content = $result->getContent();
    $this->assertContains('<div class="error">Missing template for <em class="placeholder">block_missing</em>.</div>', $content);
  }

}
