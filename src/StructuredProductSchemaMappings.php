<?php

namespace Netzstrategen\ShopStandards;

/**
 * Config-driven mapping between WooCommerce attributes and schema.org fields.
 */
class StructuredProductSchemaMappings {

  const OPTION_PAGE_SLUG = 'structured-product-schema-mappings';
  const FIELD_MAPPINGS = 'structured_product_schema_mappings';

  const SCHEMA_PROPERTIES = [
    'material',
    'color',
    'weight',
    'width',
    'height',
    'depth',
    'model',
    'pattern',
    'size',
    'additionalProperty',
  ];

  /**
   * Init module.
   */
  public static function init(): void {
    if (!function_exists('register_field_group') || !function_exists('acf_add_options_sub_page')) {
      return;
    }

    static::register_acf_options_page();
    static::register_acf_fields();
  }

  /**
   * Registers WooCommerce options page.
   */
  public static function register_acf_options_page(): void {
    acf_add_options_sub_page([
      'page_title' => __('Structured Product Schema Mappings', Plugin::L10N),
      'menu_title' => __('Product Schema Mappings', Plugin::L10N),
      'parent_slug' => 'woocommerce',
      'menu_slug' => self::OPTION_PAGE_SLUG,
    ]);
  }

  /**
   * Registers ACF fields for mapping builder.
   */
  public static function register_acf_fields(): void {
    register_field_group([
      'key' => 'group_structured_product_schema_mappings',
      'title' => __('Structured Product Schema Mappings', Plugin::L10N),
      'fields' => [[
        'key' => 'field_structured_product_schema_mappings',
        'label' => __('Mappings', Plugin::L10N),
        'name' => self::FIELD_MAPPINGS,
        'type' => 'repeater',
        'instructions' => __('Map WooCommerce product attributes to schema.org properties.', Plugin::L10N),
        'layout' => 'table',
        'button_label' => __('Add mapping', Plugin::L10N),
        'sub_fields' => [
          [
            'key' => 'field_schema_mapping_attribute_slug',
            'label' => __('Attribute', Plugin::L10N),
            'name' => 'attribute_slug',
            'type' => 'select',
            'required' => 1,
            'allow_null' => 0,
            'multiple' => 0,
            'choices' => static::getAttributeOptions(),
            'wrapper' => [
              'width' => '30',
            ],
          ],
          [
            'key' => 'field_schema_mapping_schema_property',
            'label' => __('Schema property', Plugin::L10N),
            'name' => 'schema_property',
            'type' => 'select',
            'required' => 1,
            'allow_null' => 0,
            'multiple' => 0,
            'choices' => static::getSchemaPropertyOptions(),
            'wrapper' => [
              'width' => '30',
            ],
          ],
          [
            'key' => 'field_schema_mapping_unit_override',
            'label' => __('Unit', Plugin::L10N),
            'name' => 'unit',
            'type' => 'select',
            'instructions' => __('Optional unit for quantitative values (e.g. kg, cm).', Plugin::L10N),
            'allow_null' => 1,
            'multiple' => 0,
            'choices' => static::getUnitOptions(),
            'conditional_logic' => [[
              [
                'field' => 'field_schema_mapping_schema_property',
                'operator' => '==',
                'value' => 'weight',
              ],
            ], [
              [
                'field' => 'field_schema_mapping_schema_property',
                'operator' => '==',
                'value' => 'width',
              ],
            ], [
              [
                'field' => 'field_schema_mapping_schema_property',
                'operator' => '==',
                'value' => 'height',
              ],
            ], [
              [
                'field' => 'field_schema_mapping_schema_property',
                'operator' => '==',
                'value' => 'depth',
              ],
            ], [
              [
                'field' => 'field_schema_mapping_schema_property',
                'operator' => '==',
                'value' => 'additionalProperty',
              ],
            ]],
            'wrapper' => [
              'width' => '20',
            ],
          ],
          [
            'key' => 'field_schema_mapping_property_label_override',
            'label' => __('Additional property name', Plugin::L10N),
            'name' => 'property_label',
            'type' => 'text',
            'instructions' => __('Name used for schema.org additionalProperty (PropertyValue.name).', Plugin::L10N),
            'conditional_logic' => [[
              [
                'field' => 'field_schema_mapping_schema_property',
                'operator' => '==',
                'value' => 'additionalProperty',
              ],
            ]],
            'wrapper' => [
              'width' => '20',
            ],
          ],
        ],
      ]],
      'location' => [[[
        'param' => 'options_page',
        'operator' => '==',
        'value' => self::OPTION_PAGE_SLUG,
      ]]],
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'active' => 1,
    ]);
  }

