<?php

namespace Drupal\graphql_twig\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GraphQLTwigBlockDeriver extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('theme_handler'));
  }

  public function __construct(ThemeHandlerInterface $themeHandler) {
    $this->themeHandler = $themeHandler;
  }

  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->themeHandler->listInfo() as $themeName => $info) {
      if (isset($info->info['blocks'])) {
        foreach ($info->info['blocks'] as $name => $block) {
          $this->derivatives[$name] = [
              'admin_label' => $this->t($block['label']),
              'graphql_theme_hook' => $name,
              'graphql_parameters' => $block['parameters'] ?: [],
          ] + $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }


}
