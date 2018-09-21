<?php

namespace Drupal\graphql_twig\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\graphql\GraphQL\Execution\QueryProcessor;
use GraphQL\Server\OperationParams;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for configuration-generated GraphQL-Twig routes.
 */
class RouteController extends ControllerBase {

  /**
   * @var \Drupal\graphql\GraphQL\Execution\QueryProcessor
   */
  protected $queryProcessor;

  /**
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('graphql.query_processor'),
      $container->get('twig')
    );
  }

  /**
   * RouteController constructor.
   *
   * @param \Drupal\graphql\GraphQL\Execution\QueryProcessor $processor
   *   A GraphQL query processor.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   A Twig environment.
   */
  public function __construct(QueryProcessor $processor, TwigEnvironment $twig) {
    $this->queryProcessor = $processor;
    $this->twig = $twig;
  }

  /**
   * Generic page callback.
   *
   * Accepts a theme hook and an array of theme variables.
   *
   * @param $_graphql_theme_hook
   *   The theme hook.
   * @param $_graphql_arguments
   *   Query arguments
   *
   * @return array
   *   The render array build.
   */
  public function page($_graphql_theme_hook, $_graphql_arguments) {
    return [
      '#theme' => $_graphql_theme_hook,
      '#graphql_arguments' => $_graphql_arguments,
    ];
  }

  /**
   * Build a page title from a twig template and a GraphQL query.
   *
   * @param $_graphql_title
   * @param $_graphql_title_query
   * @param $_graphql_arguments
   *
   * @return \Drupal\Component\Render\MarkupInterface|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function title($_graphql_title, $_graphql_title_query, $_graphql_arguments) {
    if ($_graphql_title_query) {
      $result = $this->queryProcessor->processQuery('default:default',
        OperationParams::create([
          'query' => $_graphql_title_query,
          'variables' => $_graphql_arguments,
        ])
      );
      $_graphql_title = $this->twig->renderInline($_graphql_title, $result->data);
    }
    else {
      $_graphql_title = $this->t($_graphql_title);
    }
    return $_graphql_title;
  }

}
