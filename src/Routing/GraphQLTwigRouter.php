<?php

namespace Drupal\graphql_twig\Routing;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\graphql_twig\Controller\RouteController;
use Symfony\Component\Routing\Route;

class GraphQLTwigRouter {

  /**
   * The theme handler to collect routes from theme info files.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * GraphQLTwigRouter constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler to collect routes from theme info files.
   */
  public function __construct(ThemeHandlerInterface $themeHandler) {
    $this->themeHandler = $themeHandler;
  }

  /**
   * Generate a list of routes based on theme info files.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   A list of routes defined by themes.
   */
  public function routes() {
    $routes = [];
    foreach ($this->themeHandler->listInfo() as $info) {
      if (isset($info->info['routes'])) {
        foreach ($info->info['routes'] as $name => $route) {
          $routes['graphql_twig.dynamic.' . $name] = new Route($route['path'], [
            '_controller' => RouteController::class . ':page',
            '_title_callback' => RouteController::class . ':title',
            '_title' => isset($route['title']) ? $route['title'] : NULL,
            '_title_query' => isset($route['title_query']) ? $route['title_query'] : NULL,
            '_graphql_theme_hook' => $name,
          ], isset($route['requirements']) ? $route['requirements'] : [
            '_access' => 'TRUE',
          ]);
        }

      }
    }
    return $routes;
  }

}
