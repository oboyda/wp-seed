<?php

if(!function_exists('wpseed_get_view'))
{
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

    function wpseed_get_view($view_name, $args=[], $echo=false, $_views_dir=null, $_views_namespace=null)
    {
        $views_dir = isset($_views_dir) ? $_views_dir : apply_filters('wpseed_views_dir', get_stylesheet_directory() . '/views');
        $views_namespace = isset($_views_namespace) ? $_views_namespace : apply_filters('wpseed_views_namespace', '\View');

        $view_path = $views_dir . '/' . $view_name . '.php';
        $view_class = str_replace(' ', '_', ucwords(str_replace('-', ' ', $view_name)));
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
    function wpseed_get_file_class_name($file)
    {
        $file_name = basename($file);
        $file_name = substr($file_name, 0, strlen($file_name)-strlen('.php'));
        $file_name = str_replace('-', ' ', $file_name);
        $file_name = str_replace('_', ' ', $file_name);
        $file_name = ucwords($file_name);
        $file_name = str_replace(' ', '_', $file_name);
        
        return $file_name;
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
                
                $class_name =  $namespace . '\\' . wpseed_get_file_class_name($file);
                
                if(class_exists($class_name))
                {
                    new $class_name();
                }
            }
        }
    }
}
