<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Req'))
{
    class Req {

        private $req;
    
        public function __construct()
        {
            $this->req = [];
        }
    
        /*
         * Get request variable from $_REQUEST
         *
         * @param string $key Variable name as in $_REQUEST
         * @param string $san_type text|textarea|int/integer|float Sanitize variable
         * @param mixed $default Default value if variable is empty
         * @return mixed
         */

        public function get($key, $san_type='text', $default=null)
        {
            if(is_array($key)){
                $values = [];
                foreach($key as $_key)
                {
                    $value = $this->get($_key, $san_type, $default);
                    if($value) $values[$_key] = $value;
                }
                return $values;
            }

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
        
        private function sanitizeReq($val_item, $san_type='text')
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
        
        private function sanitizeReqArr(&$val_item, $san_type='text', $urldec=false)
        {
            if($urldec) $val_item = urldecode($val_item);
            $val_item = self::sanitizeReq($val_item, $san_type);
        }

        public function getFile($key)
        {
            if(is_array($key)){
                $files = [];
                foreach($key as $_key)
                {
                    $file = $this->getFile($_key);
                    if($file) $files[$_key] = $file;
                }
                return $files;
            }

            return isset($_FILES[$key]) ? $_FILES[$key] : false;
        }
    }
}