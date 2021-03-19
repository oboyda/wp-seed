<?php
namespace AC;

class Type {
    
    var $ID;
    
    var $fields_config;
    
    var $meta;
    var $post;
    var $post_type;
    
    var $link;
    var $edit_link;
    
    public function __construct($post=0, $fields_config=[]){
        
        $this->post = (is_int($post) && $post) ? get_post($post) : $post;
        $this->ID = ($this->post && isset($this->post->ID)) ? $this->post->ID : 0;
        
        $this->fields_config = $fields_config;
        
        if(!$this->ID){
            return;
        }
        
        $this->post_type = $this->get('post_type');
        
        $this->meta = get_post_meta($this->ID);
        
        $this->link = get_permalink($this->ID);
        $this->edit_link = get_edit_post_link($this->ID);
    }
    
    static function selectFieldsConfig($fields_config=null, $incl_keys=[]){
        
        if(!isset($fields_config)){
            $fields_config = $this->fields_config;
        }
        
        if(empty($incl_keys)){
            return $fields_config;
        }
        
        $selected = [];
        
        foreach($incl_keys as $key){
            if(isset($fields_config[$key])){
                $selected[$key] = $fields_config[$key];
            }
        }
        
        return $selected;
    }
    
    public function getFieldConfig($key, $check_sys_key=false){
        
        $field_config = isset($this->fields_config[$key]) ? $this->fields_config[$key] : [];
        
        return (empty($field_config) && $check_sys_key) ? $this->selectFieldConfigByAtt('sys_key', $key) : $field_config;
    }
    
    public function selectFieldConfigsByAtt($att, $att_val, $incl_keys=[]){
        
        $selected = [];
        
        if(!empty($this->fields_config)){
            foreach($this->fields_config as $key => $field_config){
                
                if(!empty($incl_keys) && !in_array($key, $incl_keys)){
                    continue;
                }
                
                if(isset($field_config[$att]) && $field_config[$att] == $att_val){
                    $selected[$key] = $field_config;
                }
            }
        }
        
        return $selected;
    }
    
    public function selectFieldConfigByAtt($att, $att_val){
        
        $selected = [];
        
        if(!empty($this->fields_config)){
            foreach($this->fields_config as $key => $field_config){
                if(isset($field_config[$ckey]) && $field_config[$ckey] === $val){
                    $selected = $field_config;
                }
            }
        }
        
        return $selected;
    }
    
    public function getFieldConfigOptionLabel($key, $val, $default=null){
        
        $field_config = $this->getFieldConfig($key);
        
        if(isset($field_config['options']) && isset($field_config['options'][$val])){
            $val = $field_config['options'][$val];
        }elseif(isset($default)){
            $val = $default;
        }
        
        return $val;
    }
    
    public function getLink(){
        
        return $this->link;
    }
    
    public function getTitle($default=null){
        
        return $this->get('post_title', $default);
    }
    
    public function getContent($default=null){
        
        return $this->get('post_content', $default);
    }
    
    public function getAuthor(){
        
        return intval($this->get('post_author'));
    }
    
