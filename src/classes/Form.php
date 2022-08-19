<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Form'))
{
    class Form 
    {
        static function validate($fields_config, $args=[]){

            $args = wp_parse_args($args, [
                'incl_fields' => [],
                'skip_fields' => [],
                'ignore_required' => false,
                'data' => null,
                'files' => null
            ]);

            return self::validateFields($fields_config, $args['incl_fields'], $args['skip_fields'], $args['ignore_required'], $args['data'], $args['files']);
        }

        static function validateFields($fields_config, $incl_fields=[], $skip_fields=[], $ignore_required=false, $data=[], $files=[]){

            $resp = [
                'fields' => [],
                'files' => [],
                'error_fields' => [],
                'errors' => [],
                'fields_config' => $fields_config
            ];

            if(!empty($fields_config)){
                foreach($fields_config as $key => $field_config){

                    /* Skip fields
                     * -------------------------------------------------- */
                    if(!empty($incl_fields) && !in_array($key, $incl_fields)){
                        continue;
                    }

                    if(!empty($skip_fields) && in_array($key, $skip_fields)){
                        continue;
                    }

                    $type = isset($field_config['type']) ? $field_config['type'] : 'text';
                    $required = isset($field_config['required']) ? $field_config['required'] : false;

                    $val = false;

                    if(isset($data)){
                        $val = isset($data[$key]) ? $data[$key] : false;
                    }else{
                        $val = Req::get($key, $type);
                    }

                    /* Add to errors if required and empty
                     * -------------------------------------------------- */
                    if($required && empty($val)){

                        if($ignore_required) continue;

                        $resp['error_fields'][] = $key;
                    }

                    /* Validate
                     * -------------------------------------------------- */
                    switch($type){

                        case 'email':

                            if(!type_var($val, FILTER_VALIDATE_EMAIL)){
                                $resp['error_fields'][] = $key;
                            }
                            break;

                        case 'file':

                            $file = false;

                            if(isset($files)){
                                $file = isset($files[$key]) ? $files[$key] : false;
                            }else{
                                $file = isset($_FILES[$key]) ? $_FILES[$key] : false;
                            }

                            /* Add to errors if required and empty
                             * -------------------------------------------------- */
                            if($required && empty($file) && !$skip_required){
                                $resp['error_fields'][] = $key;
                            }

                            $file = self::fileFieldToArrays($file);

                            foreach($file['name'] as $i => $file_name){

                                if(empty($file_name) && !$required){
                                    continue;
                                }

                                /* Check server errors
                                 * -------------------------------------------------- */
                                if(!empty($file['error'][$i])){
                                    $resp['error_fields'][] = $key;
                                    $resp['errors'][] = sprintf(__('%s failed to upload', 'ac'), $file_name);

                                    continue;
                                }

                                /* Validate type
                                 * -------------------------------------------------- */
                                if(isset($field_config['file_types']) && !in_array($file['type'][$i], $field_config['file_types'])){
                                    $resp['error_fields'][] = $key;
                                    $resp['errors'][] = sprintf(__('%s file type %s is not allowed', 'ac'), $file_name, $file['type'][$i]);
                                }

                                /* Validate size
                                 * -------------------------------------------------- */
                                if(isset($field_config['file_max_size']) && $file['size'][$i] > $field_config['file_max_size']){
                                    $resp['error_fields'][] = $key;
                                    $resp['errors'][] = sprintf(__('%s file size is not allowed', 'ac'), $file_name);
                                }

                                $resp['files'][$key][$i] = [
                                    'name' => $file_name,
                                    'tmp_name' => $file['tmp_name'][$i],
                                    'type' => $file['type'][$i],
                                    'size' => $file['size'][$i],
                                    'ext' => strtolower(pathinfo($file_name, PATHINFO_EXTENSION))
                                ];
                            }

                            /* Add del fields
                             * -------------------------------------------------- */
                            $del_key = $key . '_del';
                            $del_fields = Req::get($del_key);
                            if($del_fields){
                                $resp['fields'][$del_key] = $del_fields;
                            }

                            /* Skip when not required to check empty vars as for an update
                             * -------------------------------------------------- */
                            if(!$ignore_required && empty($resp['files'][$key])){
                                continue;
                            }

                            /* Add to errors if required and empty
                             * -------------------------------------------------- */
                            if($required && empty($resp['files'][$key])){
                                $resp['error_fields'][] = $key;
                            }

                            break;

                        default:

                            $resp['fields'][$key] = $val;
                    }
                }
            }

            $resp['error_fields'] = array_unique($resp['error_fields']);
            /*if(!empty($resp['error_fields']) && empty($resp['errors'])){
                $resp['errors'][] = __('Check the required fields', 'ac');
            }*/

            return $resp;
        }

        static function fileFieldToArrays($file){

            if(is_array($file['name'])){
                return $file;
            }

            return [
                'name' => [$file['name']],
                'type' => [$file['type']],
                'tmp_name' => [$file['tmp_name']],
                'error' => [$file['error']],
                'size' => [$file['size']]
            ];
        }

    }
}