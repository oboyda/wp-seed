<?php
namespace AC;

class Helpers {
    
    /*
     * ---------- ATTACHMENTS
     */
    
    static function getAttachmentSrc($attachment_id, $size){
        $image_src = wp_get_attachment_image_src($attachment_id, $size);
        if(isset($image_src[0])){
            return $image_src[0];
        }
        return false;
    }
    
    static function getAdditionalImageSize($slug){
        global $_wp_additional_image_sizes;
        if(isset($_wp_additional_image_sizes[$slug])){
            return array($_wp_additional_image_sizes[$slug]['width'], $_wp_additional_image_sizes[$slug]['height']);
        }
        return array(0, 0);
    }

    /*
     * ---------- LANGUAGE
     */
    
    static function getLang(){
        return (defined('ICL_LANGUAGE_CODE')) ? ICL_LANGUAGE_CODE : substr(get_locale(), 0, 2);
    }
    
    /*
     * ---------- TAXONOMIES
     */
    
    static function getTermLabel($term, $tax){
        $term_data = is_int($term) ? get_term($term, $tax) : get_term_by('slug', $term, $tax);
        if(isset($term_data->name)) return $term_data->name;
        return '';
    }

    static function getTermData($term, $tax, $data_key=null){
        $term_data = is_int($term) ? get_term($term, $tax) : get_term_by('slug', $term, $tax);
        if($term_data && !is_wp_error($term_data)){
            if(isset($data_key) && isset($term_data->$data_key)) return $term_data->$data_key;
            return $term_data;
        }
        return false;
    }

    static function getTermName($term, $tax){
        return self::getTermData($term, $tax, 'name');
    }
    
    static function getTermId($term, $tax){
        return self::getTermData($term, $tax, 'term_id');
    }
    
    static function getTermSlug($term, $tax){
        return self::getTermData($term, $tax, 'slug');
    }

    static function getTermParent($term_slug, $tax, $type='id'){
        $term_parents = get_ancestors(self::getTermId($term_slug, $tax), $tax, 'taxonomy');
        $term_parent = ($term_parents && !is_wp_error($term_parents)) ? $term_parents[0] : 0;
        if($term_parent && $type == 'slug') $term_parent = self::getTermSlug($term_parent, $tax);
        return $term_parent;
    }
    
