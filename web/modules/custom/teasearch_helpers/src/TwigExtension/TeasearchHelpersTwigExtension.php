<?php

namespace Drupal\teasearch_helpers\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension per Teasearch Helpers.
 */
class TeasearchHelpersTwigExtension extends AbstractExtension
{

  /**
   * {@inheritdoc}
   */
  public function getFilters()
  {
    return [
      new TwigFilter('translate_content_type', [$this, 'translateContentType']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions()
  {
    return [
      new TwigFunction('get_content_type_name', [$this, 'getContentTypeName']),
    ];
  }

  /**
   * Traduce un machine name di content type.
   *
   * @param string $machine_name
   *   Il machine name del content type.
   * @param string|null $language
   *   La lingua desiderata (opzionale).
   *
   * @return string
   *   Il nome tradotto.
   */
  public function translateContentType($machine_name, $language = NULL)
  {
    if (function_exists('getContentTypeNameByMachineName')) {
      return getContentTypeNameByMachineName($machine_name, $language);
    }
    return $machine_name;
  }

  /**
   * Ottiene il nome tradotto di un content type.
   *
   * @param string $machine_name
   *   Il machine name del content type.
   * @param string|null $language
   *   La lingua desiderata (opzionale).
   *
   * @return string
   *   Il nome tradotto.
   */
  public function getContentTypeName($machine_name, $language = NULL)
  {
    if (function_exists('getContentTypeNameByMachineName')) {
      return getContentTypeNameByMachineName($machine_name, $language);
    }
    return $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return 'teasearch_helpers.twig_extension';
  }

}