<?php

declare(strict_types=1);

namespace Drupal\custom_field_graphql\Plugin\GraphQLCompose\SchemaType;

use Drupal\custom_field_graphql\Plugin\GraphQLCompose\FieldType\CustomFieldItem;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "CustomField",
 * )
 */
class CustomFieldType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    // This assumes all fields have been bootstrapped.
    $fields = $this->gqlFieldTypeManager->getFields();

    array_walk_recursive($fields, function ($field) use (&$types) {
      if ($field instanceof CustomFieldItem) {
        $columns = $field->getFieldDefinition()->getSetting('columns');
        $subfields = [];
        foreach ($columns as $name => $column) {
          $subfields[$name] = [
            'type' => static::type($field->getSubfieldTypeSdl($name)),
            'description' => (string) $this->t('The @field value of the custom field', ['@field' => $name]),
          ];
        }
        $types[$field->getTypeSdl()] = new ObjectType([
          'name' => $field->getTypeSdl(),
          'description' => (string) $this->t('A custom field is a field of fields.'),
          'fields' => fn() => $subfields,
        ]);
      }
    });

    return array_values($types);
  }

}
