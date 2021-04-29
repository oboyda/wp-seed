<?php

namespace WPSEED;

if(!class_exists('\WPSEED\View'))
{
    class View {
        
        var $args;
        
        /*
        * Construct the View object
        *
        * @param array $args Arguments to be used in the template. Will be merged with the default arguments.
        * @param array $args Default arguments to be used in the template.
        */

        public function __construct($args=[], $default_args=[]){
            
            $this->args = empty($default_args) ? $args : wp_parse_args($args, $default_args);
        }
        
    }
}