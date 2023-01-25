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
            $this->set_prop_types(['data', 'meta']);

            parent::__construct($props_config);

            $this->init_data($user);
            $this->init_meta();
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

            if($reconstruct)
            {
                $this->__construct(
                    $this->get_id(), 
                    $this->props_config
                );
            }

            return true;
        }

        /*
        --------------------------------------------------
        Delete user
        
        @param bool $reassign Reassign posts and links to new User ID

        @return void
        --------------------------------------------------
        */
        public function delete($reassign=null)
        {
            if(!$this->id) return false;
            
            return wp_delete_user($this->id, $reassign);
        }
    }
}