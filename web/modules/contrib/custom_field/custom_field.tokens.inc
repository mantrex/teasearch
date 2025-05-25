<?php

/**
 * @file
 * Token callbacks for the custom_field module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType;

/**
 * Implements hook_token_info().
 */
function custom_field_token_info(): array {
  $info = [];
  $module_handler = \Drupal::moduleHandler();
  if (!$module_handler->moduleExists('token')) {
    return $info;
  }
  $type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();
  $field_manager = \Drupal::service('entity_field.manager');
  $token_mapper = \Drupal::service('token.entity_mapper');
  /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_manager */
  $custom_field_manager = \Drupal::service('plugin.manager.custom_field_type');

  foreach ($entity_types as $entity_type_id => $entity_type) {
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }

    // Make sure a token type exists for this entity.
    $token_type = $token_mapper->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type)) {
      continue;
    }

    $field_definitions = $field_manager->getFieldStorageDefinitions($entity_type_id);
    foreach ($field_definitions as $field_name => $field) {
      /** @var \Drupal\field\FieldStorageConfigInterface $field */
      // We only care about 'custom' field types.
      if ($field->getType() !== 'custom') {
        continue;
      }

      // Generate a description for the token.
      $labels = _custom_field_token_field_label($entity_type_id, $field_name);
      $label = array_shift($labels);
      $params['@type'] = $type_info[$field->getType()]['label'];
      if (!empty($labels)) {
        $params['%labels'] = implode(', ', $labels);
        $description = t('@type field. Also known as %labels.', $params);
      }
      else {
        $description = t('@type field.', $params);
      }

      $cardinality = $field->getCardinality();
      $cardinality = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $cardinality > 3) ? 3 : $cardinality;
      $field_token_name = $token_type . '-' . $field_name;
      $info['tokens'][$token_type][$field_name] = [
        'name' => Html::escape($label),
        'description' => $description,
        'module' => 'custom_field',
        // For multivalue fields the field token is a list type.
        'type' => $cardinality > 1 ? "list<$field_token_name>" : $field_token_name,
      ];
      // Field token type.
      $info['types'][$field_token_name] = [
        'name' => Html::escape($label),
        'description' => t('@label tokens.', ['@label' => $field->getLabel()]),
        'needs-data' => $field_token_name,
        'module' => 'custom_field',
        'nested' => TRUE,
      ];
      // Account for multi-value custom fields.
      if ($cardinality > 1) {
        // Field list token type.
        $info['types']["list<$field_token_name>"] = [
          'name' => t('List of @type values', ['@type' => Html::escape($label)]),
          'description' => t('Tokens for lists of @type values.', ['@type' => Html::escape($label)]),
          'needs-data' => "list<$field_token_name>",
          'module' => 'custom_field',
          'nested' => TRUE,
        ];
        // Show a different token for each field delta.
        for ($delta = 0; $delta < $cardinality; $delta++) {
          $info['tokens']["list<$field_token_name>"][$delta] = [
            'name' => t('@type type with delta @delta', ['@type' => Html::escape($label), '@delta' => $delta]),
            'module' => 'custom_field',
            'type' => $field_token_name,
          ];
        }
      }

      // Add subfield tokens.
      $settings = $field->getSettings();
      $custom_fields = $custom_field_manager->getCustomFieldItems($settings);
      foreach ($custom_fields as $name => $custom_field) {
        $type = $custom_field->getDataType();
        $subfield_token_name = $field_token_name . '-' . $name;
        // Subfield token type.
        $info['types'][$subfield_token_name] = [
          'name' => t('@label', ['@label' => $name]),
          'needs-data' => $subfield_token_name,
          'module' => 'custom_field',
          'nested' => TRUE,
        ];
        // Define tokens for subfield values.
        $info['tokens'][$field_token_name][$name] = [
          'name' => $name,
          'description' => t('The value of the @label subfield.', ['@label' => $name]),
          'type' => $subfield_token_name,
        ];
        // The field label token.
        $info['tokens'][$subfield_token_name]['field_label'] = [
          'name' => t('Field label'),
          'description' => t('The field label of the @label subfield.', ['@label' => $name]),
        ];
        // Handle specific data types.
        if ($type === 'entity_reference' && $target_type = $custom_field->getTargetType()) {
          $entity_token_type = $token_mapper->getTokenTypeForEntityType($target_type);
          $info['tokens'][$subfield_token_name]['entity'] = [
            'name' => t('Referenced entity'),
            'description' => t('The referenced entity.'),
            'type' => $entity_token_type,
            'nested' => TRUE,
          ];
        }
        if ($type === 'image') {
          $info['tokens'][$subfield_token_name]['alt'] = [
            'name' => t('Alternative text'),
            'description' => t("Alternative image text, for the image's 'alt' attribute."),
          ];
          $info['tokens'][$subfield_token_name]['title'] = [
            'name' => t('Title'),
            'description' => t("Image title text, for the image's 'title' attribute."),
          ];
          $info['tokens'][$subfield_token_name]['height'] = [
            'name' => t('Height'),
            'description' => t('The height of the image in pixels.'),
          ];
          $info['tokens'][$subfield_token_name]['width'] = [
            'name' => t('Width'),
            'description' => t('The width of the image in pixels.'),
          ];
          $info['tokens'][$subfield_token_name]['entity'] = [
            'name' => t('File'),
            'description' => t('The referenced entity'),
            'type' => 'file',
            'nested' => TRUE,
          ];
          // Provide image_with_image_style tokens for image fields.
          if (\Drupal::moduleHandler()->moduleExists('image')) {
            $image_styles = image_style_options(FALSE);
            foreach ($image_styles as $style => $description) {
              $info['tokens'][$subfield_token_name][$style] = [
                'name' => $description,
                'description' => t('Represents the image in the given image style.'),
                'type' => 'image_with_image_style',
              ];
            }
          }
        }
        if ($type === 'file') {
          $info['tokens'][$subfield_token_name]['entity'] = [
            'name' => t('File'),
            'description' => t('The referenced entity'),
            'type' => 'file',
            'nested' => TRUE,
          ];
        }
        if ($type === 'datetime') {
          $info['tokens'][$subfield_token_name]['formatted'] = [
            'name' => t('Formatted date'),
            'description' => t('The formatted datetime value.'),
            'type' => 'date',
            'nested' => TRUE,
          ];
        }
        else {
          $info['tokens'][$subfield_token_name]['value'] = [
            'name' => t('Value'),
            'description' => t('The raw value of the subfield.'),
          ];
        }
        // Add a list label token for fields that allow it.
        if (in_array($type, ['string', 'integer', 'float'])) {
          $info['tokens'][$subfield_token_name]['label'] = [
            'name' => t('Label'),
            'description' => t('The label from widget settings allowed values (if applicable).'),
          ];
        }
      }

    }
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function custom_field_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];
  $module_handler = \Drupal::moduleHandler();
  if (!$module_handler->moduleExists('token')) {
    return $replacements;
  }
  $langcode = $options['langcode'] ?? NULL;

  // Handle entity tokens.
  if ($type == 'entity' && !empty($data['entity_type']) && !empty($data['entity']) && !empty($data['token_type'])) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $data['entity'];
    if (!($entity instanceof ContentEntityInterface)) {
      return $replacements;
    }

    if (!isset($options['langcode'])) {
      // Set the active language in $options, so that it is passed along.
      $langcode = $options['langcode'] = $entity->language()->getId();
    }

    // Obtain the entity with the correct language.
    $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);

    foreach ($tokens as $name => $original) {
      $delta = 0;
      // For the [entity:field_name] token.
      if (!str_contains($name, ':')) {
        $field_name = $name;
        if (is_array($original)) {
          $property_name = key($original);
        }
      }
      // For [entity:field_name:0], [entity:field_name:0:value] and
      // [entity:field_name:value] tokens.
      else {
        $parts = explode(':', $name);
        $field_name = $parts[0];
        $delta = $parts[1];
        $property_name = $parts[2] ?? NULL;
        if (!is_numeric($delta)) {
          $property_name = $delta;
          unset($delta);
        }
      }

      $token_module_provider = \Drupal::service('token.module_provider');
      if ($token_module_provider->getTokenModule($data['token_type'], $field_name) != 'custom_field') {
        continue;
      }

      // Skip tokens not for this field.
      if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
        continue;
      }

      $display_options = 'token';
      // Handle [entity:field_name] and [entity:field_name:0] tokens.
      if ($field_name === $name && !isset($property_name) || (isset($delta) && !isset($property_name))) {
        $view_display = _custom_field_get_token_view_display($entity);
        if (!$view_display) {
          // We don't have the token view display and should fall back on
          // default formatters. If the field has specified a specific formatter
          // to be used by default with tokens, use that, otherwise use the
          // default formatter.
          /** @var \Drupal\Core\Field\FieldTypePluginManager $field_type_manager */
          $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
          $field_type_definition = $field_type_manager->getDefinition($entity->getFieldDefinition($field_name)->getType());
          if (empty($field_type_definition['default_token_formatter']) && empty($field_type_definition['default_formatter'])) {
            continue;
          }
          $display_options = [
            'type' => !empty($field_type_definition['default_token_formatter']) ? $field_type_definition['default_token_formatter'] : $field_type_definition['default_formatter'],
            'label' => 'hidden',
          ];
        }

        // Render only one delta.
        if (isset($delta)) {
          if ($field_delta = $entity->{$field_name}[$delta]) {
            $field_output = $field_delta->view($display_options);
          }
          // If no such delta exists, let's not replace the token.
          else {
            continue;
          }
        }
        // Render the whole field (with all deltas).
        else {
          $field_output = $entity->$field_name->view($display_options);
          // If we are displaying all field items we need this #pre_render
          // callback.
          $field_output['#pre_render'][] = '\Drupal\token\TokenFieldRender::preRender';
        }
        $field_output['#token_options'] = $options;
        $replacements[$original] = \Drupal::service('renderer')->renderInIsolation($field_output);
      }
      // Handle [entity:field_name:value] and [entity:field_name:0:value]
      // tokens.
      elseif ($field_tokens = \Drupal::token()->findWithPrefix($tokens, $field_name)) {
        // With multiple nested tokens for the same field name, this might
        // match the same field multiple times. Filter out those that have
        // already been replaced.
        $field_tokens = array_filter($field_tokens, function ($token) use ($replacements) {
          return !isset($replacements[$token]);
        });

        if ($field_tokens) {
          // Retrieve field items and handle deltas.
          $field_items = $entity->get($field_name);
          /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item */
          $item = isset($delta) ? ($field_items[$delta] ?? NULL) : $field_items->first();
          if (!$item) {
            continue;
          }

          $nested_array = [];
          // Process the tokens into a structured array by delta.
          foreach ($field_tokens as $key => $value) {
            $parts = explode(':', $key);
            $current = &$nested_array;
            foreach ($parts as $part) {
              // Ensure the current level is an array before assigning deeper
              // levels.
              if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
              }
              $current = &$current[$part];
            }
            // Assign the value at the deepest level.
            $current = $value;
            // Break the reference.
            unset($current);
          }

          // Generate the replacements.
          foreach ($nested_array as $property => $properties) {
            if (is_numeric($property)) {
              $delta = (int) $property;
              $item = $field_items[$delta] ?? NULL;
              if (!$item) {
                continue;
              }

              // Iterate through the nested properties.
              foreach ($properties as $nested_property => $nested_values) {
                _custom_field_process_property(
                  $nested_property,
                  $nested_values,
                  $item,
                  $langcode,
                  $options,
                  $bubbleable_metadata,
                  $replacements
                );
              }
            }
            else {
              // Handle the top-level property directly.
              _custom_field_process_property(
                $property,
                $properties,
                $item,
                $langcode,
                $options,
                $bubbleable_metadata,
                $replacements
              );
            }
          }
        }
      }
    }
    // Remove the cloned object from memory.
    unset($entity);
  }

  // Return the result so that we can now use the token.
  return $replacements;
}

