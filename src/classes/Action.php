<?php 
namespace WPSEED;

class Action 
{
    protected $status;
    protected $error_fields;
    protected $messages;
    protected $values;
    protected $redirect;
    protected $reload;

    protected $req;

    function __construct()
    {
        $this->status = true;
        $this->error_fields = [];
        $this->messages = '';
        $this->values = [];
        $this->redirect = '';
        $this->reload = false;

        $this->req = new Req();
    }

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
        if(!in_array($field, $this->error_fields))
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

    protected function checkErrorFields($fields, $required_keys, $respond_on_errors=false)
    {
        $empty_fields = Utils::checkArrayEmptyVals($fields, $required_keys);

        if($empty_fields)
        {
            foreach($empty_fields as $empty_field)
            {
                $this->addErrorField($empty_field);
            }
        }

        if($respond_on_errors && $this->hasErrors())
        {
            $this->respond();
        }
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

    protected function addSuccessMessage($message)
    {
        $this->messages .= $this->wrapResponseMessages($message, 'success');
    }

    protected function addErrorMessage($message)
    {
        $this->messages .= $this->wrapResponseMessages($message, 'error');
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

    protected function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    protected function getReq($key, $san='text', $default=null)
    {
        return $this->req->get($key, $san, $default);
    }

    protected function hasErrors()
    {
        return (!$this->status || !empty($this->error_fields));
    }

    protected function respond($resp=[])
    {
        if($this->error_fields)
        {
            $this->setStatus(false);
            $this->addErrorMessage(__('Please, check the required fields.', 'wpseed'));
        }

        if(!$this->status && empty($this->messages))
        {
            $this->addErrorMessage(__('Something went wrong. Please, try again later.', 'wpseed'));
        }

        $resp = wp_parse_args([
            'status' => $this->status,
            'error_fields' => $this->error_fields,
            'messages' => $this->messages,
            'values' => $this->values,
            'redirect' => $this->redirect,
            'reload' => $this->reload
        ], $resp);

        wp_send_json($resp);
    }
}