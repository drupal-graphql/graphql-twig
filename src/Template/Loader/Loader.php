<?php

namespace Drupal\graphql_twig\Template\Loader;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Filesystem loader that will search for fractal component shortnames.
 */
class Loader extends \Twig_Loader_Filesystem {

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Static component lookup cache.
   *
   * @var array
   */
  protected $components = null;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * @var array
   */
  protected $twigConfig;

  public function __construct(
    ThemeManagerInterface $themeManager,
    array $twigConfig,
    CacheBackendInterface $cacheBackend,
    $paths = [],
    ?string $rootPath = NULL
  ) {
    parent::__construct($paths, $rootPath);
    $this->cacheBackend = $cacheBackend;
    $this->twigConfig = $twigConfig;
    $this->themeManager = $themeManager;
  }

  /**
   * List all components found within a specific path.
   *
   * @param string $path
   *   The directory to scan for
   *
   * @return string[]
   *   Map of component filenames keyed by component handle.
   */
  protected function listComponents($path) {
    if ($this->twigConfig['cache'] && $cache = $this->cacheBackend->get($path)) {
      return $cache->data;
    }

    foreach (file_scan_directory($path, '/.*\.twig/') as $file) {
      $this->components[$file->name] = $file->uri;
    }

    if ($this->twigConfig['cache']) {
      $this->cacheBackend->set($path, $this->components);
    }

    return $this->components;

  }

  /**
   * {@inheritdoc}
   */
  protected function findTemplate($name) {
    if (is_null($this->components)) {
      // Scan the directory for any twig files and register them.
      // TODO: inherit components from base theme
      // TODO: configurable components directory
      $this->components = $this->listComponents($this->themeManager->getActiveTheme()->getPath() . '/components');
    }

    if ($name[0] === '#') {
      $component = substr($name, 1);
      if (array_key_exists($component, $this->components)) {
        return $this->components[$component];
      }
    }

    return FALSE;
  }

}
