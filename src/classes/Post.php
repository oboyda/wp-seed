<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Post'))
{
    class Post extends Entity
    {
        /*
        --------------------------------------------------
        Construct the Post object

        @param object|int $post WP_Post instance or post ID.
        @param array $props_config['key'] = [
            'type' => 'data' (default) | 'meta' | 'term' | 'attachment'
            'label' => 'Field Label' (defaults to $key),
            'options' => [
                'option1' => 'Option Label 1',
                'option2' => 'Option Label 2'
            ],
            'required' => false (default) | true
        ]

        @return void
        --------------------------------------------------
        */
        public function __construct($post=null, $props_config=[])
        {
            $this->_set_prop_types(['data', 'meta', 'term', 'attachment']);

            parent::__construct($props_config);

            $this->_set_data($post);
            $this->_set_meta();
            $this->_set_terms();
            $this->_set_attachments();
        }

        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        
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
                    $this->post_type = $_post->post_type;
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

                foreach($this->get_props_config() as $key => $prop_config)
                {
                    $type = isset($prop_config['type']) ? $prop_config['type'] : 'data';
                    
                    if(in_array($type, ['meta', 'attachment']) && isset($meta[$key]))
                    {
                        $this->meta[$key] = [];

                        foreach($meta[$key] as $i => $_meta)
                        {
                            $this->meta[$key][$i] = maybe_unserialize($_meta);
                        }
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
                'ID',
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
        Save object's data to the database from $this->data, $this->meta, $this->terms

        @return void
        --------------------------------------------------
        */
        public function persist()
        {
            $id = 0;

            if(!$this->get_data('post_type') && isset($this->post_type))
            {
                $this->set_data('post_type', $this->post_type);
            }
            if(!$this->get_data('post_status'))
            {
                $this->set_data('post_status', 'publish');
            }

            $data = array_merge($this->get_data(), [
                'meta_input' => $this->get_meta(null, null, true)
            ]);

            if(isset($data['ID']))
            {
                $id = wp_insert_post($data);
                do_action('wpseed_post_inserted', $id, $this);
            }
            else{
                $id = wp_update_post($data);
                do_action('wpseed_post_updated', $id, $this);
            }

            if(!empty($this->terms) && $id)
            {
                foreach($this->terms as $taxonomy => $terms)
                {
                    wp_set_object_terms($id, $terms, $taxonomy, false);
                }
            }

            if($id !== $this->id)
            {
                $this->__construct(
                    $id, 
                    $this->props_config
                );
            }

            do_action('wpseed_post_persisted', $id, $this);
        }

        /*
        --------------------------------------------------
        Delete WP_Post
        
        @param bool $force_deleten Whether to mark as trashed or delete permanently
        @param bool $delete_children Whether to delete children

        @return void
        --------------------------------------------------
        */
        public function delete($force_delete=true, $delete_children=false)
        {
            if(!$this->id) return false;
            
            if($delete_children)
            {
                $this->delete_children($force_delete);
            }
            
            return wp_delete_post($this->id, $force_delete);
        }
    }
}