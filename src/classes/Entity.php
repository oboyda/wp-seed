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
        public function __construct($post=null, $props_config=[])
        {
            if(!isset($this->prop_types))
            {
                $this->prop_types = ['data', 'meta', 'term', 'attachment'];
            }

            $this->_set_data($post);
            $this->_set_meta();
            $this->_set_terms();
            $this->_set_attachments();
            $this->_set_props_config($props_config);
        }
        
        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        
        protected function _set_prop_types($prop_types)
        {
            $this->prop_types = $prop_types;
        }

        protected function _set_data($post=null)
        {
            if(!in_array('data', $this->prop_types)) return;

            $this->id = 0;
            $this->data = [];
            $this->permalink = '';

            $_post = is_int($post) ? get_post($post) : $post;

            if(is_a($_post, 'WP_Post'))
            {
                $this->id = $_post->ID;
                if(!isset($this->post_type))
                {
                    $this->post_type = $_post['post_type'];
                }
                $this->data = (array)$_post;
                $this->permalink = get_permalink($this->id);
            }
        }

        protected function _set_meta()
        {
            if(!in_array('meta', $this->prop_types)) return;
            
            $this->meta = [];

            if($this->id)
            {
                $meta = get_post_meta($this->id);
                foreach((array)$meta as $key => $meta_item)
                {
                    foreach((array)$meta_item as $i => $m)
                    {
                        $this->meta[$key][$i] = maybe_unserialize($m);
                    }
                }
            }
        }

        protected function _set_terms()
        {
            if(!in_array('term', $this->prop_types)) return;
            
            $this->terms = [];

            if($this->id)
            {
                $taxonomies = get_object_taxonomies($this->get_type());
                foreach((array)$taxonomies as $taxonomy)
                {
                    $terms = wp_get_object_terms($this->id, $taxonomy, ['fields' => 'ids']);
                    $this->terms[$taxonomy] = is_wp_error($terms) ? [] : $terms;
                }
            }
        }

        protected function _set_attachments()
        {
            if(!in_array('attachment', $this->prop_types)) return;
            
            $this->attachments = [];
            
            if(!$this->id) return;

            $this->attachments = get_posts([
                'post_type' => 'attachment',
                'posts_per_page' => -1,
                'post_parent' => $this->id,
                'post_status' => 'any',
                'order' => 'ASC',
                'orderby' => 'menu_order',
                'fields' => 'ids'
            ]);
        }

        protected function _set_props_config($props_config)
        {
            foreach((array)$props_config as $key => $prop_config)
            {
                $this->props_config[$key] = wp_parse_args($prop_config, [
                    'type' => 'data',
                    'label' => $key,
                    'required' => false
                ]);
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

            $_keys = [
                'post_author',
                'post_date',
                'post_date_gmt',
                'post_content',
                'post_title',
                'post_excerpt',
                'post_status',
                'comment_status',
                'ping_status',
                'post_password',
                'post_name',
                'to_ping',
                'pinged',
                'post_modified',
                'post_modified_gmt',
                'post_content_filtered',
                'post_parent',
                'guid',
                'menu_order',
                'post_type',
                'post_mime_type',
                'comment_count'
            ];

            if(in_array($key, $_keys))
            {
                $this->data[$key] = $value;
            }
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
            if(!in_array('meta', $this->prop_types)) return;

            $prop_config = $this->get_props_config($key);

            if(!(isset($prop_config) && $prop_config['type'] === 'meta')) return;

            if(!(!isset($prop_config['options']) || isset($prop_config['options'][$value]))) return;

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
        Set terms

        @param string $taxonomy
        @param array $terms Array of terms ids

        @return void
        --------------------------------------------------
        */
        public function set_terms($taxonomy, $terms)
        {
            if(!in_array('term', $this->prop_types)) return;

            if(!isset($this->terms[$taxonomy])) $this->terms[$taxonomy] = [];

            foreach((array)$terms as $term)
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

        /*
        --------------------------------------------------
        Set $this->attachments;

        @param string $key
        @param array $attachments Array of attachments ids or Attachment instances

        @return void
        --------------------------------------------------
        */
        public function set_attachments($key, $attachments)
        {
            if(!in_array('attachment', $this->prop_types)) return;

            $prop_config = $this->get_props_config($key);
            
            if(!(isset($prop_config) && $prop_config['type'] === 'attachment')) return;

            $_attachments = [];

            foreach((array)$attachments as $attachment)
            {
                if(is_a($attachment, '\WPSEED\Attachment'))
                {
                    $_attachments[$key][] = $attachment->get_id();
                }
                else
                {
                    $_attachments[$key][] = (int)$attachment;
                }
            }

            if(!is_array($attachments) && isset($_attachments[0])) $_attachments = $_attachments[0];

            $thit->set_meta($key, $_attachments);
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
            $type = isset($prop_config) ? $prop_config['type'] : 'data';

            switch($type)
            {
                case 'data':
                    $this->set_data($key, $value);
                    break;
                case 'meta':
                    $this->set_meta($key, $value);
                    break;
                case 'term':
                    $this->set_terms($key, $value);
                    break;
                case 'attachment':
                    $this->set_attachments($key, $value);
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
            if(!in_array('data', $this->prop_types)) return null;
            
            if(!isset($key)) return $this->data;

            return (isset($default) && empty($this->data[$key])) ? $default : $this->data[$key];
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
        public function get_meta($key=null, $single=true, $default=null)
        {
            if(!in_array('meta', $this->prop_types)) return null;
            
            if(!isset($key))
            {
                if($single)
                {
                    $meta = [];
                    foreach($this->meta as $_key => $_meta)
                    {
                        if(isset($_meta[0])) $meta[$_key] = $_meta[0];
                    }
                    return $meta;
                }
                return $this->meta;
            }

            $meta = isset($this->meta[$key]) ? $this->meta[$key] : false;

            $meta = (isset($default) && empty($meta)) ? $default : $meta;

            return ($single && isset($meta[0])) ? $meta[0] : $meta;
        }

        /*
        --------------------------------------------------
        Get terms ids by taxonomy

        @prop string $taxonomy
        @param mixed $default Default value to return

        @return array
        --------------------------------------------------
        */
        public function get_terms($taxonomy=null, $default=null)
        {
            if(!in_array('term', $this->prop_types)) return null;

            if(!isset($taxonomy)) return $this->terms;
            
            $terms = isset($this->terms[$taxonomy]) ? $this->terms[$taxonomy] : false;

            return (isset($default) && empty($terms)) ? $default : $terms;
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
                return isset($this->props_config[$key]) ? $this->props_config[$key] : null;
            }
            return $this->props_config;
        }

        /*
        --------------------------------------------------
        Get prop from $this->data or $this->meta

        @return mixed
        --------------------------------------------------
        */

        public function get_prop($key, $default=null, $single=false)
        {
            $prop_config = $this->get_props_config($key);

            if(array_key_exists($key, $this->data))
            {
                return $this->get_data($key, $default);
            }
            elseif(array_key_exists($key, $this->meta))
            {
                return $this->get_meta($key, $single, $default);
            }
            elseif(array_key_exists($key, $this->terms))
            {
                return $this->get_terms($key, $default);
            }
            elseif(isset($prop_config) && $prop_config['type'] === 'attachment')
            {
                return $this->get_attachments($key, $default);
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
    }
}