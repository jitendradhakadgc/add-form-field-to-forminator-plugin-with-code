<?
/* - - - - - - - - - - - -
    ! Remember to insert form ID in both filters
- - - - - - - - - - - - */
add_filter('forminator_cform_render_fields', function($wrappers, $model_id) {
    $json_file_path = plugin_dir_path(__FILE__) . 'airports.json';

    //Read JSON file contents
    $json_data = file_get_contents($json_file_path);
// 
    //Decode JSON data into an associative array
    $data = json_decode($json_data, true);
 /*    $data =   $data = array(
        "00AK" => array(
            "icao" => "00AK",
            "iata" => "",
            "name" => "Lowell Field",
            "city" => "Anchor Point",
            "state" => "Alaska",
            "country" => "US",
            "elevation" => 450,
            "lat" => 59.94919968,
            "lon" => -151.695999146,
            "tz" => "America/Anchorage"
        ),
       ); */

    // Check if the JSON data is valid
    if ($data === null) {
        echo 'Error decoding JSON data.';
        return $wrappers; // Return original wrappers if JSON decoding fails
    }

    /* - - - - - - - - - - - -
        ! Change 748 to your form ID
    - - - - - - - - - - - - */
    if ($model_id != 748) {
        return $wrappers;
    }

    /* - - - - - - - - - - - -
        ! Update the field data
    - - - - - - - - - - - - */
    $select_fields_data = array(
        'select-2' => 'Label 1',
        'select-3' => 'Label 1',
        'select-4' => 'Label 1',
        'select-5' => 'Label 1',
    );

    foreach ($wrappers as $wrapper_key => $wrapper) {
        if (!isset($wrapper['fields'])) {
            continue;
        }

        foreach ($wrapper['fields'] as $field_key => $field) {
            if (
                isset($select_fields_data[$field['element_id']]) &&
                !empty($select_fields_data[$field['element_id']])
            ) {
                // Generate options from JSON data
                $new_options = [];
                foreach ($data as $key => $location) {
                    $new_options[] = array(
                        'label' => esc_html($location['name']) . ' (' . esc_html($key) . ')',
                        'value' => esc_attr($key),
                        'limit' => '-1', // Modify as needed
                        'key'   => forminator_unique_key(), // Assuming this generates a unique key
                    );
                }

                $opt_data = array(
                    'options' => $new_options,
                );

                $select_field = Forminator_API::get_form_field($model_id, $field['element_id'], true);
                if ($select_field) {
                    Forminator_API::update_form_field($model_id, $field['element_id'], $opt_data);
                    $wrappers[$wrapper_key]['fields'][$field_key]['options'] = $new_options;
                }
            }
        }
    }

    return $wrappers;
}, 10, 2);

add_filter('forminator_replace_form_data', function($content, $data, $fields) {

    /* - - - - - - - - - - - -
        ! Change 748 to your form ID
    - - - - - - - - - - - - */
    if ($data['form_id'] != 748) {
        return $content;
    }

    if (!empty($content)) {
        return $content;
    }

    $form_fields = Forminator_API::get_form_fields($data['form_id']);
    foreach ($data as $key => $value) {
        if (strpos($key, 'select') !== false) {
            $field_value = isset($data[$key]) ? $data[$key] : null;

            if (!is_null($field_value)) {
                $fields_slugs = wp_list_pluck($form_fields, 'slug');
                $field_key = array_search($key, $fields_slugs, true);
                $field_options = false !== $field_key && !empty($form_fields[$field_key]->raw['options'])
                    ? wp_list_pluck($form_fields[$field_key]->options, 'label', 'value')
                    : array();

                if (!isset($field_options[$field_value]) && isset($_POST[$key])) {
                    return sanitize_text_field($_POST[$key]);
                }
            }
        }
    }

    return $content;
}, 10, 3);

// end code forminator