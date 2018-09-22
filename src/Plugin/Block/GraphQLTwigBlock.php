<?php

namespace Drupal\graphql_twig\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GraphQLTwigBlock
 *
 * @Block(
 *   id="graphql_twig",
 *   category=@Translation("GraphQL Twig"),
 *   deriver="\Drupal\graphql_twig\Plugin\Deriver\GraphQLTwigBlockDeriver"
 * )
 */
class GraphQLTwigBlock extends BlockBase {

  /**
   * @inheritdoc
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $arguments = [];

    if (isset($this->configuration['graphql_block'])) {
      foreach ($this->configuration['graphql_block'] as $arg) {
        $arguments[$arg['key']] = $arg['value'];
      }
    }

    foreach ($this->pluginDefinition['graphql_parameters'] as $name => $el) {
      foreach ($el as $key => $value) {
        if (in_array($key, ['title', 'description'])) {
          $form['graphql_block'][$name]['#' . $key] = $this->t($value);
        }
        else {
          $form['graphql_block'][$name]['#' . $key] = $value;
        }

        $form['graphql_block'][$name]['#default_value'] = isset($arguments[$name]) ? $arguments[$name] : '';
      }
    }

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    foreach (array_keys($this->pluginDefinition['graphql_parameters']) as $arg) {
      $values = $form_state->getValues();
      $this->configuration['graphql_block'][] = [
        'key' => $arg,
        'value' => $values['graphql_block'][$arg],
      ];
    }
  }

  /**
   * @inheritdoc
   */
  public function build() {
    $arguments = [];

    foreach ($this->configuration['graphql_block'] as $arg) {
      $arguments[$arg['key']] = $arg['value'];
    }

    return [
      '#theme' => $this->pluginDefinition['graphql_theme_hook'],
      '#graphql_arguments' => $arguments,
    ];
  }

}
