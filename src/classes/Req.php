<?php
namespace AC;

class Req {
    
    static function get($key, $san_type='text', $default=null){
        global $_ac_req;
        
        if(isset($_ac_req) && isset($_ac_req[$key])) return $_ac_req[$key];
        
        $val = isset($_REQUEST[$key]) ? $_REQUEST[$key] : false;
        
        if($val !== false){
            if(is_array($val)){
                array_walk_recursive($val, 'self::sanitizeReqArr', $san_type);
            }else{
                $val = self::sanitizeReq(urldecode($val), $san_type);
            }
        }
        
        $_ac_req[$key] = $val;
        
        return (empty($val) && isset($default)) ? $default : $val;
    }
    
    static function getAll(){
        $_ac_req = array();
        if(isset($_REQUEST) && $_REQUEST){
            foreach(array_keys($_REQUEST) as $key){
                $_ac_req[$key] = self::getReq($key);
            }
        }
        return $_ac_req;
    }
    
    static function sanitizeReq($val_item, $san_type='text'){
        
        $val_item = trim($val_item);
        
        switch($san_type){
            case 'text':
                $val_item = sanitize_text_field($val_item);
                break;
            case 'textarea':
                $val_item = sanitize_textarea_field($val_item);
                break;
            case 'integer':
                $val_item = intval($val_item);
                break;
            case 'floatval':
                $val_item = floatval($val_item);
                break;
        }
        
        return $val_item;
    }
    
    static function sanitizeReqArr(&$val_item, $san_type='text', $udec=false){
        if($udec) $val_item = urldecode($val_item);
        $val_item = self::sanitizeReq($val_item, $san_type);
    }
    
    static function getNoparamRequestUri($uri){
        $qp = strpos($uri, '?');
        if($qp !== false) $uri = substr($uri, 0, $qp);
        return $uri;
    }

    static function modifyRequestUri($args=array()){
        $args = wp_parse_args($args, array(
            'add_params' => array(),
            'del_params' => array(),
            'reset_params' => true,
            'req_uri' => null
        ));
        
        $params = $_GET;
        $params_str = '';
        if(!$args['req_uri']){
            $args['req_uri'] = $_SERVER['REQUEST_URI'];
        }
        if($args['req_uri'] && strpos($args['req_uri'], '?') && !$args['reset_params']){
            $params_str = substr($args['req_uri'], strpos($args['req_uri'], '?')+1);
            if($params_str){
                parse_str($params_str, $params);
            }
        }
        if($args['req_uri']){
            $args['req_uri'] = self::getNoparamRequestUri($args['req_uri']);
        }
        if($args['reset_params']){
            $params = array();
        }
        if($args['add_params']){
            foreach($args['add_params'] as $key => $val){
                $params[$key] = $val;
            }
        }
        if($args['del_params']){
            foreach($args['del_params'] as $key){
                if(isset($params[$key])){
                    unset($params[$key]);
                }
            }
        }
        if($params){
            $params_str = '?';
            foreach($params as $key => $val){
                if(strlen($params_str) > 1){
                    $params_str .= '&';
                }
                $params_str .= $key;
                if(isset($val) && $val !== ''){
                    $params_str .= '=' . urlencode($val);
                }
            }
        }
        return $args['req_uri'] . $params_str;
    }
    
    static function getReqUri(){
        $req_uri = '';
        if(isset($_SERVER['REQUEST_SCHEME'])) $req_uri .= $_SERVER['REQUEST_SCHEME'] . '://';
        if(isset($_SERVER['SERVER_NAME'])) $req_uri .= $_SERVER['SERVER_NAME'];
        if(isset($_SERVER['REQUEST_URI'])) $req_uri .= $_SERVER['REQUEST_URI'];
        return $req_uri;
    }
    
    static function isAjax($action=''){
        $is_ajax = wp_doing_ajax();
        return ($action && $is_ajax && self::getReq($action)) ? true : $is_ajax;
    }
    
    static function respondJson($status=false, $args=array()){
        $args = wp_parse_args($args, array(
            'status_only' => false,
            'error_fields' => array(),
            'error_messages' => array(),
            'error_messages_html' => '',
            'ok_messages' => array(),
            'ok_messages_html' => '',
            'values' => array(),
            'redirect' => '',
            'reload' => false,
            'reset_form' => false
            ));
        if(!is_array($args['error_messages'])){
            $args['error_messages'] = array($args['error_messages']);
        }
        if(!is_array($args['ok_messages'])){
            $args['ok_messages'] = array($args['ok_messages']);
        }

        header('Content-Type: application/json');
        $response = array(
                            'status' => (int)$status,
                            'errorFields' => $args['error_fields'],
                            'errorMessages' => $args['error_messages'],
                            'errorMessagesHtml' => $args['error_messages_html'],
                            'okMessages' => $args['ok_messages'],
                            'okMessagesHtml' => $args['ok_messages_html'],
                            'values' => $args['values'],
                            'redirect' => $args['redirect'],
                            'reload' => (int)$args['reload'],
                            'resetForm' => (int)$args['reset_form']
                            );
        if($response['errorMessagesHtml'] == '' && $response['errorMessages']){
            $response['errorMessagesHtml'] = implode('<br />', $response['errorMessages']);
        }
        if($response['okMessagesHtml'] == '' && $response['okMessages']){
            $response['okMessagesHtml'] = implode('<br />', $response['okMessages']);
        }
        
        if($args['status_only']){
            $response = array('status' => $response['status']);
        }
        
        echo json_encode($response);
        die();
    }
    
    static function post($endpoint, $args=array()){
        
        $args = wp_parse_args($args, array(
            'headers' => array(),
            'body' => array(),
            'timeout' => 20,
            'request_json' => false,
            'response_json' => false
        ));
        
        if($args['request_json']){
            $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
            if(is_array($args['body'])){
                $args['body'] = json_encode($args['body']);
            }
        }/*else{
            $args['body'] = http_build_query($args['body']);
        }*/
        
        $resp = wp_remote_post($endpoint, $args);
        
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        
        $body = ($code == 200 && $body) ? $body : false;
        
        if($body && $args['response_json']){
            $body = json_decode($body, true);
        }
        
        return $body;
    }
    
    static function getQueriedTerm(){
        if(!is_tax() && !is_category()) return false;
        $q_obj = get_queried_object();
        if(!isset($q_obj->taxonomy) || !isset($q_obj->slug)) return false;
        return array(
            'taxonomy' => $q_obj->taxonomy, 
            'term' => $q_obj->slug
            );
    }
    
    static function getQueriedAuthorId(){
        if(!is_author()) return false;
        $q_obj = get_queried_object();
        if(get_class($q_obj) !== 'WP_User' || !isset($q_obj->ID)) return false;
        return $q_obj->ID;
    }
    
    static function getPostEditArgs(){
        
        $args = array(
            'post_id' => 0,
            'post_type' => 'post',
            'template' => ''
        );
        
        if($post_id = self::get('post', 'integer')){
            $args['post_id'] = $post_id;
        }elseif($post_id = self::get('post_ID', 'integer')){
            $args['post_id'] = $post_id;
        }elseif($post_id = self::get('post_id', 'integer')){
            $args['post_id'] = $post_id;
        }
        
        if($post_type = self::get('post_type')){
            $args['post_type'] = $post_type;
        }else{
            $args['post_type'] = get_post_type($args['post_id']);
        }
        
        if($args['post_type'] == 'page' && $args['post_id']){
            $args['template'] = get_page_template_slug($args['post_id']);
        }
        
        return $args;
    }
}