/**
 * Helper function to get computed property values.
 *
 * @param \Drupal\Core\Field\FieldItemInterface $item
 *   The field item.
 * @param string $property_name
 *   The main property name.
 * @param string $sub_property_name
 *   The appended string that builds the computed property.
 *
 * @return mixed|null
 *   The value or NULL if the property doesn't exist.
 */
function _custom_field_get_computed_value(FieldItemInterface $item, string $property_name, string $sub_property_name): mixed {
  $property_string = "{$property_name}__{$sub_property_name}";
  return $item->{$property_string} ?? NULL;
}

/**
 * Finds the value corresponding to a specific key in a structured array.
 *
 * @param array<string|int|float, mixed> $array
 *   The array to search in.
 * @param mixed $value
 *   The key to search for. Can be a string, int, or float.
 *
 * @return mixed
 *   The corresponding value if found, or the $value if not found.
 */
function _custom_field_find_value_by_key(array $array, mixed $value): mixed {
  foreach ($array as $item) {
    if (isset($item['key']) && $item['key'] === $value) {
      return $item['value'] ?? $value;
    }
  }

  return $value;
}

/**
 * Processes a property and applies replacements.
 *
 * @param string $property
 *   The property name (e.g., "string", "image").
 * @param mixed $properties
 *   The property values or nested properties.
 * @param \Drupal\Core\Field\FieldItemInterface $item
 *   The field item for the current delta.
 * @param string $langcode
 *   The language code.
 * @param array<string, mixed> $options
 *   The array of tokens options.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   The cache metadata.
 * @param array &$replacements
 *   The array to store token replacements.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function _custom_field_process_property(string $property, mixed $properties, FieldItemInterface $item, string $langcode, array $options, BubbleableMetadata $bubbleable_metadata, array &$replacements): void {
  $module_handler = \Drupal::moduleHandler();
  if (!$module_handler->moduleExists('token')) {
    return;
  }
  $token_mapper = \Drupal::service('token.entity_mapper');
  $settings = $item->getFieldDefinition()->getSettings();
  /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_manager */
  $custom_field_manager = \Drupal::service('plugin.manager.custom_field_type');
  $custom_fields = $custom_field_manager->getCustomFieldItems($settings);
  if ($custom_item = $custom_fields[$property] ?? NULL) {
    $data_type = $custom_item->getDataType();
    $raw_value = $item->{$property};
    $referenced_entity = NULL;
    $is_reference = in_array($data_type, [
      'entity_reference',
      'image',
      'file',
    ]);
    if ($is_reference && $referenced_entity = $item->{$property . '__entity'}) {
      $referenced_entity = \Drupal::service('entity.repository')
        ->getTranslationFromContext($referenced_entity, $langcode);
    }
    if (is_array($properties)) {
      foreach ($properties as $sub_property => $property_value) {
        if ($sub_property === 'value') {
          $replacements[$property_value] = $raw_value;
        }
        elseif ($sub_property === 'field_label') {
          $replacements[$property_value] = $custom_item->getLabel();
        }
        elseif ($sub_property === 'label') {
          $allowed_values = $custom_item->getWidgetSetting('settings')['allowed_values'] ?? [];
          $replacements[$property_value] = _custom_field_find_value_by_key($allowed_values, $raw_value);
        }
        elseif ($sub_property === 'entity' && $referenced_entity) {
          $token_type = $token_mapper->getTokenTypeForEntityType($referenced_entity->getEntityTypeId(), TRUE);
          if (is_array($property_value)) {
            $term_properties = [];
            // @todo Figure out what to do with field values not replaced here.
            $field_properties = [];
            foreach ($property_value as $property_value_key => $property_value_value) {
              if (!is_array($property_value_value)) {
                $term_properties[$property_value_key] = $property_value_value;
              }
              else {
                $field_properties[$property_value_key] = $property_value_value;
              }
            }
            if (!empty($term_properties)) {
              $replacements += \Drupal::token()->generate($token_type, $term_properties, [$token_type => $referenced_entity], $options, $bubbleable_metadata);
            }
          }
          else {
            $replacements[$property_value] = $referenced_entity->label();
          }
        }
        // Image replacements.
        elseif ($data_type === 'image') {
          $image_style_storage = \Drupal::entityTypeManager()->getStorage('image_style');
          if (in_array($sub_property, ['alt', 'title', 'width', 'height'])) {
            $replacement_value = _custom_field_get_computed_value($item, $property, $sub_property);
            $replacements[$property_value] = $replacement_value;
          }
          // Provide image_with_image_style tokens for image fields.
          elseif (\Drupal::moduleHandler()->moduleExists('image') && ($style = $image_style_storage->load($sub_property))) {
            /** @var \Drupal\image\Entity\ImageStyle $style */
            $original_uri = $referenced_entity->getFileUri();
            if (is_array($property_value)) {
              $image_width = $item->{$property . '__width'} ?? NULL;
              $image_height = $item->{$property . '__height'} ?? NULL;
              foreach ($property_value as $image_property => $image_value) {
                // Only generate the image derivative if needed.
                if ($image_property === 'width' || $image_property === 'height') {
                  $dimensions = [
                    'width' => $image_width,
                    'height' => $image_height,
                  ];
                  $style->transformDimensions($dimensions, $original_uri);
                  $replacements[$image_value] = $dimensions[$image_property];
                }
                elseif ($image_property === 'uri') {
                  $replacements[$image_value] = $style->buildUri($original_uri);
                }
                elseif ($image_property === 'url') {
                  // Encloses the URL in markup object to prevent HTML escaping.
                  $replacements[$image_value] = Markup::create($style->buildUrl($original_uri));
                }
                else {
                  // Generate the image derivative, if it doesn't already exist.
                  $derivative_uri = $style->buildUri($original_uri);
                  $derivative_exists = TRUE;
                  if (!file_exists($derivative_uri)) {
                    $derivative_exists = $style->createDerivative($original_uri, $derivative_uri);
                  }
                  if ($derivative_exists) {
                    $image = \Drupal::service('image.factory')->get($derivative_uri);
                    // Provide the replacement.
                    switch ($image_property) {
                      case 'mimetype':
                        $replacements[$image_value] = $image->getMimeType();
                        break;

                      case 'filesize':
                        $replacements[$image_value] = $image->getFileSize();
                        break;
                    }
                  }
                }
              }
            }
            else {
              // Encloses the URL in markup object to prevent HTML escaping.
              $replacements[$property_value] = Markup::create($style->buildUrl($original_uri));
            }
          }
        }
        // Datetime replacements.
        elseif ($data_type === 'datetime' && $sub_property === 'formatted') {
          if (is_array($property_value)) {
            assert($custom_item instanceof DateTimeType);
            $timestamp = $custom_item->getTimestamp($item);
            $replacements += \Drupal::token()->generate('date', $property_value, ['date' => $timestamp], $options, $bubbleable_metadata);
          }
          else {
            $replacements[$property_value] = $raw_value;
          }
        }
      }
    }
    // Fallback to reference label.
    elseif ($referenced_entity) {
      $replacements[$properties] = $referenced_entity->label();
    }
    // Fallback to raw value for everything else.
    else {
      $replacements[$properties] = $raw_value;
    }
  }
}

