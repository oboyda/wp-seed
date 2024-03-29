<?php 

namespace WPSEED;

if(!class_exists(__NAMESPACE__ . '\Action'))
{
    class Action 
    {
        protected $status;
        protected $error_fields;
        protected $error_messages;
        protected $success_messages;
        protected $messages;
        protected $values;
        protected $redirect;
        protected $reload;

        protected $req;

        function __construct()
        {
            $this->status = true;
            $this->error_fields = [];
            $this->error_messages = [];
            $this->success_messages = [];
            $this->messages = '';
            $this->values = [];
            $this->redirect = '';
            $this->reload = false;

            $this->req = new Req();
        }

        /*
        Setters
        -------------------------
        */

        protected function setStatus($status)
        {
            $this->status = (bool)$status;
        }

        protected function setRedirect($redirect)
        {
            $this->redirect = $redirect;
        }

        protected function setReload($reload)
        {
            $this->reload = (bool)$reload;
        }

        protected function addErrorField($field)
        {
            if(is_array($field) && !empty($field))
            {
                foreach($field as $_field)
                {
                    $this->addErrorField($_field);
                }
                return;
            }

            if(!empty($field) && !in_array($field, $this->error_fields))
            {
                $this->error_fields[] = $field;
            }
        }

        protected function removeErrorField($field)
        {
            $i = array_search($field, $this->error_fields);

            if($i !== false)
            {
                unset($this->error_fields[$i]);
            }
        }

        protected function addSuccessMessage($message)
        {
            if(!empty($message) && !in_array($message, $this->success_messages))
            {
                $this->success_messages[] = $message;
                $this->messages .= $this->wrapResponseMessages($message, 'success');
            }
        }

        protected function addErrorMessage($message)
        {
            if(empty($message))
            {
                return;
            }

            if(is_array($message))
            {
                foreach($message as $_message)
                {
                    $this->addErrorMessage($_message);
                }
                return;
            }

            if(!empty($message) && !in_array($message, $this->error_messages))
            {
                $this->error_messages[] = $message;
                $this->messages .= $this->wrapResponseMessages($message, 'error');
            }
        }

        protected function setValue($key, $value)
        {
            $this->values[$key] = $value;
        }

        /*
        Getters
        -------------------------
        */

        protected function getStatus()
        {
            return $this->status;
        }

        protected function getReq($key, $san='text', $default=null)
        {
            return $this->req->get($key, $san, $default);
        }

        protected function getReqFile($key, $default=null)
        {
            return $this->req->getFile($key, $default);
        }

        protected function getReqAll($san_types=[], $defaults=[])
        {
            return $this->req->getAll($san_types, $defaults);
        }

        /*
        Helpers
        -------------------------
        */

        protected function validateFields($fields_config, $respond_on_errors=false)
        {
            // if(!empty($extra_required_fields))
            // {
            //     foreach($extra_required_fields as $key => $field)
            //     {
            //         if(is_array($field))
            //         {
            //             $fields_config[$key] = $field;
            //         }
            //         else{
            //             $fields_config[$field] = [
            //                 'validate' => 'text'
            //             ];
            //         }
            //     }
            // }

            $validated = $this->req->validateFields($fields_config);

            if(!empty($validated['error_fields']))
            {
                $this->addErrorField($validated['error_fields']);
            }

            if(!empty($validated['errors']))
            {
                $this->addErrorMessage($validated['errors']);
            }

            if($respond_on_errors && $this->hasErrors())
            {
                return $this->respond();
            }

            return $validated;
        }

        protected function checkErrorFields($fields, $required_keys, $respond_on_errors=false)
        {
            $empty_fields = self::checkArrayEmptyVals($fields, $required_keys);

            if(!empty($empty_fields))
            {
                $this->addErrorField($empty_fields);
            }

            if($respond_on_errors && $this->hasErrors())
            {
                $this->respond();
            }
        }

        static function checkArrayEmptyVals($arr, $include=[], $empty_compare=[])
        {
            $empty_keys = [];
    
            foreach((array)$arr as $k => $a)
            {
                if($include && !in_array($k, $include))
                {
                    continue;
                }
    
                if($empty_compare)
                {
                    if(in_array($a, $empty_compare, true))
                    {
                        $empty_keys[] = $k;
                    }
                }
                elseif(empty($a))
                {
                    $empty_keys[] = $k;
                }
            }
    
            return $empty_keys;
        }
    
        protected function checkCurrentUserCapability($capability)
        {
            if(!current_user_can($capability))
            {
                $this->setStatus(false);
                $this->addErrorMessage(__('Current user is not allowed to perform this action.', 'wpseed'));
                $this->respond();
            }
        }

        protected function wrapResponseMessages($messages, $type='success')
        {
            $w_messages = [];
            foreach((array)$messages as $message)
            {
                $w_messages[] = '<p class="message ' . $type . '">' . $message . '</p>';
            }
            return implode('', $w_messages);
        }

        protected function hasErrors()
        {
            return (!empty($this->error_fields) || !empty($this->error_messages));
        }

        protected function respond($resp=[])
        {
            if($this->hasErrors())
            {
                $this->setStatus(false);
            }

            $resp = apply_filters('wpseed_respond_args', wp_parse_args($resp, [
                
                'status' => $this->status,
                'error_fields' => $this->error_fields,
                'error_messages' => $this->error_messages,
                'success_messages' => $this->success_messages,
                'messages' => $this->messages,
                'values' => $this->values,
                'redirect' => $this->redirect,
                'reload' => $this->reload
            ]));

            wp_send_json($resp);
        }
    }
}