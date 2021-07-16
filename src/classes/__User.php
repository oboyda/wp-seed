<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\User'))
{
    class User {

        var $ID;
        var $wp_user;
        var $role;
        var $meta;
        var $fields_config;

        public function __construct($user=0, $fields_config=[]){

            $this->wp_user = (is_int($user) && $user) ? get_userdata($user) : $user;
            $this->ID = ($this->wp_user && isset($this->wp_user->ID)) ? $this->wp_user->ID : 0;

            $this->fields_config = $fields_config;

            if(!$this->ID){
                return;
            }

            $this->role = $this->wp_user->roles[0];

            $this->meta = get_user_meta($this->ID);
        }

        static function selectFieldsConfig($fields_config, $incl_keys=[]){

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

        public function get($key, $default=null, $label=false){

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
            $data = (array)$this->wp_user->data;
            if(isset($data[$key])){
                $val = $data[$key];
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

            return $val;
        }

        public function getFieldConfig($key){

            return isset($this->fields_config[$key]) ? $this->fields_config[$key] : false;
        }

        public function getFieldConfigOptionLabel($key, $val){

            $field_config = $this->getFieldConfig($key);

            if(isset($field_config['options']) && isset($field_config['options'][$val])){
                return $field_config['options'][$val];
            }

            return $val;
        }

        static function groupFieldsByModelType($fields, $fields_config=[]){

            return Type::groupFieldsByModelType($fields, $fields_config);
        }

        public function create($fields, $files=[]){

            $_fields = self::groupFieldsByModelType($fields, $this->fields_config);

            if(empty($_fields['data']['user_email']) || empty($_fields['data']['user_pass'])){
                return;
            }
            if(empty($_fields['data']['user_login'])){
                $_fields['data']['user_login'] = $_fields['data']['user_email'];
            }
            if(empty($_fields['data']['user_nicename']) && isset($_fields['meta']['first_name'])){
                $_fields['data']['user_nicename'] = sanitize_title($_fields['meta']['first_name']);
            }
            if(empty($_fields['data']['display_name']) && isset($_fields['data']['user_nicename'])){
                $_fields['data']['display_name'] = $_fields['data']['user_nicename'];
            }

            $_fields['data']['role'] = $this->role;

            $created = wp_insert_user($_fields['data']);
            if(!$created || is_wp_error($created)){
                return;
            }
            $this->ID = $created;

            /* ----- unset data fields as they already have been inserted ----- */
            foreach(array_keys($_fields['data']) as $key){
                unset($_fields['data'][$key]);
            }

            $this->update($_fields, $files);
        }

        public function update($fields, $files=[]){

            if(!$this->ID){
                return false;
            }

            $_fields = self::groupFieldsByModelType($fields, $this->fields_config);

            if(!empty($_fields['data'])){

                foreach($_fields['data'] as $key => $data){
                    $this->wp_user->$key = $data;
                }

                $_fields['data']['ID'] = $this->ID;
                $updated = wp_update_user($_fields['data']);
                if(!$updated || is_wp_error($updated)){
                    return;
                }
            }

            if(!empty($_fields['meta'])){
                foreach($_fields['meta'] as $key => $meta){
                    if($meta !== ""){
                        $this->meta[$key][0] = $meta;
                        update_user_meta($this->ID, $key, $meta);
                    }else{
                        if(isset($this->meta[$key])){
                            unset($this->meta[$key]);
                        }
                        delete_user_meta($this->ID, $key);
                    }
                }
            }

            $attachments = $this->saveAttachments($_fields, $files);

            //$this->__construct($this->ID, $this->fields_config);

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

            return wp_delete_user($this->ID);
        }

        public function deleteChildren(){

            if(!$this->ID){
                return false;
            }

            $children_posts = get_posts([
                'author' => $this->ID,
                'post_status' => 'any',
                'post_type' => 'attachment',
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
                            $attachment_id = Media::saveAttachment($file_item, $save_name, $author_id, $this->ID);
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
                        update_user_meta($this->ID, $key, $attachment_ids_meta);
                    }else{
                        $this->meta[$key][0] = $attachment_ids[0];
                        update_user_meta($this->ID, $key, $attachment_ids[0]);
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
                            delete_user_meta($this->ID, $key);
                        }else{
                            $this->meta[$key][0] = $attachment_ids_meta;
                            update_user_meta($this->ID, $key, $attachment_ids_meta);
                        }
                    }else{
                        $attachment_ids_meta = intval($this->get($key));
                        if($attachment_ids_meta === $attachment_ids[0]){
                            if(isset($this->meta[$key])){
                                unset($this->meta[$key]);
                            }
                            delete_user_meta($this->ID, $key);
                        }
                    }
                }
            }

            return $resp;
        }

    }
}