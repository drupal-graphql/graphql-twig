<?php

namespace Drupal\graphql_twig;

use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Language\Parser;
use Twig_Compiler;

/**
 * GraphQL meta information Twig node.
 *
 * A Twig node that will be attached to templates `class_end` to output the
 * collected graphql query and inheritance metadata. Not parsed directly but
 * injected by the `GraphQLNodeVisitor`.
 */
class GraphQLNode extends \Twig_Node {

  /**
   * The modules query string.
   *
   * @var string
   */
  protected $query = "";

  /**
   * The modules parent class.
   *
   * @var string
   */
  protected $parent = "";

  /**
   * The modules includes.
   *
   * @var array
   */
  protected $includes = [];

  /**
   * Boolean indicator if this fragment includes operations.
   *
   * @var bool
   */
  public $hasOperations = FALSE;

  /**
   * The list of arguments accepted by operations in this fragment.
   *
   * @var array
   */
  public $arguments = [];

  /**
   * GraphQLNode constructor.
   *
   * @param string $query
   *   The query string.
   * @param string $parent
   *   The parent template identifier.
   * @param array $includes
   *   Identifiers for any included/referenced templates.
   */
  public function __construct($query, $parent, $includes) {
    $this->query = trim($query);
    $this->parent = $parent;
    $this->includes = $includes;

    if ($this->query) {
      $document = Parser::parse($this->query);

      /** @var \GraphQL\Language\AST\OperationDefinitionNode[] $operations */
      $operations = array_filter(iterator_to_array($document->definitions->getIterator()), function (DefinitionNode $node) {
        return $node instanceof OperationDefinitionNode;
      });

      $this->hasOperations = (bool) $operations;

      $this->arguments = array_map(function (VariableDefinitionNode $node) {
        return $node->variable->name->value;
      }, array_reduce($operations, function ($carry, OperationDefinitionNode $node) {
        return array_merge($carry, iterator_to_array($node->variableDefinitions->getIterator()));
      }, []));
    }

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Twig_Compiler $compiler) {
    $compiler
      // Make the template implement the GraphQLTemplateTrait.
      ->write("\nuse \Drupal\graphql_twig\GraphQLTemplateTrait;\n")
      // Write metadata properties.
      ->write("\npublic static function hasGraphQLOperations() { return ")->repr($this->hasOperations)->write("; }\n")
      ->write("\npublic static function rawGraphQLQuery() { return ")->string($this->query)->write("; }\n")
      ->write("\npublic static function rawGraphQLParent() { return ")->string($this->parent)->write("; }\n");

    $compiler->write("\npublic static function rawGraphQLIncludes() { return [");

    foreach ($this->includes as $include) {
      $compiler->string($include)->write(",");
    }

    $compiler->write("]; }\n");

    $compiler->write("\npublic static function rawGraphQLArguments() { return [");
    foreach ($this->arguments as $argument) {
      $compiler->string($argument)->write(",");
    }
    $compiler->write("]; }\n");
  }

}
