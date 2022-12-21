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
                'fields' => [],
                'error_fields' => [],
                'errors' => []
            ];

            if(!empty($fields_config))
            {
                foreach($fields_config as $key => $field_config)
                {
                    $type = isset($field_config['type']) ? $field_config['type'] : 'text';
                    $validate = in_array($type, ['file', 'attachment']) ? 'file' : ( isset($field_config['validate']) ? $field_config['validate'] : (isset($field_config['cast']) ? $field_config['cast'] : 'text') );
                    $required = isset($field_config['required']) ? $field_config['required'] : false;

                    $value = ($validate == 'file') ? $this->getFile($key) : $this->get($key, $validate);

                    $skip_add_field = false;

                    /* 
                    Add to errors if required and empty
                    -------------------------
                    */
                    if($required && empty($value))
                    {
                        $result['error_fields'][] = $key;
                    }

                    switch($validate)
                    {
                        case 'email':

                            if(empty($value))
                            {
                                break;
                            }

                            if(!filter_var($value, FILTER_VALIDATE_EMAIL))
                            {
                                $result['error_fields'][] = $key;
                            }
                            break;

                        case 'file':

                            if(empty($value))
                            {
                                // Do not create index in $result['fields'] 
                                // as an empty array to prevent deleting attachments
                                $skip_add_field = true;

                                break;
                            }

                            foreach($value as $i => $file)
                            {
                                /* 
                                Check server errors
                                -------------------------
                                */
                                if(!empty($file['error']))
                                {
                                    $result['error_fields'][] = $key;
                                    $result['errors'][] = sprintf(__('%s failed to upload', 'wpseed'), $file['name']);
                                }

                                /* 
                                Validate type
                                -------------------------
                                */
                                if(isset($field_config['file_types']) && !in_array($file['type'], $field_config['file_types']))
                                {
                                    $result['error_fields'][] = $key;
                                    $result['errors'][] = sprintf(__('%s file type %s is not allowed', 'wpseed'), $file['name'], $file['type']);
                                }

                                /* 
                                Validate size
                                -------------------------
                                */
                                if(isset($field_config['file_max_size']) && $file['size'] > $field_config['file_max_size'])
                                {
                                    $result['error_fields'][] = $key;
                                    $result['errors'][] = sprintf(__('%s file size is not allowed', 'wpseed'), $file['name']);
                                }
                            }

                            break;
                    }

                    if(!$skip_add_field)
                    {
                        $result['fields'][$key] = $value;
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
                    if(!empty($file_name))
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
            }

            return $_file;
        }
    }
}