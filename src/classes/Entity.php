<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Entity'))
{
    class Entity 
    {
        protected $id;
        protected $post_type;
        protected $permalink;

        protected $prop_types;
        protected $props_config;

        protected $data;
        protected $meta;
        protected $terms;

        protected $attachments;
        protected $attachments_insert;
        
        /*
        --------------------------------------------------
        Construct the Post object

        @param object|int $post WP_Post instance or post ID.
        @param array $props_config['key'] = [
            'type' => 'data' | 'meta' | 'term' | 'attachment'
            'label' => 'Field Label' (defaults to $key),
            'options' => [
                'option1' => 'Option Label 1',
                'option2' => 'Option Label 2'
            ],
            'required' => false | true
        ]

        @return void
        --------------------------------------------------
        */
        public function __construct($props_config=[])
        {
            if(!isset($this->prop_types))
            {
                $this->set_prop_types(['data', 'meta']);
            }

            $this->set_props_config($props_config);
        }
        
        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        
        protected function set_props_config($props_config)
        {
            $this->props_config = [];
            
            foreach((array)$props_config as $key => $prop_config)
            {
                $this->props_config[$key] = wp_parse_args($prop_config, [
                    'sys_key' => $key,
                    'type' => 'data',
                    'label' => $key,
                    'required' => false
                ]);
            }
        }

        protected function set_prop_types($prop_types)
        {
            $this->prop_types = $prop_types;
        }

        public function set_id($id)
        {
            $this->id = $id;
            $this->set_data('ID', $id);
        }

        /*
        --------------------------------------------------
        Set meta type properties

        @param string $key Must be specified in $this->props_config;
        @param mixed $value

        @return void
        --------------------------------------------------
        */
        public function set_meta($key, $value, $single=true)
        {
            if(!in_array('meta', $this->prop_types))
            {
                return;
            }

            $prop_config = $this->get_props_config($key);

            // if(!(isset($prop_config['type']) && in_array($prop_config['type'], ['meta', 'attachment'])))
            // {
            //     return;
            // }

            if(isset($prop_config['options']) && !isset($prop_config['options'][$value]))
            {
                return;
            }

            if(!isset($this->meta[$key]))
            {
                $this->meta[$key] = [];
            }

            if($single)
            {
                $this->meta[$key] = [$value];
            }
            else{
                $this->meta[$key][] = $value;
            }
        }

        /*
        --------------------------------------------------
        Set data type properties. 
        Data properties map to WP_Post object properties;

        @param string $key as in WP_Post object
        @param mixed $value

        @return void
        --------------------------------------------------
        */
        public function set_data($key, $value)
        {
            if(!in_array('data', $this->prop_types)) return;

            // Should be implemented by the child class
        }

        /*
        --------------------------------------------------
        Set terms

        @param string $taxonomy
        @param array $terms Array of terms ids

        @return void
        --------------------------------------------------
        */
        public function set_terms($taxonomy, $terms)
        {
            if(!in_array('term', $this->prop_types))
            {
                return;
            }

            if(!is_array($terms))
            {
                $terms = [$terms];
            }

            if(!isset($this->terms[$taxonomy]))
            {
                $this->terms[$taxonomy] = [];
            }

            if(!empty($terms))
            {
                foreach($terms as $term)
                {
                    if(is_a($term, 'WP_Term'))
                    {
                        $this->terms[$taxonomy][] = $term->term_id;
                    }
                    else{
                        $this->terms[$taxonomy][] = (int)$term;
                    }
                }
            }
        }

        /*
        --------------------------------------------------
        Set $this->attachments;

        @param string $key
        @param array $attachments Array of attachments ids or Attachment instances

        @return void
        --------------------------------------------------
        */
        public function set_attachments($key, $attachments, $set_meta=true)
        {
            if(!in_array('attachment', $this->prop_types))
            {
                return;
            }

            if(!is_array($attachments))
            {
                $attachments = [$attachments];
            }

            $key_attachment_ids = [];

            if(!empty($attachments))
            {
                $this->attachments_insert[$key] = [];

                foreach($attachments as $attachment)
                {
                    if(is_array($attachment) && isset($attachment['name']))
                    {
                        $this->attachments_insert[$key][] = new Attachment(0, [], $this->get_id(), $attachment);
                    }
                    elseif(is_a($attachment, '\WPSEED\Attachment'))
                    {
                        $key_attachment_ids[] = $attachment->get_id();
                    }
                    else
                    {
                        $key_attachment_ids[] = (int)$attachment;
                    }
                }

                if(!empty($key_attachment_ids))
                {
                    $this->attachments = array_unique(array_merge($this->attachments, $key_attachment_ids));
                }
            }

            if($set_meta)
            {
                $this->set_meta($key, $key_attachment_ids);
            }
        }

        /*
        --------------------------------------------------
        Common method to set data, meta, term and attachment type properties

        @param string $key
        @param mixed $value

        @return void
        --------------------------------------------------
        */
        public function set_prop($key, $value)
        {
            $prop_config = $this->get_props_config($key);
            $type = isset($prop_config['type']) ? $prop_config['type'] : 'data';
            $sys_key = isset($prop_config['sys_key']) ? $prop_config['sys_key'] : $key;

            switch($type)
            {
                case 'data':
                    $this->set_data($sys_key, $value);
                    break;
                case 'meta':
                    $this->set_meta($sys_key, $value);
                    break;
                case 'taxonomy':
                    $this->set_terms($sys_key, $value);
                    break;
                case 'attachment':
                    $this->set_attachments($sys_key, $value);
                    break;
            }
        }

        /*
        --------------------------------------------------
        Common method to set in bulk data, meta, term and attachment type properties

        @param array $props Array of key-value pairs

        @return void
        --------------------------------------------------
        */
        public function set_props($props)
        {
            foreach((array)$props as $key => $prop)
            {
                $this->set_prop($key, $prop);
            }
        }

        /*
        --------------------------------------------------
        Check if property is not empty

        @param string $key

        @return bool
        --------------------------------------------------
        */
        public function has_prop($key)
        {
            $prop = $this->get_prop($key);
            return !empty($prop);
        }

        /*
        --------------------------------------------------
        General method to get/check/set properties

        @param string $name Property name
        @param mixed $default Default value to return
        @param bool Whether to return a single value for the meta type

        @return mixed
        --------------------------------------------------
        */
        public function __call($name, $args)
        {
            if(strpos($name, 'get_') === 0)
            {
                $default = isset($args[0]) ? $args[0] : null;
                $single = isset($args[1]) ? $args[1] : true;
    
                $prop_name = substr($name, strlen('get_'));
                return $this->get_prop($prop_name, $default, $single);
            }
            elseif(strpos($name, 'has_') === 0){
                
                $prop_name = substr($name, strlen('has_'));
                return $this->has_prop($prop_name);
            }
            elseif(strpos($name, 'set_') === 0){

                $value = isset($args[0]) ? $args[0] : null;
    
                $prop_name = substr($name, strlen('g_'));
                return $this->set_prop($prop_name, $value);
            }
            
            return null;
        }

        /*
        --------------------------------------------------
        Get WP_Post ID from $this->id

        @return int
        --------------------------------------------------
        */
        public function get_id()
        {
            return $this->id;
        }
        
        /*
        --------------------------------------------------
        Get post_type from $this->data

        @return int
        --------------------------------------------------
        */
        public function get_type()
        {
            // return $this->get_data('post_type');
            return $this->post_type;
        }

        /*
        --------------------------------------------------
        Get data type properties

        @param string|null $key as in WP_Post object
        @param mixed $default Default value to return

        @return mixed If $key=null all data values will be returned
        --------------------------------------------------
        */
        public function get_data($key=null, $default=null)
        {
            if(!in_array('data', $this->prop_types))
            {
                return null;
            }
            
            if(!isset($key))
            {
                return $this->data;
            }

            return (empty($this->data[$key]) && isset($default)) ? $default : (isset($this->data[$key]) ? $this->data[$key] : null);
        }

        /*
        --------------------------------------------------
        Get meta type properties

        @param string|null $key
        @param bool $single Whether to return a single meta value
        @param mixed $default Default value to return

        @return mixed If $key=null all meta values will be returned
        --------------------------------------------------
        */
        public function get_meta($key=null, $default=null, $single=true)
        {
            if(!in_array('meta', $this->prop_types))
            {
                return null;
            }
            
            if(!isset($key))
            {
                if($single)
                {
                    $meta_s = [];
                    foreach($this->meta as $_key => $_meta)
                    {
                        if(isset($_meta[0])) $meta_s[$_key] = $_meta[0];
                    }

                    return (empty($meta_s) && isset($default)) ? $default : $meta_s;
                }

                return (empty($this->meta) && isset($default)) ? $default : $this->meta;
            }

            $meta = isset($this->meta[$key]) ? $this->meta[$key] : [];

            if($single)
            {
                $meta = isset($meta[0]) ? $meta[0] : null;
            }

            $meta = (empty($meta) && isset($default)) ? $default : (is_string($meta) ? trim($meta) : $meta);

            return $this->cast_prop($meta, $key);
        }

        /*
        --------------------------------------------------
        Get terms ids by taxonomy

        @prop string $taxonomy
        @param mixed $default Default value to return

        @return array
        --------------------------------------------------
        */
        public function get_terms($taxonomy, $default=null, $single=true)
        {
            if(!in_array('term', $this->prop_types))
            {
                return null;
            }

            if(!isset($taxonomy))
            {
                return $this->terms;
            }
            
            $terms = isset($this->terms[$taxonomy]) ? $this->terms[$taxonomy] : [];

            if($single)
            {
                $terms = isset($terms[0]) ? $terms[0] : $terms;
            }

            return (empty($terms) && isset($default)) ? $default : $terms;
        }

        /*
        --------------------------------------------------
        Get attachments ids

        @prop string|null $key
        @param mixed $default Default value to return

        @return array|int
        --------------------------------------------------
        */
        public function get_attachments($key=null, $default=null)
        {
            if(!in_array('attachment', $this->prop_types)) return null;

            if(!isset($key)) return $this->attachments;

            $attachments = wp_parse_id_list($this->get_meta($key));

            return (isset($default) && empty($attachments)) ? $default : $attachments;
        }

        /*
        --------------------------------------------------
        Get permalink

        @return string
        --------------------------------------------------
        */
        public function get_permalink()
        {
            return $this->permalink;
        }

        /*
        --------------------------------------------------
        Get $props_config

        @prop string|null $key

        @return array|bool
        --------------------------------------------------
        */
        public function get_props_config($key=null)
        {
            if(isset($key))
            {
                return isset($this->props_config[$key]) ? $this->props_config[$key] : [];
            }
            return $this->props_config;
        }

        /*
        --------------------------------------------------
        Get prop from $this->data or $this->meta

        @return mixed
        --------------------------------------------------
        */

        public function get_prop($key, $_default=null, $single=true)
        {
            $prop_config = $this->get_props_config($key);
            $type = isset($prop_config['type']) ? $prop_config['type'] : 'data';
            $sys_key = isset($prop_config['sys_key']) ? $prop_config['sys_key'] : $key;
            $default = isset($prop_config['default']) ? $prop_config['default'] : $_default;

            switch($type)
            {
                case 'data':
                    return $this->get_data($sys_key, $default);
                break;
                case 'meta':
                    return $this->get_meta($sys_key, $default, $single);
                break;
                case 'taxonomy':
                    return $this->get_terms($sys_key, $default, $single);
                break;
                case 'attachment':
                    return $this->get_attachments($sys_key, $default);
                break;
            }

            return null;
        }

        /*
        --------------------------------------------------
        Delete the children posts
        
        @return void
        --------------------------------------------------
        */
        private function delete_children($force_delete=true){
            
            if(!$this->id) return;
            
            $children_posts = get_posts([
                'post_parent' => $this->id,
                'post_status' => 'any',
                'posts_per_page' => -1
            ]);

            foreach((array)$children_posts as $post)
            {
                wp_delete_post($post->ID, $force_delete);
            }
        }

        /*
        --------------------------------------------------
        Validate object properties

        @return array
        --------------------------------------------------
        */
        public function validate()
        {
            $errors = [
                'field_errors' => []
            ];

            foreach((array)$this->props_config as $key => $prop_config)
            {
                $value = $this->get_prop($key);

                if($prop_config['required'] && empty($value))
                {
                    $errors['field_errors'][] = $key;
                }
            }

            return $errors;
        }

        protected function cast_prop_walker(&$prop_item, $i, $cast)
        {
            $prop = $this->cast_prop($prop, null, $cast);
        }

        protected function cast_prop($prop, $prop_name=null, $cast=null)
        {
            if(!isset($cast))
            {
                $prop_config = isset($prop_name) ? $this->get_props_config($prop_name) : null;
                $cast = (isset($prop_config) && isset($prop_config['cast'])) ? $prop_config['cast'] : null;
            }

            if(isset($cast))
            {
                if(is_array($prop))
                {
                    array_walk($prop, [$this, 'cast_prop_walker'], $cast);
                }
    
                switch($cast)
                {
                    case 'int':
                    case 'integer':
                        $prop = intval($prop);
                    break;
                    case 'float':
                    case 'floatval':
                        $prop = floatval($prop);
                    break;
                    case 'bool':
                    case 'boolean';
                        $prop = boolval($prop);
                    break;
                    case 'str':
                    case 'string';
                        $prop = strval($prop);
                    break;
                }
            }

            return $prop;
        }
    }
}