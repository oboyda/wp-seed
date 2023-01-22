<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\View'))
{
    class View {
        
        var $args;
        var $id;
        var $html_class;
        var $html_style;

        protected $context_name;
        protected $mod_name;

        // const CONTEXT_NAME = '';
        // const MOD_NAME = '';
            
        /*
        --------------------------------------------------
        Construct the View object

        @param array $args Arguments to be used in the template. Will be merged with the default arguments.
        @param array $args Default arguments to be used in the template.
        --------------------------------------------------
        */
        public function __construct($args=[], $default_args=[], $args_parse_deep=true)
        {
            $default_args = wp_parse_args($default_args, [
                'view_cap' => 'public'
            ]);

            if($args_parse_deep)
            {
                foreach($default_args as $key => $default_arg)
                {
                    if(is_array($default_arg) && isset($args[$key]) && is_array($args[$key]))
                    {
                        $args[$key] = wp_parse_args($args[$key], $default_arg);
                    }
                }
            }
            $this->args = wp_parse_args($args, $default_args);

            $this->id = empty($this->args['id']) ? $this->genId() : $this->args['id'];

            if(!isset($this->context_name))
            {
                $this->setContextName('');
            }
            if(!isset($this->mod_name))
            {
                $this->setModName('');
            }

            $this->html_class = ['view'];
            $this->addHtmlClass($this->getContextName());
            $this->addHtmlClass($this->getModName(true));
            $this->addHtmlClass($this->getViewName());

            $this->html_style = [];
        }
        
        /* 
        --------------------------------------------------
        Set $this->args to object properties. Depricated.
        --------------------------------------------------
        */
        protected function setArgsToProps($force_set=false)
        {
            foreach($this->args as $name => $arg)
            {
                if(!isset($this->$name) || $force_set) $this->$name = $arg;
            }
        }

        /* 
        --------------------------------------------------
        Set $this->context_name
        --------------------------------------------------
        */
        protected function setContextName($context_name)
        {
            $this->context_name = $context_name;
        }

        protected function getContextName()
        {
            return defined('static::CONTEXT_NAME') ? static::CONTEXT_NAME : (isset($this->context_name) ? $this->context_name : '');
        }
    
        /* 
        --------------------------------------------------
        Set $this->mod_name
        --------------------------------------------------
        */
        protected function setModName($mod_name)
        {
            $this->mod_name = $mod_name;
        }

        protected function getModName($as_slug=false)
        {
            $mod_name = defined('static::MOD_NAME') ? static::MOD_NAME : (isset($this->mod_name) ? $this->mod_name : '');
            return $as_slug ? strtolower(str_replace('_', '-', $mod_name)) : $mod_name;
        }
        
        /* 
        --------------------------------------------------
        Magic method for getting (get_[property_name]) and checking (has_[property_name]) object properties
        
        @return mixed
        --------------------------------------------------
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
        --------------------------------------------------
        Return WP ajax URL
        
        @return str
        --------------------------------------------------
        */
        public function getAjaxUrl()
        {
            return admin_url('admin-ajax.php');
        }
    
        /* 
        --------------------------------------------------
        Generates random ID
        
        @return str
        --------------------------------------------------
        */
        private function genId()
        {
            $chars = str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890');
            return 'view-' . substr($chars, 0, 10);
        }
    
        /* 
        --------------------------------------------------
        Get random ID
        
        @return str
        --------------------------------------------------
        */
        public function getId()
        {
            return $this->id;
        }
        
        /* 
        --------------------------------------------------
        Get view name
        
        @return str
        --------------------------------------------------
        */
        public function getViewName()
        {
            $name = strtolower(basename(str_replace('\\', '/', get_called_class())));
            
            return str_replace('_', '-', $name);
        }

        public function getName($include_context=false, $include_mod=true)
        {
            $name_parts = [];
    
            if($include_context && $this->getContextName())
            {
                $name_parts['context_name'] = $this->getContextName();
            }
    
            if($include_mod && $this->getModName())
            {
                $name_parts['mod_name'] = $this->getModName(true);
            }
    
            $name_parts['view_name'] = $this->getViewName();
    
            $name = implode('.', $name_parts);
    
            // $name = strtolower(str_replace('_', '-', $name));
    
            return $name;
        }
    
        /* 
        --------------------------------------------------
        Add view classes
        
        @param str|array $class
        --------------------------------------------------
        */
        public function addHtmlClass($class)
        {
            if(empty($class))
            {
                return;
            }

            $_class = is_array($class) ? $class : explode(' ', trim($class));

            $this->html_class = array_merge($this->html_class, $_class);
        }
            
        /* 
        --------------------------------------------------
        Get view classes
        
        @param str|array $add_class
        @return str
        --------------------------------------------------
        */
        public function getHtmlClass($add_class=null)
        {
            if(isset($add_class))
            {
                $this->addHtmlClass($add_class);
            }

            return implode(' ', $this->html_class);
        }

        /* 
        --------------------------------------------------
        Add view styles
        
        @param str $style_rule
        @param str $style_value
        --------------------------------------------------
        */
        protected function addHtmlStyle($style_rule, $style_value)
        {
            if(!isset($this->html_style[$style_rule]))
            {
                $this->html_style[$style_rule] = $style_value;
            }
        }
    
        /* 
        --------------------------------------------------
        Get view styles
        
        @return str
        --------------------------------------------------
        */
        public function getHtmlStyle()
        {
            $_html_style = [];
            
            if(!empty($this->html_style))
            {
                foreach($this->html_style as $style_rule => $style_value)
                {
                    $_html_style[] = $style_rule . ': ' . $style_value;
                }
            }
    
            return !empty($_html_style) ? implode('; ', $_html_style) : '';
        }

        /* 
        --------------------------------------------------
        Distribute cols
        
        @param array $items_html
        @param int $cols_num
        @param str $col_class
        
        @return str
        --------------------------------------------------
        */
        public function distributeCols($items_html, $cols_num=3, $col_class='lg')
        {
            if($cols_num === 5 || $cols_num > 6)
            {
                $cols_num = 6;
            }

            $html = '';

            if($cols_num === 1)
            {
                $html = implode('', $items_html);

                return $html;
            }
    
            $items_html_rows = array_chunk($items_html, $cols_num);
            $col_class = 'col-' . $col_class . '-' . 12/$cols_num;
    
            foreach($items_html_rows as $items_html_row)
            {
                $html .= '<div class="row">';
                foreach($items_html_row as $item_html)
                {
                    $html .= '<div class="' . $col_class . '">';
                        $html .= $item_html;
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
    
            return $html;
        }
    }
}