<?php
namespace AC;

class View extends Helpers {
    
    var $args;
    
    public function __construct($args=[], $default_args=[]){
        
        $this->args = $args;
        
        if(!empty($default_args)){
            $this->args = wp_parse_args($args, $default_args);
        }
        
    }
    
}
