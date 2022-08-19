<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Media'))
{
    class Media 
    {
        /**
         * 
         * @param type array $file: ['name' =>, 'type' => '', 'tmp_name' => '', 'error' => '', 'size' => '']
         * @param type string $save_name: name to be saved in /wp-content/uploads/[year]/[month]/$save_name
         * @param type int $parent_id
         */
        static function saveAttachment($file, $save_name, $author_id=0, $parent_id=0, $config_key=''){

            $attachment_id = 0;

            $upload_dir = wp_upload_dir();

            $save_name_unique = wp_unique_filename($upload_dir['path'], $save_name);
            $save_path = $upload_dir['path'] . '/' . $save_name_unique;

            $tmp_dir = sys_get_temp_dir();

            if(strpos($file['tmp_name'], $tmp_dir) === 0){
                move_uploaded_file($file['tmp_name'], $save_path);
            }else{
                copy($file['tmp_name'], $save_path);
                unlink($file['tmp_name']);
            }

            /* Insert attachment
             * -------------------------------------------------- */
            if(file_exists($save_path)){

                //$type_check = wp_check_filetype($save_name, null);
                //$type = $file_type_check['type'];
                $type = $file['type'];

                $args = array(
                    'guid' => $upload_dir['url'] . '/' . $save_name_unique, 
                    'post_mime_type' => $type,
                    'post_title' => $save_name,
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                if(!empty($author_id)){
                    $args['post_author'] = $author_id;
                }

                $attachment_id = wp_insert_attachment($args, $save_path, $parent_id);
            }

            /* Update attachment metadata
             * -------------------------------------------------- */
            if($attachment_id){
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $metadata = wp_generate_attachment_metadata($attachment_id, $save_path);
                wp_update_attachment_metadata($attachment_id, $metadata);

                /*if(!empty($config_key)){
                    update_post_meta($attachment_id, '_config_key', $config_key);
                }*/
            }

            return $attachment_id;
        }

        static function deleteAttachment($attachment_id, $author_id=0){

            /* Make checks if author is set
             * -------------------------------------------------- */
            if(!empty($author_id)){
                $attachment_post = get_post($attachment_id);
                if(empty($attachment_post) || intval($attachment_post->post_author) !== $author_id){
                    return false;
                }
            }

            return (bool) wp_delete_attachment($attachment_id, true);
        }

        static function manageFormAttachments($form, $author_id=0, $parent_id=0){

            $resp = [
                'saved' => [],
                'deleted' => []
            ];

            /* Check if should delete
             * -------------------------------------------------- */
            if(!empty($form['fields_config'])){
                foreach($form['fields_config'] as $key => $field_config){
                    if(isset($field_config['model_type']) && $field_config['model_type'] === 'attachment'){

                        $att_ids = Req::get($key . '_del');
                        if($att_ids){
                            $att_ids = is_array($att_ids) ? $att_ids : [$att_ids];
                            foreach($att_ids as $att_id){
                                $att_id = intval($att_id);
                                if(self::deleteAttachment($att_id, $author_id)){
                                //if(self::deleteAttachment($att_id)){
                                    $resp['deleted'][$key][] = $att_id;
                                }
                            }
                        }
                    }
                }
            }

            /* Insert new attachments
             * -------------------------------------------------- */
            if(!empty($form['files'])){
                foreach($form['files'] as $key => $files){
                    if(!empty($files)){
                        foreach($files as $i => $file){
                            $save_name = str_replace('_', '-', $key) . '-u' . $aid . '-' . $i . '.' . $file['ext'];
                            $attachment_id = self::saveAttachment($file, $save_name, $author_id, $parent_id);
                            if($attachment_id){
                                $resp['saved'][$key][] = $attachment_id;
                            }
                        }
                    }
                }
            }

            return $resp;
        }

        static function cropImage($img_path, $img_path_new, $crop_x, $crop_y, $crop_w, $crop_h){

            if(empty($crop_w) || empty($crop_h)){
                return false;
            }

            $editor = wp_get_image_editor($img_path);

            if(is_wp_error($editor)){
                return false;
            }

            $editor->maybe_exif_rotate();

            $img_size = $editor->get_size();

            $_crop_x = intval(round($crop_x * ($img_size['width']/100)));
            $_crop_y = intval(round($crop_y * ($img_size['height']/100)));

            $_crop_w = intval(round($crop_w * ($img_size['width']/100)));
            $_crop_h = intval(round($crop_h * ($img_size['height']/100)));

            if($img_size['width'] > ($_crop_x + $_crop_w) || $img_size['height'] > ($_crop_y + $_crop_h)){

                $cropped = $editor->crop($_crop_x, $_crop_y, $_crop_w, $_crop_h);

                if($cropped && !is_wp_error($cropped)){

                    $saved = $editor->save($img_path_new);

                    if(!is_wp_error($saved)){
                        return $img_path_new;
                    }
                }
            }

            return false;
        }

        static function resizeImage($img_path, $max_width=null, $max_height=null, $crop=false){

            if(empty($max_width) && empty($max_height)){
                return false;
            }

            $editor = wp_get_image_editor($img_path);

            if(is_wp_error($editor)){
                return false;
            }

            $resized = $editor->resize($max_width, $max_height, $crop);
            $saved = !is_wp_error($resized) ? $editor->save($img_path) : false;

            if(is_wp_error($saved)){
                return false;
            }

            return $saved;
        }
    }
}