<?php

if(!function_exists('wpseed_get_view'))
{
    /*
     * Builds the view object
     * 
     * @param string $view_name Template name, relative to the views dir. Must NOT include .php at the end!
     * @param array $args Arguments to be passed to the view object. Will be merged with the default arguments.
     * @return string|void 
     */

    function wpseed_get_view_object($view_name, $args=[], $view_dir=null, $view_namespace=null)
    {
        $_view_dir = isset($view_dir) ? $view_dir : apply_filters('wpseed_views_dir', get_stylesheet_directory() . '/views');
        $_view_namespace = isset($view_namespace) ? $view_namespace : apply_filters('wpseed_views_namespace', '\View');

        $view_class = str_replace(' ', '_', ucwords(str_replace('-', ' ', $view_name)));
        $view_class_name = $view_namespace . '\\' . $view_class;

        $view = class_exists($view_class_name) ? new $view_class_name($args) : new \WPSEED\View($args);

        return $view;
    }
    
    /*
     * Builds and returns/outputs the view template
     * 
     * Inside the template we use $view variable to reference to the view object. 
     * We access template arguments with $view->args;
     *
     * @param string $view_name Template name, relative to the views dir. Must NOT include .php at the end!
     * @param array $args Arguments to be passed to the view object. Will be merged with the default arguments.
     * @param bool $echo Whether to return or output the template
     * @return string|void 
     */

    function wpseed_get_view($view_name, $args=[], $echo=false, $view_dir=null, $view_namespace=null)
    {
        $_view_dir = isset($view_dir) ? $view_dir : apply_filters('wpseed_views_dir', get_stylesheet_directory() . '/views');
        $view_path = $_view_dir . '/' . $view_name . '.php';

        $view = wpseed_get_view_object($view_name, $args, $view_dir, $view_namespace);
    
        if(file_exists($view_path) || method_exists($view, 'renderHtml'))
        {
            if(!$echo)
            {
                ob_start();
            }
    
            if(isset($view) && (isset($view->args['view_cap']) && ($view->args['view_cap'] === 'public' || current_user_can($view->args['view_cap']))))
            {
                if(method_exists($view, 'renderHtml')){
                    $view->renderHtml();
                }else{
                    include $view_path;
                }
            }
    
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
     * @param string $view_name Template name, relative to the views dir. Must NOT include .php at the end!
     * @param array $args Arguments to be passed to the view object. Will be merged with the default arguments.
     * @return void
     */

    function wpseed_print_view($view_name, $args=[])
    {
        wpseed_get_view($view_name, $args, true);
    }
}

if(!function_exists('wpseed_get_dir_files'))
{
    /*
     * Retrieves dir files
     * 
     * @param $dir string
     * @param $full_path bool
     * @param $skip_dirs bool
     * @return array
     */
    function wpseed_get_dir_files($dir, $full_path=true, $skip_dirs=true)
    {
        $dir = rtrim($dir, '/');
        
        $files = [];
        
        $scan_files = scandir($dir);

        foreach($scan_files as $file)
        {
            if(!in_array($file, ['.', '..']) && substr($file, 0, 2) !== '__')
            {
                $file_path = $dir . '/' . $file;

                if($skip_dirs && is_dir($file_path))
                {
                    continue;
                }

                $files[] = $full_path ? $file_path : $file;
            }
        }
        
        return $files;
    }
}

if(!function_exists('wpseed_require_dir_files'))
{
    /*
     * Includes directory files
     * 
     * @param $dir string
     * @return void
     */
    function wpseed_require_dir_files($dir)
    {
        $files = wpseed_get_dir_files($dir, true, true);
        
        if($files)
        {
            foreach($files as $file)
            {
                require_once $file;
            }
        }
    }
}

if(!function_exists('wpseed_get_file_class_name'))
{
    /*
     * Get class name from file name
     * 
     * @param $file string
     * @return string
     */
    function wpseed_get_file_class_name($file, $namespace='')
    {
        $file_name = basename($file);
        $file_name = substr($file_name, 0, strlen($file_name)-strlen('.php'));
        $file_name = str_replace('-', ' ', $file_name);
        $file_name = str_replace('_', ' ', $file_name);
        $file_name = ucwords($file_name);
        $file_name = str_replace(' ', '_', $file_name);

        if($namespace)
        {
            $file_name = $namespace . '\\' . $file_name;
        }
        
        return $file_name;
    }
}

if(!function_exists('wpseed_get_dir_classes'))
{
    /*
     * List dir classes
     * 
     * @param $dir string
     * @param $namespace string
     * @return array
     */
    function wpseed_get_dir_classes($dir, $namespace='')
    {
        $dir_classes = [];

        $files = wpseed_get_dir_files($dir);
        
        if($files)
        {
            foreach($files as $file)
            {
                $dir_classes[] = wpseed_get_file_class_name($file, $namespace);
            }
        }

        return $dir_classes;
    }
}

if(!function_exists('wpseed_load_dir_classes'))
{
    /*
     * Load dir files and instantiates classes
     * 
     * @param $dir string
     * @param $namespace string
     * @param $load_files bool
     * @return void
     */
    function wpseed_load_dir_classes($dir, $namespace='', $load_files=false)
    {
        $files = wpseed_get_dir_files($dir);
        
        if($files)
        {
            foreach($files as $file)
            {
                if($load_files)
                {
                    require_once $file;
                }
                
                $class_name =  wpseed_get_file_class_name($file, $namespace);
                
                if(class_exists($class_name))
                {
                    new $class_name();
                }
            }
        }
    }
}