    static function getTermChildren($taxonomy, $parent=0, $hide_empty=false){
        $parent = is_int($parent) ? $parent : self::getTermId($parent, $taxonomy);
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'parent' => $parent,
            'hide_empty' => $hide_empty,
            'orderby' => 'name'
            ));
        if($terms && !is_wp_error($terms)) return $terms;
        return false;
    }
    
    static function getPostLowestTerm($taxonomy, $post_id=null, $item_data=null, $skip_terms=[]){
        $post_id = isset($post_id) ? $post_id : get_the_ID();
        $post_terms = $post_id ? wp_get_post_terms($post_id, $taxonomy) : [];
        if(!is_wp_error($post_terms) && $post_terms){
            if(count($post_terms) == 1){
                if(isset($item_data)) return $post_terms[0]->$item_data;
                return $post_terms[0];
            }else{
                foreach($post_terms as $post_term1){
                    if($skip_terms && in_array($post_term1->slug, $skip_terms)) continue;
                    
                    $is_parent = false;
                    foreach($post_terms as $post_term2){
                        if($post_term1->term_id == $post_term2->parent) $is_parent = true;
                    }
                    if(!$is_parent){
                        if(isset($item_data)) return $post_term1->$item_data;
                        return $post_term1;
                    }
                }
            }
        }
        return false;
    }
    
    /*
     * ---------- SELECT OPTIONS
     */
    
    static function getTaxSelectOpts($tax, $parent=null){
        $opts = [];
        $terms_args = array(
            'taxonomy' => $tax,
            'hide_empty' => false,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'parent' => 0
            );
        if(isset($parent)){
            if(is_string($parent)){
                $parent = self::getTermId($parent, $tax);
            }
            $terms_args['parent'] = $parent;
        }
        $terms = get_terms($terms_args);
        if($terms && !is_wp_error($terms)){
            foreach($terms as $term){
                $opts[] = array(
                    'value' => urldecode($term->slug), 
                    'title' => $term->name,
                    'count' => $term->count
                    );
            }
        }
        return $opts;
    }
    
    static function getPostSelectOpts($q_args=[]){
        $opts = [];
        
        $q_args = wp_parse_args($q_args, array(
            'post_type' => 'post',
            'posts_per_page' => -1
            ));
        
        $posts_q = new \WP_Query($q_args);        
        
        if($posts_q->posts){
            foreach($posts_q->posts as $post){
                $opts[$post->ID] = $post->post_title;
            }
        }
        
        return $opts;
    }
    
    /*
     * ---------- USER
     */
    
    static function getUserRoles($user_id=null){
        if(!isset($user_id)) $user_id = get_current_user_id();
        if(!$user_id) return false;

        $user = get_userdata($user_id);
        if(!$user) return false;

        return $user->roles;
    }
    
    static function userIs($roles, $user_id=null){
        $user_roles = self::getUserRoles($user_id);
        if(!$user_roles) return false;

        if(!is_array($roles)) $roles = array($roles);

        foreach($roles as $role){
            if(in_array($role, $user_roles)){
                return true;
            }
        }

        return false;
    }
    
    static function getUserData($user_id, $prop){
        if($user_id && ($user = get_userdata($user_id)) && isset($user->$prop)){
            return $user->$prop;
        }
        return false;
    }
    
    /*
     * ---------- SCREEN
     */
    
    static function getAdminScreenId(){
        $screen = get_current_screen();
        return isset($screen) ? $screen->id : false;
    }
    
    /*
     * ---------- MISC
     */
    
    static function getDirFiles($dir_path, $exclude=[]){
        
        $files = [];
        $dir_files = file_exists($dir_path) ? scandir($dir_path) : false;
        
        if($dir_files){
            
            $exclude = array_merge($exclude, array('.', '..'));
            
            foreach($dir_files as $dir_file){
                
                if($exclude && in_array($dir_file, $exclude)){
                    continue;
                }
                
                $files[] = $dir_file;
            }
        }
        
        return $files;
    }
    
    static function removeDir($dir) {
        $files = self::getDirFiles();
        foreach($files as $file){
            $file_path = $dir . '/' . $file;
            if(is_dir($file_path)){
                self::removeDir($file_path);
            }else{
                unlink($file_path);
            }
        }
        return rmdir($dir);
    }
    
    static function getPageIdsByTpl($tpl, $first_found=false, $meta_check=[]){
        global $wpdb;
        
        $ids_q = $wpdb->get_col('SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key="_wp_page_template" AND meta_value="' . $tpl . '"');
        if($ids_q) array_walk($ids_q, function(&$item){ $item = (int)$item; });
        
        if($meta_check){
            $ids_q_mc = [];
            foreach($ids_q as $id){
                foreach($meta_check as $key => $val){
                    $meta_val = get_post_meta($id, $key, true);
                    if($meta_val == $val) $ids_q_mc[] = $id;
                }
            }
            $ids_q = $ids_q_mc;
        }
        
        if(!$ids_q) return false;
        if($first_found) return $ids_q[0];
        return $ids_q;
    }
    
    static function getRandomStr($length=10){
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_length = strlen($chars);
        $random_str = '';
        for($i = 0; $i < $length; $i++) {
            $random_str .= $chars[rand(0, $chars_length - 1)];
        }
        return $random_str;
    }
    
    static function getSysDate(){
        return date('Y-m-d', current_time('timestamp'));
    }
    
    static function hasShortcode($shortcode, $post_arg=null, $search_meta=null){
        global $post;
        $post_arg = isset($post_arg) ? $post_arg : $post;
        
        if(!isset($post_arg)) return false;
        
        if(isset($search_meta) && ($search_meta_val = get_post_meta($post_arg->ID, $search_meta, true))){
            $search_meta_val = is_array($search_meta_val) ? serialize($search_meta_val) : $search_meta_val;
            return (strpos($search_meta_val, $shortcode) !== false);
        }
        
        return ($post_arg->post_content && strpos($post_arg->post_content, $shortcode) !== false);
    }
    
    static function getDomain($no_www=true){
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
        if(!$server_name) return false;
        
        if($no_www && strpos($server_name, 'www.') === 0){
            $server_name = substr($server_name, strlen('www.'));
        }
        
        return $server_name;
    }
    
    static function addTrailSlash($str){
        if(substr($str, -1) !== '/'){
            $str .= '/';
        }
        return $str;
    }
    
    static function removeTrailSlash($str){
        if(substr($str, -1) == '/'){
            $str = substr($str, 0, -1);
        }
        return $str;
    }
    
    static function getUploadsDir(){
		$upload_dir = wp_upload_dir();
		return $upload_dir['path'];
	}
    
    static function getUploadsUrl(){
		$upload_dir = wp_upload_dir();
		return $upload_dir['url'];
	}
    
    static function getUploadsBaseDir(){
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'];
	}
    
    static function getUploadsBaseUrl(){
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'];
	}
    
    static function setDefaultArgs($args, $args_d){
        $args_n = [];
        if(!empty($args)){
            foreach($args_d as $k => $arg_d){
                if(empty($args[$k])){
                    $args_n[$k] = $arg_d;
                }else{
                    if(is_array($arg_d)){
                        $args_n[$k] = self::setDefaultArgs($args[$k], $arg_d);
                    }else{
                        $args_n[$k] = $args[$k];
                    }
                }
            }
        }
        return $args_n;
    }
    
    static function getExcerpt($content, $length=200, $more_link=' ...', $endchar=' '){
        $content = strip_tags(trim($content));
        if($content && strlen($content) > $length){
            $content = substr($content, 0, $length);
            $endchar_l = strlen($endchar);
            while(substr($content, -$endchar_l) != $endchar){
                $length--;
                $content = substr($content, 0, $length);
            }
            if($more_link != ''){
                $content .= $more_link;
            }
        }
        return $content;
    }

    static function formatDate($date){
        return date_i18n(get_option('date_format'), strtotime($date));
    }

    static function replaceRnBr($content, $repl='<br/>'){
        $content = str_replace(array("\r\n", "\r", "\n"), $repl, $content);
        return $content;
    }
    
    static function removeRn($content){
        if($content){
            $content = str_replace(array("\r\n", "\r", "\n"), ' ', $content);
        }
        return $content;
    }
    
    static function getProductCatImage($term_id, $size='thumbnail'){
        if(is_string($term_id)){
            $term_id = Helpers::getTermId($term_id, 'product_cat');
        }
        $tid = (int)get_term_meta($term_id, 'thumbnail_id', true);
        return $tid ? wp_get_attachment_image($tid, $size, false) : '';
    }
    
    static function getProductCatImageSrc($term_id, $size='thumbnail'){
        if(is_string($term_id)){
            $term_id = Helpers::getTermId($term_id, 'product_cat');
        }
        $tid = (int)get_term_meta($term_id, 'thumbnail_id', true);
        $src = $tid ? wp_get_attachment_image_src($tid, $size, false) : false;
        
        return isset($src[0]) ? $src[0] : false;
    }
}
