<?php

if(!function_exists('wpseed_get_view'))
{
    /*
     * Builds and returns/outputs the view template
     * 
     * Inside the template we use $view variable to reference to the view object. 
     * We access template arguments with $view->args;
     *
     * @param string $name Template name, relative to the views dir. Must NOT include .php at the end!
     * @param array $args Arguments to be passed to the view object. Will be merged with the default arguments.
     * @param bool $echo Whether to return or output the template
     * @return string|void 
     */

    function wpseed_get_view($name, $args=[], $echo=false)
    {
        $views_dir = apply_filters('wpseed_views_dir', get_stylesheet_directory() . '/views');
        $views_namespace = apply_filters('wpseed_views_namespace', '\View');

        $view_path = $views_dir . '/' . $name . '.php';
        $view_class = str_replace(' ', '_', ucwords(str_replace('-', ' ', $name)));
        $view_class_name = $views_namespace . '\\' . $view_class;
        
        if(class_exists($view_class_name))
        {
            $view = new $view_class_name($args);
        }
        else{
            
            $view = new \WPSEED\View($args);
        }
    
        if(file_exists($view_path))
        {
            if(!$echo)
            {
                ob_start();
            }
    
            include $view_path;
    
            if(!$echo)
            {
                $html = ob_get_contents();
                ob_end_clean();
    
                return $html;
            }
        }
    }
}

if(!function_exists('wpseed_print_view'))
{
    /*
     * Prints the view template
     * 
     * Inside the template we use $view variable to reference to the view object. 
     * We access template arguments with $view->args;
     * 
     * @param string $name Template name, relative to the views dir. Must NOT include .php at the end!
     * @param array $args Arguments to be passed to the view object. Will be merged with the default arguments.
     * @return void
     */

    function wpseed_print_view($name, $args=[])
    {
        wpseed_get_view($name, $args, true);
    }
}