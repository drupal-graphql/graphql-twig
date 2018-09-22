<?php

namespace Drupal\Tests\graphql_twig\Kernel;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test dynamic routes added by GraphQL Twig.
 *
 * @group graphql_twig
 */
class RouteTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'graphql_twig',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

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

  /**
   * Test page without arguments.
   */
  public function testNoArguments() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/no-args'));
    $content = $result->getContent();
    $this->assertContains('<h1>Shouting: DRUPAL</h1>', $content);
    $this->assertContains('<p>This page is supposed to shout: DRUPAL</p>', $content);
  }

  /**
   * Test page with one argument.
   */
  public function testOneArgument() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/one-arg/drupal'));
    $content = $result->getContent();
    $this->assertContains('<h1>Shouting: DRUPAL</h1>', $content);
    $this->assertContains('<p>This page is supposed to shout: DRUPAL</p>', $content);
  }

  /**
   * Test page with multiple arguments.
   */
  public function testMultipleArguments() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/multi-args/drupal/graphql'));
    $content = $result->getContent();
    $this->assertContains('<h1>Shouting: DRUPAL and GRAPHQL</h1>', $content);
    $this->assertContains('<p>This page is supposed to shout: DRUPAL and GRAPHQL</p>', $content);
  }

  /**
   * Test page without query.
   */
  public function testStatic() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/static'));
    $content = $result->getContent();
    $this->assertContains('<h1>This is a static page</h1>', $content);
    $this->assertContains('<p>This page is static.</p>', $content);
  }

  /**
   * Test page without title.
   */
  public function testNoTitle() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/no-title'));
    $content = $result->getContent();
    $this->assertNotContains('<h1>', $content);
    $this->assertContains('<p>This page has no title.</p>', $content);
  }

  /**
   * Test page with forbidden access.
   */
  public function testNoAccess() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/no-access'));
    $this->assertEquals(403, $result->getStatusCode());
  }

  /**
   * Test page with forbidden access.
   */
  public function testMissing() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/missing'));
    $content = $result->getContent();
    $this->assertContains('<h1>Missing template</h1>', $content);
    $this->assertContains('<div class="error">Missing template for <em class="placeholder">missing</em>.</div>', $content);
  }

}
