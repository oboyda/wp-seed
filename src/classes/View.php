<?php

namespace WPSEED;

if(!class_exists('\WPSEED\View'))
{
    class View {
        
        var $id;
        var $args;
            
        /*
        * Construct the View object
        *
        * @param array $args Arguments to be used in the template. Will be merged with the default arguments.
        * @param array $args Default arguments to be used in the template.
        */

        public function __construct($args=[], $default_args=[])
        {
            $this->id = $this->genId();
            $this->args = empty($default_args) ? $args : wp_parse_args($args, $default_args);
        }

        public function getAjaxUrl()
        {
            return admin_url('admin-ajax.php');
        }
    
        private function genId()
        {
            $chars = str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890');
            return 'view-' . substr(str_shuffle($chars), 0, 10);
        }
    
        public function getId()
        {
            return $this->id;
        }
            
    }
}