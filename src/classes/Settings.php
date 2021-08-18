<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Settings'))
{
    class Settings 
    {
        var $settings;
        var $sections;
        var $page_slug;
        var $settings_group;
        var $lang;

        function __construct($settings, $sections){
            $this->settings = wp_parse_args($settings, array(
                'prefix' => 'wpseed_',
                'menu_page' => 'options-general.php', // https://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
                'menu_title' => __('Theme Options', 'ac'),
                'page_title' => __('Theme Options', 'ac'),
                'btn_title' => __('Update', 'ac')
            ));

            $this->sections = $sections;
            if(!$this->sections) return;

            $this->page_slug = $this->settings['prefix'] . 'page';
            $this->settings_group = $this->settings['prefix'] . 'settings_group';

            $this->define_lang();
            add_action('plugins_loaded', array($this, 'define_lang'));

            add_action('admin_menu', array($this, 'add_submenu_page'));
            add_action('admin_init', array($this, 'add_fields'));
        }

        function define_lang(){

            $this->lang = '_en';

            if(isset($_REQUEST['lang'])){
                $this->lang = '_' . $_REQUEST['lang'];
            }else if(defined('ICL_LANGUAGE_CODE')){
                $this->lang = '_' . ICL_LANGUAGE_CODE;
            }else{
                $this->lang = '_' . substr(get_locale(), 0, 2);
            }
        }

        private function get_option_full_id($id, $lang=null){
            $lang = isset($lang) ? '_' . $lang : $this->lang; 
            return $this->settings['prefix'] . $id . $lang;
        }

        private function get_section_full_id($id){
            return $this->settings['prefix'] . 'section_' . $id;
        }

        function get_option($name, $lang=null){
            $value = get_option($this->get_option_full_id($name, $lang));
            if($value == '' || $value === false){
                foreach($this->sections as $section){
                    if(isset($section['fields'][$name]['default']) && $section['fields'][$name]['default'] != ''){
                        return $section['fields'][$name]['default'];
                    }
                }
            }
            return $value;
        }

        function add_submenu_page(){
            add_submenu_page(
                $this->settings['menu_page'],
                $this->settings['page_title'],
                $this->settings['menu_title'],
                'manage_options',
                $this->page_slug,
                array($this, 'display_options_page')
            );
        }

        function display_options_page(){ ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <h1><?php echo $this->settings['page_title']; ?></h1>
                <?php do_action($this->settings['prefix'] . 'before_form'); ?>
                <form action="options.php" method="POST">
                    <?php
                    do_settings_sections($this->page_slug);
                    settings_fields($this->settings_group);
                    submit_button($this->settings['btn_title']);

                    $lang = filter_input(INPUT_GET, 'lang');
                    if(!empty($lang)){
                        echo '<input type="hidden" name="lang" value="' . $lang . '" />';
                    }
                    ?>
                </form>
                <?php do_action($this->settings['prefix'] . 'after_form'); ?>
            </div>
            <?php
        }

        function add_fields(){
            foreach($this->sections as $section_id => $section){
                add_settings_section(
                    $this->get_section_full_id($section_id),
                    $section['title'],
                    array($this, 'display_section'),
                    $this->page_slug
                );
                foreach($section['fields'] as $field_id => $field){
                    add_settings_field(
                        $this->get_option_full_id($field_id),
                        $field['title'],
                        array($this, 'display_field'),
                        $this->page_slug,
                        $this->get_section_full_id($section_id),
                        array('field_id' => $field_id, 'field_config' => $field)
                    );
                    register_setting(
                        $this->settings_group,
                        $this->get_option_full_id($field_id)
                    );
                }
            }
        }

        function display_section($section){
            if(isset($this->sections[$section['id']]['description']) && $this->sections[$section['id']]['description'] != ''){ ?>
                <p><?php echo $this->section[$section['id']]['description']; ?></p>
            <?php
            }
        }

        function display_field($args){
            $option_name = $this->get_option_full_id($args['field_id']);
            switch($args['field_config']['type']){
                case 'text': ?>
                    <input name="<?php echo $option_name; ?>" type="text" class="regular-text" value="<?php echo $this->get_option($args['field_id']); ?>" />
                    <?php break;
                case 'textarea': ?>
                    <textarea name="<?php echo $option_name; ?>" class="large-text" cols="50" rows="10"><?php echo $this->get_option($args['field_id']); ?></textarea>
                    <?php break;
                case 'select': ?>
                    <select name="<?php echo $option_name; ?>">
                        <?php foreach((array) $args['field_config']['options'] as $value => $option){ ?>
                        <option value="<?php echo $value; ?>" <?php selected($this->get_option($args['field_id']), $value); ?>><?php echo $option; ?></option>
                        <?php } ?>
                    </select>
                    <?php break;
                case 'checkbox': ?>
                    <input name="<?php echo $option_name; ?>" type="checkbox" value="1" <?php checked($this->get_option($args['field_id']), 1); ?>/>
                    <?php break;
                case 'checkbox_multiple': ?>
                    <?php foreach((array) $args['field_config']['options'] as $value => $option){ ?>
                    <p>
                        <input id="<?php echo $option_name . '-' . $value; ?>" name="<?php echo $option_name; ?>" type="checkbox" value="<?php echo $value; ?>" <?php checked(in_array($value, (array)$this->get_option($args['field_id'])), true); ?> />
                        <label for="<?php echo $option_name . '-' . $value; ?>"><?php echo $option; ?></label>
                    </p>
                    <?php } ?>
                    <?php break;
            }
            if(isset($args['field_config']['description']) && $args['field_config']['description'] != ''){ ?>
                <p class="description"><?php echo $args['field_config']['description']; ?></p>
            <?php
            }
        }
    }
}