  /**
   * Returns normalized mapping rows for GraphQL/frontend.
   *
   * @return array<int, array<string, string|null>>
   *   Mappings keyed as attributeSlug, schemaProperty, unit, propertyLabel.
   */
  public static function getMappings(): array {
    if (!function_exists('get_field')) {
      return [];
    }

    $rows = get_field(self::FIELD_MAPPINGS, 'option');
    if (empty($rows) || !is_array($rows)) {
      return [];
    }

    $normalized = [];

    foreach ($rows as $row) {
      $attribute_slug = static::normalize_attribute_slug((string) ($row['attribute_slug'] ?? ''));
      $schema_property = trim((string) ($row['schema_property'] ?? ''));

      if (!$attribute_slug || !$schema_property || !in_array($schema_property, self::SCHEMA_PROPERTIES, true)) {
        continue;
      }

      $unit = trim((string) ($row['unit'] ?? ''));
      $property_label = trim((string) ($row['property_label'] ?? ''));

      // Last row for an attribute wins.
      $normalized[$attribute_slug] = [
        'attributeSlug' => $attribute_slug,
        'schemaProperty' => $schema_property,
        'unit' => $unit !== '' ? sanitize_text_field($unit) : null,
        'propertyLabel' => $property_label !== '' ? sanitize_text_field($property_label) : null,
      ];
    }

    return array_values($normalized);
  }

  /**
   * Creates select options from registered WooCommerce attributes.
   */
  protected static function getAttributeOptions(): array {
    $options = [
      '' => __('Select attribute', Plugin::L10N),
    ];
    foreach (WooCommerce::getAvailableAttributes() as $slug => $label) {
      $taxonomy = 'pa_' . $slug;
      $options[$taxonomy] = sprintf('%s (%s)', $label, $taxonomy);
    }
    return $options;
  }

  /**
   * Creates select options from supported schema properties.
   */
  protected static function getSchemaPropertyOptions(): array {
    return [
      'material' => 'material',
      'color' => 'color',
      'weight' => 'weight',
      'width' => 'width',
      'height' => 'height',
      'depth' => 'depth',
      'model' => 'model',
      'pattern' => 'pattern',
      'size' => 'size',
      'additionalProperty' => 'additionalProperty',
    ];
  }

  /**
   * Creates select options for common units used in Europe.
   */
  protected static function getUnitOptions(): array {
    return [
      '' => __('No override', Plugin::L10N),
      'mm' => 'mm',
      'cm' => 'cm',
      'm' => 'm',
      'km' => 'km',
      'g' => 'g',
      'kg' => 'kg',
      'mg' => 'mg',
      't' => 't',
      'ml' => 'ml',
      'cl' => 'cl',
      'l' => 'l',
      'm2' => 'm²',
      'm3' => 'm³',
      'w' => 'W',
      'kw' => 'kW',
      'v' => 'V',
      'a' => 'A',
      'hz' => 'Hz',
      'c' => '°C',
      'pa' => 'Pa',
      'bar' => 'bar',
    ];
  }

  /**
   * Normalizes raw input to canonical `pa_<slug>` format.
   */
  protected static function normalize_attribute_slug(string $slug): ?string {
    $slug = strtolower(trim($slug));
    if ($slug === '') {
      return null;
    }

    if (strpos($slug, 'attribute_pa_') === 0) {
      $slug = substr($slug, strlen('attribute_pa_'));
    }
    if (strpos($slug, 'pa_') === 0) {
      $slug = substr($slug, strlen('pa_'));
    }

    $slug = sanitize_key($slug);
    if ($slug === '') {
      return null;
    }

    return 'pa_' . $slug;
  }

}
