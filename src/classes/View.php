<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\View'))
{
    class View {
        
        var $args;
        var $id;
        var $html_class;
        var $html_style;

        /*
        --------------------------------------------------
        Construct the View object

        @param array $args Arguments to be used in the template. Will be merged with the default arguments.
        @param array $args Default arguments to be used in the template.
        --------------------------------------------------
        */
        public function __construct($args=[], $default_args=[])
        {
            $default_args = array_merge([
                'view_cap' => 'public'
            ], $default_args);

            $this->args = array_merge($default_args, $args);

            $this->id = empty($this->args['id']) ? $this->genId() : $this->args['id'];

            $this->html_class = ['view'];
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
        Set $this->args[$name]
        --------------------------------------------------
        */
        protected function setArg($name, $value=null)
        {
            $this->args[$name] = $value;
        }

        /* 
        --------------------------------------------------
        Magic method for getting (get_[property_name]) and checking (has_[property_name]) object properties
        
        @return mixed
        --------------------------------------------------
        */
        public function __call($name, $props)
        {
            if(strpos($name, 'get_') === 0)
            {
                $var = substr($name, strlen('get_'));

                // return isset($this->args[$var]) ? $this->args[$var] : null;
                
                $v = isset($this->args[$var]) ? $this->args[$var] : null;

                if(isset($props[0]) && is_array($v) && isset($v[$props[0]])){
                    $v = $v[$props[0]];
                }

                return $v;
            }
            elseif(strpos($name, 'has_') === 0){
                
                $var = substr($name, strlen('has_'));

                // return isset($this->args[$var]) ? (is_bool($this->args[$var]) ? $this->args[$var] : !empty($this->args[$var])) : false;

                $v = isset($this->args[$var]) ? $this->args[$var] : null;

                if(isset($props[0]) && is_array($v) && isset($v[$props[0]])){
                    $v = $v[$props[0]];
                }
                
                // return is_bool($v) ? $v : ($v !== null);
                return is_bool($v) ? $v : !empty($v);
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
        @param int|array $cols_num
        @param str $col_class
        
        @return str
        --------------------------------------------------
        */
        public function distributeCols($items_html, $cols_num=3, $col_class='lg')
        {
            $html = '';

            $_cols_num = is_array($cols_num) ? array_merge([
                'num' => 0,
                'num_md' => 0,
                'num_lg' => 0
            ], $cols_num) : [
                'num' => ($col_class == '') ? $cols_num : 0,
                'num_md' => ($col_class == 'md') ? $cols_num : 0,
                'num_lg' => ($col_class == 'lg') ? $cols_num : 0
            ];

            $cols_num_max = max($_cols_num);

            if($cols_num_max === 1){
                $html = implode('', $items_html);
                return $html;
            }
    
            $_col_class = [];
            if($_cols_num['num']){
                $_col_class[] = 'col-' . 12/$_cols_num['num'];
            }
            if($_cols_num['num_md']){
                $_col_class[] = 'col-md-' . 12/$_cols_num['num_md'];
            }
            if($_cols_num['num_lg']){
                $_col_class[] = 'col-lg-' . 12/$_cols_num['num_lg'];
            }

            $items_html_rows = array_chunk($items_html, $cols_num_max);

            foreach($items_html_rows as $items_html_row)
            {
                $html .= '<div class="row">';
                foreach($items_html_row as $item_html)
                {
                    $html .= '<div class="' . implode(' ', $_col_class) . '">';
                        $html .= $item_html;
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
    
            return $html;
        }
    }
}