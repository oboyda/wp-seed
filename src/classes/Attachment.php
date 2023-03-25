<?php

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Attachment'))
{
    class Attachment extends Post
    {
        protected $file_data;
        protected $metadata;
        protected $base_url;
        protected $base_dir;

        /*
        --------------------------------------------------
        Construct the Attachment object

        @param object|int $post WP_Post instance or post ID.
        @param array $file_data Provide if you are lookig to submit a new file. 
        $file_data expects the following keys as specified in $_FILES array: [
            'name' => 'MyImage.png',
            'type' => 'image/png,
            'tmp_name' => /tmp/php/php1h4j1o
            'error' => 0
            'size' => 100000
        ]

        @return void
        --------------------------------------------------
        */
        public function __construct($post=null, $props_config=[], $parent_id=0, $file_data=[])
        {
            parent::__construct($post, $props_config);

            $this->set_parent_id($parent_id);
            $this->set_file_data($file_data);

            $this->set_prop_types(['data', 'meta']);

            $this->init_metadata();
        }

        /*
        --------------------------------------------------
        Init & setter methods
        --------------------------------------------------
        */
        protected function init_metadata()
        {
            $this->metadata = $this->id ? wp_get_attachment_metadata($this->id) : [];

            // $upload_dir = wp_get_upload_dir();
            $upload_dir = wp_upload_dir();

            if(isset($upload_dir['url'])) $this->base_url = $upload_dir['url'];
            if(isset($upload_dir['path'])) $this->base_dir = $upload_dir['path'];
        }

        public function set_file_data($file_data)
        {
            $this->file_data = $file_data;
        }

        /*
        --------------------------------------------------
        Get Attachment file name

        @param string $size

        @return string
        --------------------------------------------------
        */
        protected function get_file($size='full')
        {
            $file = '';

            if($size == 'full' && isset($this->metadata['file']))
            {
                $file = basename($this->metadata['file']);
            }
            elseif(isset($this->metadata['sizes']) && isset($this->metadata['sizes'][$size]))
            {
                $file = $this->metadata['sizes'][$size]['file'];
            }

            return $file;
        }

        /*
        --------------------------------------------------
        Get Attachment file path

        @param string $size
        @param mixed $default Default value to return

        @return string
        --------------------------------------------------
        */
        public function get_path($size='full', $default=null)
        {
            $file = $this->get_file($size);

            return (isset($default) && empty($file)) ? $default : $this->base_dir . '/' . $file;
        }

        /*
        --------------------------------------------------
        Get Attachment src url

        @param string $size
        @param mixed $default Default value to return

        @return string
        --------------------------------------------------
        */
        public function get_src($size='full', $default=null)
        {
            $file = $this->get_file($size);

            return (isset($default) && empty($file)) ? $default : $this->base_url . '/' . $file;
        }

        /*
        --------------------------------------------------
        Get image html

        @param string $size
        @param mixed $default Default value to return

        @return string
        --------------------------------------------------
        */
        public function get_html($size='full', $default=null)
        {
            $html = wp_get_attachment_image($this->id, $size);

            return (isset($default) && empty($html)) ? $default : $html;
        }

        /*
        --------------------------------------------------
        Save object's data to the database from $this->data, $this->meta, $this->terms

        @return void
        --------------------------------------------------
        */
        public function persist($reconstruct=false)
        {
            $updating = (bool)$this->get_id();
            
            if($updating)
            {
                if(!$this->get_data('post_parent') && $this->parent_id)
                {
                    $this->set_data('post_parent', $this->parent_id);
                }

                $data = array_merge($this->get_data(), [
                    'meta_input' => $this->get_meta(null, null, true)
                ]);
    
                $id = wp_update_post($data);
                if(is_wp_error($id))
                {
                    return false;
                }
            }
            elseif(!empty($this->file_data))
            {
                $save_name = wp_unique_filename($this->base_dir, $this->file_data['name']);
                $save_path = $this->base_dir . '/' . $save_name;
                
                $tmp_dir = sys_get_temp_dir();
                
                if(strpos($this->file_data['tmp_name'], $tmp_dir) === 0)
                {
                    move_uploaded_file($this->file_data['tmp_name'], $save_path);
                }
                else
                {
                    copy($this->file_data['tmp_name'], $save_path);
                    unlink($this->file_data['tmp_name']);
                }

                if(file_exists($save_path))
                {
                    $mime_type = empty($this->file_data['type']) ? mime_content_type($save_path) : $this->file_data['type'];

                    $this->set_data('guid', $this->base_url . '/' . $save_name);
                    $this->set_data('post_mime_type', $mime_type);
                    $this->set_data('post_title', $save_name);
                    //$this->set_data('post_content', '');
                    $this->set_data('post_status', 'inherit');

                    $data = array_merge($this->get_data(), [
                        'meta_input' => $this->get_meta(null, null, true)
                    ]);
                    
                    $id = wp_insert_attachment($data, $save_path, $this->parent_id);
                    if(is_wp_error($id))
                    {
                        return false;
                    }
    
                    $this->set_id((int)$id);

                    //require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $metadata = wp_generate_attachment_metadata($this->get_id(), $save_path);
                    wp_update_attachment_metadata($this->get_id(), $metadata);
                }
            }

            if($reconstruct)
            {
                $this->__construct(
                    $this->get_id(), 
                    $this->props_config,
                    $this->parent_id,
                    []
                );
            }

            return $this->get_id();
        }
    
        /*
        --------------------------------------------------
        Delete WP_Post
        
        @param bool $force_deleten Whether to mark as trashed or delete permanently

        @return void
        --------------------------------------------------
        */
        public function delete($force_delete=true, $delete_attachments=false){
            
            if(!$this->id) return false;
            
            return wp_delete_attachment($this->id, $force_delete);
        }

        /*
        --------------------------------------------------
        Validate object properties

        @prop array $file_reqs = [
            'max_size' => 0,
            'file_types' => []
        ]

        @return array
        --------------------------------------------------
        */
        public function validate($file_reqs=[])
        {
            $errors = parent::validate();

            $errors['file_errors'] = [];

            if(!empty($this->file_data))
            {
                $file_reqs = wp_parse_args($file_reqs, [
                    'max_size' => 0,
                    'mime_types' => []
                ]);

                if($this->file_data['error'] !== 0)
                {
                    $errors['file_errors'] = $this->file_data['error'];
                    return $errors;
                }

                if($file_reqs['max_size'] && $this->file_data['size'] > $file_reqs['max_size'])
                {
                    $errors['file_errors'][] = 'max_size';
                }
                if($file_reqs['mime_types'] && !in_array($this->file_data['type'], $file_reqs['mime_types']))
                {
                    $errors['file_errors'][] = 'mime_types';
                }
            }

            return $errors;
        }
    }
}