    public function getImages($size='large'){
        
        $images = array();
        
        if(!$this->ID){
            return $images;
        }
        
        $image_posts = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_mime_type' => 'image',
            'post_parent' => $this->ID,
            'post_status' => 'any',
            'order' => 'ASC',
            'orderby' => 'menu_order'
        ));
        
        if($image_posts){
            foreach($image_posts as $image_post){
                $image = array(
                    'id' => $image_post->ID,
                    'src' => Helpers::getAttachmentSrc($image_post->ID, $size),
                    'html' => wp_get_attachment_image($image_post->ID, $size)
                );
                $images[] = $image;
            }
        }
        
        return $images;
    }
    
    public function getFeaturedImage($size='large'){
        
        if(!$this->ID){
            return false;
        }
        
        $thumbnail_id = get_post_thumbnail_id($this->ID);
        if($thumbnail_id){
            
            return array(
                'id' => $thumbnail_id,
                'src' => Helpers::getAttachmentSrc($thumbnail_id, $size),
                'html' => wp_get_attachment_image($thumbnail_id, $size)
            );
        }
        
        return false;
    }
    
    public function get($key, $default=null, $label=false, $format=false){
        
        $val = false;
        
        if(!$this->ID){
            return isset($default) ? $default : $val;
        }
        
        $field_config = $this->getFieldConfig($key);
        
        if(isset($field_config['sys_key'])){
            $key = $field_config['sys_key'];
        }
        
        if($key === 'ID'){
            return $this->ID;
        }
        
        //Get from data
        $post = (array)$this->post;
        if(isset($post[$key])){
            $val = $post[$key];
        }
        
        //Get from meta
        if($val === false){
            $val = isset($this->meta[$key]) ? maybe_unserialize($this->meta[$key][0]) : false;
        }
                
        $val = (isset($default) && empty($val)) ? $default : $val;
        
        if($label){
            if(isset($field_config['options'])){
                return isset($field_config['options'][$val]) ? $field_config['options'][$val] : $val; 
            }
            if(isset($field_config['taxonomy'])){
                $term = get_term_by('slug', $val, $field_config['taxonomy']);
                return $term ? $term->name : $val;
            }
        }
        
        if($format && isset($field_config['display_format']) && !empty($val)){
            
            if(is_array($val) && isset($val[1])){
                $val = @sprintf($field_config['display_format'], $val[0], $val[1]);
            }else{
                $val = @sprintf($field_config['display_format'], $val);
            }
            
        }
        
        return $val;
    }
    
    public function collectData(){
        
        $data = [];
        
        if(!$this->ID){
            return $data;
        }
        
        if($this->fields_config){
            foreach(array_keys($this->fields_config) as $key){
                $data[$key] = $this->get($key);
            }
        }
        
        $data['link'] = $this->link;
        $data['images'] = $this->getImages();
        $data['feat_image'] = $this->getFeaturedImage();
        
        return array_merge((array)$this->post, $data);
    }
    
    static function groupFieldsByModelType($fields, $fields_config=[]){
        
        $_fields = [
            'data' => [],
            'meta' => [],
            'attachment_del' => []
        ];
        
        /* Check if not already grouped
         * -----------------------------------
         */
        if(!empty($fields) && !empty($fields_config)){
            
            if(isset($fields['data']) || isset($fields['meta']) || isset($fields['attachment_del'])){
                return $fields;
            }
            
            foreach($fields_config as $key => $field_config){
                $model_type = isset($field_config['model_type']) ? $field_config['model_type'] : 'data';
                if(isset($fields[$model_type])){
                    return $fields;
                }
            }
        }
        
        if(!empty($fields_config)){
            foreach($fields_config as $key => $field_config){
                
                if(isset($field_config['save']) && !$field_config['save']){
                    continue;
                }
                
                $model_type = isset($field_config['model_type']) ? $field_config['model_type'] : 'data';
                $sys_key = isset($field_config['sys_key']) ? $field_config['sys_key'] : $key;
                
                if(!isset($_fields[$model_type])){
                    $_fields[$model_type] = [];
                }
                
                if(isset($fields[$key])){
                    $_fields[$model_type][$sys_key] = $fields[$key];
                }
                
                if($model_type == 'attachment'){
                    $field_del = Req::get($key . '_del', 'integer');
                    if($field_del){
                        $_fields['attachment_del'][$sys_key] = $field_del;
                    }
                }
            }
        }
        
        return $_fields;
    }
    
    public function create($fields, $files=[]){
        
        $_fields = self::groupFieldsByModelType($fields, $this->fields_config);
        
        if(empty($_fields['data']['post_title'])){
            $_fields['data']['post_title'] = 'Post title';
        }
        if(empty($_fields['data']['post_content'])){
            $_fields['data']['post_content'] = '&nbsp;';
        }
        $_fields['data']['post_type'] = $this->post_type;
        
        $created = wp_insert_post($_fields['data']);
        if(is_wp_error($created)){
            return;
        }
        $this->ID = $created;
        
        /* ----- unset data fields as they already have been inserted ----- */
        foreach(array_keys($_fields['data']) as $key){
            unset($_fields['data'][$key]);
        }
        
        return $this->update($_fields, $files);
    }
    
    public function update($fields, $files=[]){
        
        if(!$this->ID){
            return;
        }
        
        $_fields = self::groupFieldsByModelType($fields, $this->fields_config);
        
        if(!empty($_fields['data'])){
            
            foreach($_fields['data'] as $key => $data){
                $this->post->$key = $data;
            }
            
            $_fields['data']['ID'] = $this->ID;
            $updated = wp_update_post($_fields['data']);
            if(is_wp_error($updated)){
                return;
            }
        }
        
        if(!empty($_fields['meta'])){
            foreach($_fields['meta'] as $key => $meta){
                
                $field_config = $this->getFieldConfig($key, true);
                $input_type = isset($field_config['input_type']) ? $field_config['input_type'] : 'text';
                
                if($meta !== ""){
                    $this->meta[$key][0] = $meta;
                    update_post_meta($this->ID, $key, $meta);
                }else{
                    if(isset($this->meta[$key])){
                        unset($this->meta[$key]);
                    }
                    delete_post_meta($this->ID, $key);
                }
                
                if(in_array($input_type, ['range_text', 'range_number']) && is_array($meta)){
                    
                    $meta_0 = isset($meta[0]) ? $meta[0] : '';
                    
                    if($meta_0 !== ""){
                        $this->meta[$key . '_0'][0] = $meta_0;
                        update_post_meta($this->ID, $key . '_0', $meta_0);
                    }else{
                        if(isset($this->meta[$key . '_0'])){
                            unset($this->meta[$key . '_0']);
                        }
                        delete_post_meta($this->ID, $key . '_0');
                    }
                    
                    $meta_1 = isset($meta[1]) ? $meta[1] : '';
                    
                    if($meta_1 !== ""){
                        $this->meta[$key . '_1'][0] = $meta_1;
                        update_post_meta($this->ID, $key . '_1', $meta_1);
                    }else{
                        if(isset($this->meta[$key . '_1'])){
                            unset($this->meta[$key . '_1']);
                        }
                        delete_post_meta($this->ID, $key . '_1');
                    }
                    
                }

            }
        }
        
        if(!empty($_fields['taxonomy'])){
            foreach($_fields['taxonomy'] as $taxonomy => $terms){
                wp_set_object_terms($this->ID, $terms, $taxonomy, false);
            }
        }
        
        //$this->__construct($this->ID);
        
        $attachments = $this->saveAttachments($_fields, $files);
        
        return [
            'attachments' => $attachments
        ];
    }
    
    public function delete($delete_children=false){
        
        if(!$this->ID){
            return false;
        }
        
        if($delete_children){
            $this->deleteChildren();
        }
        
        return wp_delete_post($this->ID, true);
    }
    
    public function deleteChildren(){
        
        if(!$this->ID){
            return false;
        }
        
        $children_posts = get_posts([
            'post_parent' => $this->ID,
            'post_status' => 'any',
            'posts_per_page' => -1
        ]);

        if(!empty($children_posts)){
            foreach($children_posts as $children_post){
                wp_delete_post($children_post->ID, true);
            }
        }
    }
    
    public function saveAttachments($fields, $files, $update_meta=true){
        
        $resp = [
            'saved' => [],
            'deletd' => []
        ];
        
        if(!$this->ID){
            return $resp;
        }
        
        $_fields = self::groupFieldsByModelType($fields, $this->fields_config);
        
        $author_id = intval($this->get('post_author'));
        
        if(!empty($_fields['attachment_del'])){
            foreach($_fields['attachment_del'] as $key => $field){
                if(!is_array($field)){
                    $field = [$field];
                }
                foreach($field as $attachment_id){
                    $attachment_id = intval($attachment_id);
                    if(Media::deleteAttachment($attachment_id, $author_id)){
                        $resp['deleted'][$key][] = $attachment_id;
                    }
                }
            }
        }
        
        if(!empty($files)){
            foreach($files as $key => $file){
                if(!empty($file)){
                    foreach($file as $i => $file_item){
                        $save_name = str_replace('_', '-', $key) . '-p' . $this->ID . '-u' . $author_id . '-' . $i . '.' . $file_item['ext'];
                        $attachment_id = Media::saveAttachment($file_item, $save_name, $author_id, $this->ID, $key);
                        if($attachment_id){
                            $resp['saved'][$key][] = $attachment_id;
                        }
                    }
                }
            }
        }
        
        if(!$update_meta){
            return $resp;
        }
        
        if(!empty($resp['saved'])){
            foreach($resp['saved'] as $key => $attachment_ids){
                if(!empty($this->fields_config[$key]['multiple'])){
                    $attachment_ids_meta = array_merge($attachment_ids, $this->get($key, []));
                    $this->meta[$key][0] = $attachment_ids_meta;
                    update_post_meta($this->ID, $key, $attachment_ids_meta);
                }else{
                    $this->meta[$key][0] = $attachment_ids[0];
                    update_post_meta($this->ID, $key, $attachment_ids[0]);
                }
            }
        }
        
        if(!empty($resp['deleted'])){
            foreach($resp['deleted'] as $key => $attachment_ids){
                if(!empty($this->fields_config[$key]['multiple'])){
                    $attachment_ids_meta = array_diff($this->get($key, []), $attachment_ids);
                    if(empty($attachment_ids_meta)){
                        if(isset($this->meta[$key])){
                            unset($this->meta[$key]);
                        }
                        delete_post_meta($this->ID, $key);
                    }else{
                        $this->meta[$key][0] = $attachment_ids_meta;
                        update_post_meta($this->ID, $key, $attachment_ids_meta);
                    }
                }else{
                    $attachment_ids_meta = intval($this->get($key));
                    if($attachment_ids_meta === $attachment_ids[0]){
                        if(isset($this->meta[$key])){
                            unset($this->meta[$key]);
                        }
                        delete_post_meta($this->ID, $key);
                    }
                }
            }
        }
        
        return $resp;
    }
    
}