/**
 * Returns the token view display for the given entity if enabled.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity.
 *
 * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
 *   The view display or null.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function _custom_field_get_token_view_display(EntityInterface $entity): ?EntityViewDisplayInterface {
  $view_mode_name = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.token';
  /** @var  \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $view_display */
  $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load($view_mode_name);
  return ($view_display && $view_display->status()) ? $view_display : NULL;
}

/**
 * Returns the label of a certain field.
 *
 * Therefore, it looks up in all bundles to find the most used instance.
 *
 * Based on views_entity_field_label().
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param string $field_name
 *   The name of the field.
 *
 * @return array<string|int, mixed>
 *   An array containing the most used label(s) for the field, or an array with
 *   the field name if no label is found.
 *
 * @todo Re-sync this method with views_entity_field_label().
 *
 * @see views_entity_field_label()
 */
function _custom_field_token_field_label(string $entity_type_id, string $field_name): array {
  $labels = [];
  // Count the amount of instances per label per field.
  foreach (array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id)) as $bundle) {
    $bundle_instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
    if (isset($bundle_instances[$field_name])) {
      $instance = $bundle_instances[$field_name];
      $label = (string) $instance->getLabel();
      $labels[$label] = isset($labels[$label]) ? ++$labels[$label] : 1;
    }
  }

  if (empty($labels)) {
    return [$field_name];
  }

  // Sort the field labels by it most used label and return the labels.
  arsort($labels);
  return array_keys($labels);
}
