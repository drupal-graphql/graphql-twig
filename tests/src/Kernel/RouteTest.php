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

  public function testRoute() {
    $result = $this->container->get('http_kernel')->handle(Request::create('/twig-test/test'));
    $content = $result->getContent();
    $this->assertContains('<h1>Shouting: TEST</h1>', $content);
    $this->assertContains('<p>This page is supposed to shout: TEST</p>', $content);
  }

}
