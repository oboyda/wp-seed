<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\User'))
{
    class User 
    {
        protected $id;

        protected $prop_types;
        protected $props_config;

        protected $data;
        protected $meta;

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
            if(!isset($this->prop_types))
            {
                $this->prop_types = ['data', 'meta'];
            }

            $this->_set_data($user);
            $this->_set_meta();
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

            if(!(isset($prop_config['options']) && in_array($value, $prop_config['options']))) return;
            
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
        Get WP_User ID from $this->id

        @return int
        --------------------------------------------------
        */
        public function get_id()
        {
            return $this->id;
        }

        /*
        --------------------------------------------------
        Get data type properties

        @param string|null $key as in WP_User object
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
            if(array_key_exists($key, $this->data))
            {
                return $this->get_data($key, $default);
            }
            elseif(array_key_exists($key, $this->meta))
            {
                return $this->get_meta($key, $single, $default);
            }
            
            return null;
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
                $meta = $this->get_meta(null, true);

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