<?php

namespace WPSEED;

if(!class_exists('\WPSEED\Post'))
{
    class Post extends Entity
    {
        /*
        --------------------------------------------------
        Construct the Post object

        @param object|int $post WP_Post instance or post ID.
        @param array $props_config['key'] = [
            'type' => 'data' (default) | 'meta' | 'term' | 'attachment'
            'label' => 'Field Label' (defaults to $key),
            'options' => [
                'option1' => 'Option Label 1',
                'option2' => 'Option Label 2'
            ],
            'required' => false (default) | true
        ]

        @return void
        --------------------------------------------------
        */
        public function __construct($post=null, $props_config=[])
        {
            parent::__construct($post, $props_config);
        }

        /*
        --------------------------------------------------
        Save object's data to the database from $this->data, $this->meta, $this->terms

        @return void
        --------------------------------------------------
        */
        public function persist()
        {
            $id = 0;

            if(!empty($this->data))
            {
                $data = $this->get_data();

                $data['meta_input'] = $this->get_meta(null, true);

                if(empty($data['ID']))
                {
                    $id = wp_insert_post($data);
                }
                else{
                    $id = wp_update_post($data);
                }
            }

            if(!empty($this->terms) && $id)
            {
                foreach($this->terms as $taxonomy => $terms)
                {
                    wp_set_object_terms($id, $terms, $taxonomy, false);
                }
            }

            if($id !== $this->id)
            {
                $this->__construct(
                    $id, 
                    $this->props_config
                );
            }
        }

        /*
        --------------------------------------------------
        Delete WP_Post
        
        @param bool $force_deleten Whether to mark as trashed or delete permanently
        @param bool $delete_children Whether to delete children

        @return void
        --------------------------------------------------
        */
        public function delete($force_delete=true, $delete_children=false)
        {
            if(!$this->id) return false;
            
            if($delete_children)
            {
                $this->delete_children($force_delete);
            }
            
            return wp_delete_post($this->id, $force_delete);
        }
    }
}