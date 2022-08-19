<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Comment'))
{
    class Comment {

        var $ID;
        var $wp_comment;
        var $type;
        var $meta;
        var $fields_config;

        public function __construct($comment=0, $fields_config=[]){

            $this->wp_comment = (is_int($comment) && $comment) ? get_comment($comment) : $comment;
            $this->ID = ($this->wp_comment && isset($this->wp_comment->comment_ID)) ? intval($this->wp_comment->comment_ID) : 0;

            $this->fields_config = $fields_config;

            if(!$this->ID){
                return;
            }

            $this->type = $this->wp_comment->comment_type;

            $this->meta = get_comment_meta($this->ID);
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
            $data = (array)$this->wp_comment;
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

        public function create($fields){

            $_fields = self::groupFieldsByModelType($fields, $this->fields_config);

            if(empty($_fields['data']['comment_content'])){
                return;
            }

            $_fields['data']['comment_type'] = $this->type;

            $created = wp_insert_comment($_fields['data']);
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

        public function update($fields){

            if(!$this->ID){
                return;
            }

            $_fields = self::groupFieldsByModelType($fields, $this->fields_config);

            if(!empty($_fields['data'])){
                $_fields['data']['ID'] = $this->ID;
                $updated = wp_update_comment($_fields['data']);
                if(!$updated || is_wp_error($updated)){
                    return;
                }
            }

            if(!empty($_fields['meta'])){
                foreach($_fields['meta'] as $key => $meta){
                    if($meta !== ""){
                        update_comment_meta($this->ID, $key, $meta);
                    }else{
                        delete_comment_meta($this->ID, $key);
                    }
                }
            }

            $this->__construct($this->ID);
        }

        public function delete(){

            if(!$this->ID){
                return false;
            }

            return wp_delete_comment($this->ID, true);
        }

    }
}