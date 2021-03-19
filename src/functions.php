<?php

$_ac_vid = 0;

function ac_get_view($name, $args=[], $echo=false, $namespace='\AC\View', $views_dir=''){
    global $_ac_vid;
    
    $view_id = '';
    
    if(empty($args['view_id'])){
        $_ac_vid += 1;
        $view_id = 'ac' . $_ac_vid;
    }else{
        $view_id = $args['view_id'];
    }
    
    $view_class = 'view view-' . $name;
    
    if(isset($args['class'])){
        $view_class .= ' ' . $args['class'];
    }
    
    $fields = isset($args['fields']) ? $args['fields'] : [];
    
    $basename = basename($name);
    $class = $namespace . '\\' . ucwords(str_replace('-', '_', $basename), '_');
    $view = class_exists($class) ? new $class($args) : new \AC\View($args);
    
    if(isset($view) && isset($view->args)){
        $args = $view->args;
    }
    
    $view_path = $views_dir . '/' . $name . '.php';
    
    if(!$echo){
        ob_start();
    }
    
    if(file_exists($view_path)){
        include($view_path);
    }
    
    if(!$echo){
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}

function ac_debug($debug, $append=false){
    
    $path = ABSPATH . '/__debug.txt';
    
    if($append){
        if(is_array($debug) || is_object($debug)){
            file_put_contents($path, print_r($debug, true), FILE_APPEND);
        }else{
            file_put_contents($path, $debug, FILE_APPEND);
        }
    }else{
        if(is_array($debug) || is_object($debug)){
            file_put_contents($path, print_r($debug, true));
        }else{
            file_put_contents($path, $debug);
        }
    }
    
}
