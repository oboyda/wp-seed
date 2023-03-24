<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\User'))
{
    class User extends Entity
    {
        protected $role;
        
        /*
        --------------------------------------------------
        Construct the User object

        @param object|int $user WP_User instance or user ID.
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
        public function __construct($user=null, $props_config=[])
        {
            parent::__construct($props_config);

            $this->set_prop_types(['data', 'meta', 'attachment']);

            $this->init_data($user);
            $this->init_meta();
            $this->init_attachments();
        }
        
        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        
        protected function init_data($user=null)
        {
            if(!in_array('data', $this->prop_types)) return;

            $this->id = 0;
            $this->data = [];
            $this->role = '';

            $_user = (is_int($user) && $user) ? get_userdata($user) : $user;

            if(is_a($_user, 'WP_User'))
            {
                $this->data = (array)$_user->data;

                $this->set_id($_user->ID);
                if(isset($_user->roles[0]))
                {
                    $this->set_role($_user->roles[0]);
                }
            }
        }

        protected function init_meta()
        {
            if(!in_array('meta', $this->prop_types)) return;
            
            $this->meta = [];

            if($this->id)
            {
                $meta = get_user_meta($this->id);

                foreach($this->get_props_config() as $key => $prop_config)
                {
                    $type = isset($prop_config['type']) ? $prop_config['type'] : 'data';
                    $sys_key = isset($prop_config['sys_key']) ? $prop_config['sys_key'] : $key;
                    
                    if(in_array($type, ['meta', 'file', 'attachment']) && isset($meta[$sys_key]))
                    {
                        $this->meta[$sys_key] = [];

                        foreach($meta[$sys_key] as $i => $_meta)
                        {
                            $this->meta[$sys_key][$i] = maybe_unserialize($_meta);
                        }
                    }
                }
            }
        }

        protected function init_attachments()
        {
            if(!in_array('attachment', $this->prop_types)) return;
            
            $this->attachments = [];
            $this->attachments_insert = [];
            $this->attachments_delete = [];
            
            // if(!$this->id) return;

            // $this->attachments = get_posts([
            //     'post_type' => 'attachment',
            //     'posts_per_page' => -1,
            //     'post_parent' => $this->id,
            //     'post_status' => 'any',
            //     'order' => 'ASC',
            //     'orderby' => 'menu_order',
            //     'fields' => 'ids'
            // ]);
        }

        /*
        --------------------------------------------------
        Set data type properties. 
        Data properties map to WP_User object properties;

        @param string $key as in WP_User object
        @param mixed $value

        @return void
        --------------------------------------------------
        */
        public function set_data($key, $value)
        {
            if(!in_array('data', $this->prop_types)) return;

            $_keys = [
                'ID',
                'user_login',
                'user_pass',
                'user_nicename',
                'user_email',
                'user_url',
                'user_registered',
                'user_activation_key',
                'user_status',
                'display_name'
            ];

            if(in_array($key, $_keys))
            {
                $this->data[$key] = $value;
            }
        }

        /*
        --------------------------------------------------
        Set user role

        @param string $role

        @return void
        --------------------------------------------------
        */
        public function set_role($role)
        {
            $this->role = $role;
        }

        /*
        --------------------------------------------------
        Get user role

        @return string
        --------------------------------------------------
        */
        public function get_role()
        {
            return $this->role;
        }

        /*
        --------------------------------------------------
        Save object's data to the database from $this->data, $this->meta

        @return void
        --------------------------------------------------
        */
        public function persist($reconstruct=false)
        {
            $updating = (bool)$this->get_id();

            if(!$this->get_data('user_login'))
            {
                $this->set_data('user_login', $this->get_data('user_email'));
            }

            if(!$this->get_data('user_pass'))
            {
                $this->set_data('user_pass', wp_generate_password());
            }

            $data = array_merge($this->get_data(), [
                'meta_input' => $this->get_meta(null, null, true)
            ]);

            if($updating)
            {
                $id = wp_update_user($data);
                if(is_wp_error($id))
                {
                    return false;
                }
            }
            else{
                $id = wp_insert_user($data);
                if(is_wp_error($id))
                {
                    return false;
                }

                $this->set_id((int)$id);
            }

            $this->persist_attachments();

            if($reconstruct)
            {
                $this->__construct(
                    $this->get_id(), 
                    $this->props_config
                );
            }

            return $this->get_id();
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
                        if($attachment->get_prop('post_author') === $this->get_id())
                        {
                            $attachment->delete(true);
                        }
                    }
                }

                $this->attachments_delete = [];
            }

            if(!empty($this->attachments_insert))
            {
                foreach($this->attachments_insert as $key => $attachments)
                {
                    $attachment_ids = [];
                    foreach($attachments as $attachment)
                    {
                        $attachment->set_parent_id(0);
                        $attachment->set_data('post_author', $this->get_id());
                        $attachment->persist();
                        if($attachment->get_id())
                        {
                            $attachment_ids[] = $attachment->get_id();
                        }
                    }

                    if($this->get_props_config($key, 'attachment_insert_mode', 'add') === 'add')
                    {
                        $attachment_ids = array_merge($attachment_ids, $this->get_meta($key, []));
                    }

                    $this->set_attachments($key, $attachment_ids);

                    // We need to persist again in order to update the new attachment meta
                    // Do not persist to avoid looping
                    update_user_meta($this->get_id(), $key, $attachment_ids);
                }

                $this->attachments_insert = [];
            }
        }

        /*
        --------------------------------------------------
        Delete user
        
        @param bool $reassign Reassign posts and links to new User ID
        @param bool $delete_attachments Whether to delete children

        @return void
        --------------------------------------------------
        */
        public function delete($reassign=null, $delete_attachments=false)
        {
            if(!$this->id) return false;

            if($delete_attachments)
            {
                $this->delete_attachments($force_delete);
            }
            
            return wp_delete_user($this->id, $reassign);
        }
    }
}