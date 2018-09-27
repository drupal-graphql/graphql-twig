<?php

namespace Drupal\Tests\graphql_twig\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\graphql\GraphQL\Execution\QueryResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\graphql\Traits\GraphQLFileTestTrait;
use Drupal\Tests\graphql_twig\Traits\ThemeTestTrait;
use GraphQL\Server\OperationParams;
use Prophecy\Argument;

/**
 * Tests that test GraphQL theme integration on module level.
 *
 * @group graphql_twig
 */
class ThemeTest extends KernelTestBase {
  use GraphQLFileTestTrait;
  use ThemeTestTrait;

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $contextManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'graphql',
    'graphql_twig',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupThemeTest();
  }

  /**
   * Test query assembly.
   */
  public function testQueryAssembly() {
    /** @var \Prophecy\Prophecy\MethodProphecy $process */
    $this->processor
      ->processQuery(Argument::any(), Argument::that(function (OperationParams $params) {
        return $params->query === $this->getQuery('garage.gql');
      }))
      ->willReturn(new QueryResult())
      ->shouldBeCalled();

    $element = ['#theme' => 'graphql_garage'];
    $this->render($element);
  }

  /**
   * Test query caching.
   */
  public function testCacheableQuery() {

    $metadata = new CacheableMetadata();
    $metadata->setCacheMaxAge(-1);

    $process = $this->processor
      ->processQuery(Argument::any(), Argument::any())
      ->willReturn(new QueryResult([], [], [], $metadata));

    $element = [
      '#theme' => 'graphql_garage',
      '#cache' => [
        'keys' => ['garage'],
      ],
    ];

    $renderer = $this->container->get('renderer');
    $element_1 = $element;
    $element_2 = $element;

    $renderer->renderRoot($element_1);
    $renderer->renderRoot($element_2);

    $process->shouldHaveBeenCalledTimes(1);
  }

  /**
   * Test query caching.
   */
  public function testUncacheableQuery() {

    $metadata = new CacheableMetadata();
    $metadata->setCacheMaxAge(0);

    $process = $this->processor
      ->processQuery(Argument::any(), Argument::any())
      ->willReturn(new QueryResult([], [], [], $metadata));

    $element = [
      '#theme' => 'graphql_garage',
      '#cache' => [
        'keys' => ['garage'],
      ],
    ];

    $renderer = $this->container->get('renderer');
    $element_1 = $element;
    $element_2 = $element;

    $renderer->renderRoot($element_1);
    $renderer->renderRoot($element_2);

    $process->shouldHaveBeenCalledTimes(2);
  }

}
