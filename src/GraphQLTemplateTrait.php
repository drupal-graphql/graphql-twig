<?php

namespace Drupal\graphql_twig;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\GraphQL\Execution\QueryProcessor;
use GraphQL\Server\OperationParams;

/**
 * Trait that will be attached to all GraphQL enabled Twig templates.
 */
trait GraphQLTemplateTrait {

  /**
   * @return bool
   */
  abstract public static function hasGraphQLOperations();

  /**
   * @var string
   */
  abstract public static function rawGraphQLQuery();

  /**
   * @return string
   */
  abstract public static function rawGraphQLParent();

  /**
   * @return string[]
   */
  abstract public static function rawGraphQLIncludes();

  /**
   * @return string[]
   */
  abstract public static function rawGraphQLArguments();

  /**
   * The GraphQL query processor.
   *
   * @var \Drupal\graphql\GraphQL\Execution\QueryProcessor
   */
  protected $queryProcessor;

  /**
   * Debug mode flag.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * Inject the query processor.
   *
   * @param \Drupal\graphql\GraphQL\Execution\QueryProcessor $queryProcessor
   *   The query processor instance.
   */
  public function setQueryProcessor(QueryProcessor $queryProcessor) {
    $this->queryProcessor = $queryProcessor;
  }

  /**
   * Set debug mode for this template.
   *
   * @param bool $debug
   *   Boolean flag for debug mode.
   */
  public function setDebug($debug) {
    $this->debug = $debug;
  }

  /**
   * {@inheritdoc}
   */
  public function display(array $context, array $blocks = array()) {
    $query = trim($this->getGraphQLQuery());

    if (!$query || !static::hasGraphQLOperations()) {
      parent::display($context, $blocks);
      return;
    }

    $arguments = [];
    foreach (static::rawGraphQLArguments() as $var) {
      if (isset($context[$var])) {
        $arguments[$var] = $context[$var] instanceof EntityInterface ? $context[$var]->id() : $context[$var];
      }
    }


    $queryResult = $this->env->getQueryProcessor()->processQuery('default:default', OperationParams::create([
      'query' => $query,
      'variables' => $arguments,
    ]));

    $build = [
      '#cache' => [
        'contexts' => $queryResult->getCacheContexts(),
        'tags' => $queryResult->getCacheTags(),
        'max-age' => $queryResult->getCacheMaxAge(),
      ],
    ];

    $this->env->getRenderer()->render($build);

    $context['graphql'] = [
      'data' => $queryResult->data,
      'errors' => $queryResult->errors,
    ];

    if ($this->env->isDebug()) {

      $attach = ['#attached' => ['library' => ['graphql_twig/debug']]];
      $this->env->getRenderer()->render($attach);

      echo '<div class="graphql-twig-debug-wrapper" data-query="' . htmlspecialchars($this->getGraphQLQuery()) . '"' . ($arguments ? ' data-variables="' . htmlspecialchars(json_encode($arguments)) . '"' : '') . '>';
      if (isset($context['graphql']['errors']) && $context['graphql']['errors']) {
        echo '<ul class="graphql-twig-errors">';
        foreach ($context['graphql']['errors'] as $error) {
          echo '<li>' . $error->message . '</li>';
        }
        echo '</ul>';
      }
    }

    parent::display($context, $blocks);

    if ($this->debug) {
      echo '</div>';
    }
  }

  /**
   * Recursively build the GraphQL query.
   *
   * Builds the templates GraphQL query by iterating through all included or
   * embedded templates recursively.
   */
  public function getGraphQLQuery() {

    $query = '';
    $includes = [];

    if ($this instanceof \Twig_Template) {
      $query = $this->getGraphQLFragment();

      $includes = array_keys($this->getGraphQLIncludes());

      // Recursively collect all included fragments.
      $includes = array_map(function ($template) {
        return $this->loadTemplate($template)->getGraphQLFragment();
      }, $includes);

      // Always add includes from parent templates.
      if ($parent = $this->getGraphQLParent()) {
        $includes += array_map(function ($template) {
          return $this->loadTemplate($template)->getGraphQLQuery();
        }, array_keys($parent->getGraphQLIncludes()));
      }
    }


    return implode("\n", [-1 => $query] + $includes);
  }

  /**
   * Get the files parent template.
   *
   * @return \Twig_Template|null
   *   The parent template or null.
   */
  protected function getGraphQLParent() {
    return static::rawGraphQLParent() ? $this->loadTemplate(static::rawGraphQLParent()) : NULL;
  }

  /**
   * Retrieve the files graphql fragment.
   *
   * @return string
   *   The GraphQL fragment.
   */
  public function getGraphQLFragment() {
    // If there is no query for this template, try to get one from the
    // parent template.
    if (!($query = static::rawGraphQLQuery()) && ($parent = $this->getGraphQLParent())) {
      $query = $parent->getGraphQLFragment();
    }
    return $query;
  }

  /**
   * Retrieve a list of all direct or indirect included templates.
   *
   * @return string[]
   *   The list of included templates.
   */
  public function getGraphQLIncludes() {
    $includes = array_flip(static::rawGraphQLIncludes());
    if ($includes) {
      foreach ($includes as $include => $key) {
        $includes += $this->loadTemplate($include)->getGraphQLIncludes();
      }
    }
    return $includes;
  }
}
