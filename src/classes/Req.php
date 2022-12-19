<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Req'))
{
    class Req {

        private $req;
        private $files;
    
        public function __construct()
        {
            $this->req = [];
            $this->files = [];
        }
    
        /*
        -------------------------
        Get request variable from $_REQUEST
        
        @param string $key Variable name as in $_REQUEST
        @param string $san_type text|textarea|int/integer|float Sanitize variable
        @param mixed $default Default value if variable is empty
        @return mixed
        -------------------------
        */

        public function get($key, $san_type='text', $default=null)
        {
            if(!isset($this->req[$key]))
            {
                $val = isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
            
                if(isset($val))
                {
                    if(is_array($val))
                    {
                        array_walk_recursive($val, [$this, 'sanitizeReqArr'], $san_type);
                    }
                    else{

                        $val = self::sanitizeReq(urldecode($val), $san_type);
                    }
                }
                
                $this->req[$key] = $val;
            }
            
            return (empty($this->req[$key]) && isset($default)) ? $default : $this->req[$key];
        }

        public function getFile($key, $default=null)
        {
            if(!isset($this->files[$key]))
            {
                $this->files[$key] = isset($_FILES[$key]) ? $this->parseFileInput($_FILES[$key]) : null;
            }

            return (empty($this->files[$key]) && isset($default)) ? $default : $this->files[$key];
        }
        
        public function validateFields($fields_config)
        {
            $result = [
                'error_fields' => []
                // 'errors' => []
            ];

            if(!empty($fields_config))
            {
                foreach($fields_config as $key => $field_config)
                {
                    $sanitize = isset($field_config['sanitize']) ? $field_config['sanitize'] : 'text';
                    $required = isset($field_config['required']) ? $field_config['required'] : false;

                    $val = $this->get($key, $sanitize);

                    /* 
                    Add to errors if required and empty
                    -------------------------
                    */
                    if($required && empty($val))
                    {
                        $result['error_fields'][] = $key;
                    }

                    $type = isset($field_config['type']) ? $field_config['type'] : 'text';

                    switch($type)
                    {
                        case 'email':

                            if(!filter_var($val, FILTER_VALIDATE_EMAIL))
                            {
                                $result['error_fields'][] = $key;
                            }
                            break;

                        case 'file':
                        case 'attachment':

                            $file = isset($_FILES[$key]) ? $this->parseFileInput($_FILES[$key]) : null;

                            if($required && empty($file))
                            {
                                $result['error_fields'][] = $key;
                            }

                            foreach($file as $i => $_file)
                            {
                                if(empty($_file['name']) && !$required)
                                {
                                    continue;
                                }

                                /* 
                                Check server errors
                                -------------------------
                                */
                                if(!empty($_file['error']))
                                {
                                    $result['error_fields'][] = $key;
                                    // $result['errors'][] = sprintf(__('%s failed to upload', 'ac'), $file_name);

                                    continue;
                                }

                                /* 
                                Validate type
                                -------------------------
                                */
                                if(isset($field_config['file_types']) && !in_array($_file['type'], $field_config['file_types']))
                                {
                                    $result['error_fields'][] = $key;
                                    // $result['errors'][] = sprintf(__('%s file type %s is not allowed', 'ac'), $file_name, $file['type'][$i]);
                                }

                                /* 
                                Validate size
                                -------------------------
                                */
                                if(isset($field_config['file_max_size']) && $_file['size'] > $field_config['file_max_size'])
                                {
                                    $result['error_fields'][] = $key;
                                    // $result['errors'][] = sprintf(__('%s file size is not allowed', 'ac'), $file_name);
                                }
                            }

                            break;

                        // default:

                        //     if($required && empty($file))
                        //     {
                        //         $result['error_fields'][] = $key;
                        //     }
                    }
                }
            }

            $result['error_fields'] = array_unique($result['error_fields']);

            return $result;
        }

        protected function sanitizeReq($val_item, $san_type='text')
        {
            $val_item = trim($val_item);
            
            switch($san_type)
            {
                case 'text':
                    $val_item = sanitize_text_field($val_item);
                    break;
                case 'textarea':
                    $val_item = sanitize_textarea_field($val_item);
                    break;
                case 'int':
                case 'integer':
                    $val_item = intval($val_item);
                    break;
                case 'floatval':
                    $val_item = floatval($val_item);
                    break;
            }
            
            return $val_item;
        }
        
        protected function sanitizeReqArr(&$val_item, $san_type='text', $urldec=false)
        {
            if($urldec) $val_item = urldecode($val_item);
            $val_item = self::sanitizeReq($val_item, $san_type);
        }

        protected function parseFileInput($file)
        {
            $_file = [];

            if(isset($file['name']) && !is_array($file['name']))
            {
                $file['name'] = [$file['name']];
                $file['type'] = isset($file['type']) ? [$file['type']] : [''];
                $file['tmp_name'] = isset($file['tmp_name']) ? [$file['tmp_name']] : [''];
                $file['error'] = isset($file['error']) ? [$file['error']] : [0];
                $file['size'] = isset($file['size']) ? [$file['size']] : [0];

            }

            if(isset($file['name']))
            {
                foreach($file['name'] as $i => $file_name)
                {
                    $_file[$i] = [
                        'name' => $file_name,
                        'type' => (is_array($file['type']) && isset($file['type'][$i])) ? $file['type'][$i] : '',
                        'tmp_name' => (is_array($file['tmp_name']) && isset($file['tmp_name'][$i])) ? $file['tmp_name'][$i] : '',
                        'error' => (is_array($file['error']) && isset($file['error'][$i])) ? $file['error'][$i] : 0,
                        'size' => (is_array($file['size']) && isset($file['size'][$i])) ? $file['size'][$i] : 0
                    ];
                }
            }

            return $_file;
        }
    }
}