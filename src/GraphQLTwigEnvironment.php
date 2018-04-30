<?php

namespace Drupal\graphql_twig;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\graphql\GraphQL\Execution\QueryProcessor;

/**
 * Enhanced Twig environment for GraphQL.
 *
 * Checks for GraphQL annotations in twig templates or matching `*.gql` and
 * adds them as `{% graphql %}` tags before passing them to the compiler.
 *
 * This is a convenience feature and also ensures that GraphQL-powered templates
 * don't break compatibility with Twig processors that don't have this extension
 * (e.g. patternlab).
 */
class GraphQLTwigEnvironment extends TwigEnvironment {

  /**
   * A GraphQL query processor.
   *
   * @var \Drupal\graphql\GraphQL\Execution\QueryProcessor
   */
  protected $queryProcessor;

  /**
   * Retrieve the query processor.
   *
   * @return \Drupal\graphql\GraphQL\Execution\QueryProcessor
   *   The GraphQL query processor.
   */
  public function getQueryProcessor() {
    return $this->queryProcessor;
  }

  /**
   * The renderer instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Retrieve the renderer instance.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer instance.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    string $root,
    CacheBackendInterface $cache,
    string $twig_extension_hash,
    StateInterface $state,
    \Twig_LoaderInterface $loader = NULL,
    array $options = [],
    QueryProcessor $queryProcessor = NULL,
    RendererInterface $renderer = NULL
  ) {
    $this->queryProcessor = $queryProcessor;
    $this->renderer = $renderer;
    parent::__construct(
      $root,
      $cache,
      $twig_extension_hash,
      $state,
      $loader,
      $options
    );
  }

  /**
   * Regular expression to find a GraphQL annotation in a twig comment.
   *
   * @var string
   */
  public static $GRAPHQL_ANNOTATION_REGEX = '/{#graphql\s+(?<query>.*?)\s+#\}/s';

  /**
   * {@inheritdoc}
   */
  public function compileSource($source, $name = NULL) {
    if ($source instanceof \Twig_Source) {
      // Check if there is a `*.gql` file with the same name as the template.
      $graphqlFile = $source->getPath() . '.gql';
      if (file_exists($graphqlFile)) {
        $source = new \Twig_Source(
          '{% graphql %}' . file_get_contents($graphqlFile) . '{% endgraphql %}' . $source->getCode(),
          $source->getName(),
          $source->getPath()
        );
      }
      else {
        // Else, try to find an annotation.
        $source = new \Twig_Source(
          $this->replaceAnnotation($source->getCode()),
          $source->getName(),
          $source->getPath()
        );
      }

    }
    else {
      // For inline templates, only comment based annotations are supported.
      $source = $this->replaceAnnotation($source);
    }

    // Compile the modified source.
    return parent::compileSource($source, $name);
  }

  /**
   * Replace `{#graphql ... #}` annotations with `{% graphql ... %}` tags.
   *
   * @param string $code
   *   The template code.
   *
   * @return string
   *   The template code with all annotations replaced with tags.
   */
  public function replaceAnnotation($code) {
    return preg_replace(static::$GRAPHQL_ANNOTATION_REGEX, '{% graphql %}$1{% endgraphql %}', $code);
  }

}
