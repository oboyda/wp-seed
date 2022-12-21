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
            $this->set_prop_types(['data', 'meta', 'term', 'attachment']);

            parent::__construct($props_config);

            $this->init_data($post);
            $this->init_meta();
            $this->init_terms();
            $this->init_attachments();
        }

        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        
        protected function init_data($post=null)
        {
            if(!in_array('data', $this->prop_types)) return;

            $this->id = 0;
            $this->data = [];
            $this->permalink = '';

            $_post = (is_int($post) && $post) ? get_post($post) : $post;

            if(is_a($_post, 'WP_Post'))
            {
                $this->data = (array)$_post;

                $this->set_id($_post->ID);
                $this->set_post_type($_post->post_type);
                $this->set_parent_id($_post->post_parent);
                $this->set_permalink();
            }
        }

        protected function init_meta()
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

        protected function init_terms()
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

        protected function init_attachments()
        {
            if(!in_array('attachment', $this->prop_types)) return;
            
            $this->attachments = [];
            $this->attachments_insert = [];
            $this->attachments_delete = [];
            
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

        public function set_post_type($post_type)
        {
            $this->post_type = $post_type;
            $this->set_data('post_type', $post_type);
        }

        public function set_parent_id($id)
        {
            $this->parent_id = $id;
            $this->set_data('post_parent', $id);
        }

        public function set_permalink($permalink=null)
        {
            if(!isset($permalink) && $this->get_id())
            {
                $permalink = get_permalink($this->get_id());
            }

            $this->permalink = $permalink;
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
        Get post_type

        @return string
        --------------------------------------------------
        */
        public function get_post_type()
        {
            // return $this->get_data('post_type');
            return $this->post_type;
        }
        public function get_type()
        {
            return $this->get_post_type();
        }

        /*
        --------------------------------------------------
        Get parent_id

        @return bool
        --------------------------------------------------
        */
        public function get_parent_id()
        {
            return $this->parent_id;
        }

        /*
        --------------------------------------------------
        Save object's data to the database from $this->data, $this->meta, $this->terms

        @return void
        --------------------------------------------------
        */
        public function persist()
        {
            $updating = (bool)$this->get_id();

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

            if($updating)
            {
                $id = wp_update_post($data);
                if(is_wp_error($id))
                {
                    return false;
                }

                $this->set_id((int)$id);

                do_action('wpseed_post_updated', $this->get_id(), $this);
            }
            else{
                $id = wp_insert_post($data);
                if(is_wp_error($id))
                {
                    return false;
                }

                do_action('wpseed_post_inserted', $id, $this);
            }

            $this->persist_terms();

            $this->persist_attachments();

            // if(!$updating)
            // {
            //     $this->__construct(
            //         $this->get_id(), 
            //         $this->props_config
            //     );
            // }

            do_action('wpseed_post_persisted', $this->get_id(), $this);

            return true;
        }

        protected function persist_terms()
        {
            if(!($this->get_id() && in_array('term', $this->prop_types)))
            {
                return;
            }

            if(!empty($this->terms))
            {
                foreach($this->terms as $taxonomy => $terms)
                {
                    wp_set_object_terms($this->get_id(), $terms, $taxonomy, false);
                }
            }
        }

        protected function persist_attachments()
        {
            if(!($this->get_id() && in_array('attachment', $this->prop_types)))
            {
                return;
            }

            if(!empty($this->attachments_delete))
            {
                foreach($this->attachments_delete as $key => $attachments)
                {
                    foreach($attachments as $attachment)
                    {
                        if($attachment->get_parent_id() === $this->get_id())
                        {
                            $attachment->delete(true);
                        }
                    }
                }
            }

            if(!empty($this->attachments_insert))
            {
                foreach($this->attachments_insert as $key => $attachments)
                {
                    $attachment_ids = [];
                    foreach($attachments as $attachment)
                    {
                        $attachment->set_data('post_parent', $this->get_id());
                        $attachment->persist();
                        if($attachment->get_id())
                        {
                            $attachment_ids[] = $attachment->get_id();
                        }
                    }

                    $this->set_attachments($key, $attachment_ids);

                    // We need to persist again in order to update the new attachment meta
                    // Do not persist to avoid looping
                    update_post_meta($this->get_id(), $key, $attachment_ids);
                }

            }

            $this->attachments_delete = [];
            $this->attachments_insert = [];
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