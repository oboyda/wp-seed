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
            $this->_set_prop_types(['data', 'meta']);

            parent::__construct($props_config);

            $this->_set_data($user);
            $this->_set_meta();
        }
        
        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        
        protected function _set_data($user=null)
        {
            if(!in_array('data', $this->prop_types)) return;

            $this->id = 0;
            $this->data = [];
            $this->role = '';

            $_user = is_int($user) ? get_userdata($user) : $user;

            if(is_a($_user, 'WP_User'))
            {
                $this->id = $_user->ID;
                $this->data = (array)$_user->data;
                if(isset($_user->roles[0])) $this->role = $_user->roles[0];
            }
        }

        protected function _set_meta()
        {
            if(!in_array('meta', $this->prop_types)) return;
            
            $this->meta = [];

            if($this->id)
            {
                $meta = get_user_meta($this->id);
                foreach((array)$meta as $key => $meta_item)
                {
                    foreach((array)$meta_item as $i => $m)
                    {
                        $this->meta[$key][$i] = maybe_unserialize($m);
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
        public function persist()
        {
            $id = 0;

            if(!empty($this->data))
            {
                
                $user_email = $this->get_data('user_email');

                if(empty($user_email)) return;

                $user_login = $this->get_data('user_login');

                if(empty($user_login)){
                    $this->set_data('user_login', $user_email);
                }
                
                $data = $this->get_data();
                $meta = $this->get_meta(null, null, true);

                $password = $data['user_pass'];
                unset($data['user_pass']);

                if(!empty($data['ID']))
                {
                    $id = wp_update_user($data);
                }
                elseif(!empty($password))
                {
                    $data['user_pass'] = $password;
                    $id = wp_insert_user($data);
                }
            }

            if($id !== $this->id)
            {
                $this->__construct(
                    $id, 
                    $this->props_config
                );
            }
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