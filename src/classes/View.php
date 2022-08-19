<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\View'))
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
            $this->args = empty($default_args) ? $args : wp_parse_args($args, $default_args);
            $this->id = empty($this->args['id']) ? $this->genId() : $this->args['id'];
            
            // $this->setArgsToProps();
        }
        
        /* 
         * Set $this->args to object properties
         * 
         */
        protected function setArgsToProps($force_set=false)
        {
            foreach($this->args as $name => $arg)
            {
                if(!isset($this->$name) || $force_set) $this->$name = $arg;
            }
        }
        
        /* 
         * Magic method for getting (get_[property_name]) and checking (has_[property_name]) object properties
         * 
         * @return mixed
         */
        public function __call($name, $args)
        {
            if(strpos($name, 'get_') === 0)
            {
                $var = substr($name, strlen('get_'));
                
                return isset($this->args[$var]) ? $this->args[$var] : null;
                return isset($this->$var) ? $this->$var : null;
            }
            elseif(strpos($name, 'has_') === 0){
                
                $var = substr($name, strlen('has_'));
                
                return isset($this->args[$var]) ? (is_bool($this->args[$var]) ? $this->args[$var] : !empty($this->args[$var])) : false;
                return isset($this->$var) ? (is_bool($this->$var) ? $this->$var : !empty($this->$var)) : false;
            }
            
            return null;
        }

        /* 
         * Return WP ajax URL
         * 
         * @return str
         */
        public function getAjaxUrl()
        {
            return admin_url('admin-ajax.php');
        }
    
        /* 
         * Generates random ID
         * 
         * @return str
         */
        private function genId()
        {
            $chars = str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890');
            return 'view-' . substr(str_shuffle($chars), 0, 10);
        }
    
        /* 
         * Get random ID
         * 
         * @return str
         */
        public function getId()
        {
            return $this->id;
        }
        
        /* 
         * Get view name
         * 
         * @return str
         */
        public function getName($underscore=false)
        {
            $name = strtolower(basename(str_replace('\\', '/', get_called_class())));
            
            return $underscore ? $name : str_replace('_', '-', $name);
        }
        
        /* 
         * Get current view classes
         * 
         * @param str|array $merge_classes
         * @return str
         */
        public function getHtmlClass($merge_classes=null)
        {
            $classes = [
                'view'
            ];
            
            $view_name = $this->getName();
            
            $classes[] = $view_name;
            
            if(isset($merge_classes))
            {
                if(is_string($merge_classes))
                {
                    $merge_classes = explode(' ', $merge_classes);
                }
                
                $classes = array_merge($classes, $merge_classes);
            }
            
            return implode(' ', $classes);
        }
    }
}