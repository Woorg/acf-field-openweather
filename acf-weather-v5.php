<?php

class acf_field_weather extends acf_field
{


    /*
    *  __construct
    *
    *  This function will setup the field type data
    *
    *  @type	function
    *  @date	5/03/2014
    *  @since	5.0.0
    *
    *  @param	n/a
    *  @return	n/a
    */

    function __construct()
    {

        /*
        *  name (string) Single word, no spaces. Underscores allowed
        */

        $this->name = 'weather';


        /*
        *  label (string) Multiple words, can include spaces, visible when selecting a field type
        */

        $this->label = __('Weather in city', 'acf-weather');


        /*
        *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
        */

        $this->category = __('Weather', 'acf-weather');


        /*
        *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
        */

        $this->defaults = array(
            'api' => '',
            'units' => '',
        );


        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('weather', 'error');
        */

        $this->l10n = array(
            'error' => __('Error! Please enter a higher value', 'acf-weather'),
        );


        // do not delete!
        parent::__construct();

    }


    /*
    *  render_field_settings()
    *
    *  Create extra settings for your field. These are visible when editing a field
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field (array) the $field being edited
    *  @return	n/a
    */

    function render_field_settings($field)
    {

        /*
        *  acf_render_field_setting
        *
        *  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
        *  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
        *
        *  More than one setting can be added by copy/paste the above code.
        *  Please note that you must also have a matching $defaults value for the field name (font_size)
        */

        acf_render_field_setting($field, array(
            'required' => true,
            'label' => __('Api Key', 'acf-weather'),
            'instructions' => __('Create openweather api', 'acf-weather') . ' <a href="https://openweathermap.org/">https://openweathermap.org/</a>',
            'type' => 'text',
            'name' => 'api'
        ));

        acf_render_field_setting($field, array(
            'required' => true,
            'label' => __('Units of measurement. standard, metric and imperial units are available.', 'acf-weather'),
            'type' => 'radio',
            'name' => 'units',
            'choices' => array(
                'standard' => __('Standard', 'acf-weather'),
                'metric' => __('Metric', 'acf-weather'),
                'imperial' => __('Imperial', 'acf-weather'),
            ),
            'layout' => 'horizontal',
        ));

        acf_render_field_setting($field, array(
            'required' => true,
            'label' => __('Cache lifetime', 'acf-weather'),
            'instructions' => __('Number of seconds before we try to fetch the weather info again', 'acf-weather'),
            'default_value' => '900',
            'min' => 0,
            'max' => 1000000,
            'type' => 'number',
            'append' => 'sec.',
            'name' => 'cache_lifetime'
        ));


    }

    /*
    *  render_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param	$field (array) the $field being rendered
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field (array) the $field being edited
    *  @return	n/a
    */

    function render_field($field)
    {
        /*
        *  Create a simple text input
        */

        ?>

        <input type="text"
               name="<?php echo esc_attr($field['name']) ?>"
               value="<?php echo esc_attr($field['value']['city']) ?>"/>

        <?php
        $api = $field['api'];
        $city = $field['value']['city'];
        $units = $field['units'];
        $cache_age = $field['cache_lifetime'];

        $weather_api_request = "http://api.openweathermap.org/data/2.5/weather?q={$city}&units={$units}&appid={$api}";
        $weather_api_response = wp_remote_get($weather_api_request);
        $weather_data = json_decode(wp_remote_retrieve_body($weather_api_response));

//        var_dump($weather_data);

    }

    /*
    *  load_value()
    *
    *  This filter is applied to the $value after it is loaded from the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value (mixed) the value found in the database
    *  @param	$post_id (mixed) the $post_id from which the value was loaded
    *  @param	$field (array) the field array holding all the field options
    *  @return	$value
    */


    function load_value($value, $post_id, $field)
    {

        $data = $value;
        $data['api_key'] = $field['api'];
        $data['units'] = $field['units'];

        $api = $field['api'];
        $city = $value['city'];
        $units = $field['units'];

        $trans_name = 'weather-'.$city;

        $weather_api_request = "http://api.openweathermap.org/data/2.5/weather?q={$city}&units={$units}&appid={$api}";
        $weather_api_response = wp_remote_get($weather_api_request);
        $weather_data = json_decode(wp_remote_retrieve_body($weather_api_response));

        $data['weather_data'] = $weather_data;

        // Запишем полученный запрос в транзитную опцию на 24 часа
		set_transient( $trans_name, $weather_data, 24 * HOUR_IN_SECONDS );

//        var_dump($weather_data);

        return $data;

    }

    /*
    *  update_value()
    *
    *  This filter is applied to the $value before it is saved in the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value (mixed) the value found in the database
    *  @param	$post_id (mixed) the $post_id from which the value was loaded
    *  @param	$field (array) the field array holding all the field options
    *  @return	$value
    */

    function update_value($value, $post_id, $field)
    {


        $data = [
            'city' => $value,
            'weather_data' => ''
        ];


        $api = $field['api'];
        $city = $field['value']['city'];
        $units = $field['units'];


        $weather_api_request = "http://api.openweathermap.org/data/2.5/weather?q={$city}&units={$units}&appid={$api}";
        $weather_api_response = wp_remote_get($weather_api_request);
        $weather_data = json_decode(wp_remote_retrieve_body($weather_api_response));

        $data['weather_data'] = $weather_data;


//        $data['raw_json'] = $weather_raw_json;

        return $data;


    }


}


// create field
new acf_field_weather();

?>
