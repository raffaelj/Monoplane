<?php

namespace Monoplane\Controller;

class Forms extends \LimeExtra\Controller {

    public $message = [
        'success' => 'Thank you for the message<br>I will answer soon.',
        'notice'  => 'Fill in all mandatory fields.',
    ];

    protected function before() {

        $this->config = array_replace_recursive([
            'form' => 'contact',
            'route' => '/contact',
            'anchor' => '',
            'session.expire' => 60,
            'session.name' => md5(__DIR__),
            // 'session.name' => null, // use Cockpit's session name by default
        ], $this->retrieve('monoplane/contactform', []));

    }

    public function index() {
        // index function must exist or LimeExtra howls
    }

    public function form($form = '', $options = []) {

        if (empty($form))
            $form = $this->config['form'];

        $_fields = $this->app->module('forms')->form($form)['fields'];

        if (!$_fields) {
            echo '<p>Form fields are not defined. Use the <a href="https://github.com/raffaelj/cockpit_FormValidation">FormValidation addon</a> to specify them.</p>';
            return;
        }

        // conditianal fields
        if (isset($options['fields'])) {
            $fld = [];
            foreach ($_fields as $f) {
                if (!isset($options['fields'][$f['name']])
                    || $options['fields'][$f['name']] != false) {
                    $fld[] = $f;
                }
            }
            $_fields = $fld;
        }

        $success = false;
        $notice  = false;

        if ($this->param('notice')) {
            $this('session')->init($this->config['session.name']);
            $notice = true;
        }

        if ($this->param('success')) {
            $this('session')->init($this->config['session.name']);
            $success = true;
        }

        // hide messages if session is expired and user calls the url again
        $expire = $this->config['session.expire'];
        $call = $this('session')->read('mp_call', null);
        if (!$call || ($call && (time() - $call > $expire))) {

            $this('session')->delete('mp_call');
            $this('session')->delete('mp_response');

            $success = false;
            $notice  = false;

        }

        $message = [
            'success' => $success ? $this->message['success'] : '',
            'notice'  => $notice  ? $this->message['notice']  : '',
        ];

        $response = $this('session')->read('mp_response', []);

        $error = $this->seriousError($_fields, $response);

        $fields = '';
        foreach ($_fields as $f)
            $fields .= $this->renderField($f, $response);

        return $this->renderView('views:partials/form.php', compact('form', 'fields', 'error', 'message', 'options'));

    }

    public function submit($form = '') {

        $this('session')->init($this->config['session.name']);
        $this('session')->write('mp_call', time());

        $referer = !empty($_SERVER['HTTP_REFERER']) ? parse_url(htmlspecialchars($_SERVER['HTTP_REFERER'])) : null;

        if (!$referer) {
            // might be disabled, use a default fallback
            // to do...
            $path = $this->app['site_url'] . $this->config['route'] . $this->config['anchor'];
            $referer = parse_url($path);
        }

        $url = @$referer['scheme'] . '://' .  @$referer['host'] . @$referer['path'];

        if (mb_stripos($url, $this->app['site_url']) !== 0) {

            // submitting data from somewhere else is not allowed
            // to do...
            return ['error' => 'submitting data from somewhere else is not allowed'];

        }

        $this('session')->write('mp_referer', $url);

        // evil user input
        $postedData = [];
        foreach($this->app->param() as $key => $val) {
            if ($key != 'submit')
                $postedData[$key] = htmlspecialchars(trim($val));
        }

        // catch response stop from FormValidation addon
        try {
            $response = $this->module('forms')->submit($form, $postedData);
        } catch (\Exception $e) {
            $response = json_decode($e->getMessage(), true);
        }

        if (!isset($response['error'])) {

            $referer = $this('session')->read('mp_referer');
            $this('session')->delete('mp_response');
            $this('session')->delete('mp_referer');

            if (!empty($this->config['success_route'])) {
                // redirect to success page
                $this->reroute($this->config['success_route'] . '?success=1' . $this->config['anchor']);
            } else {
                // redirect to same page
                $this->reroute($referer . '?success=1' . $this->config['anchor']);
            }

        } else {

            $this('session')->write('mp_response', $response);

            if (!empty($this->config['submit_route'])) {
                // redirect to submit page
                $path = $this->routeUrl($this->config['submit_route']) . '?notice=1' . $this->config['anchor'];
            } else {
                // redirect to same page
                $path = $this('session')->read('mp_referer') . '?notice=1' . $this->config['anchor'];
            }

            header('Location: '.$path);
            $this->stop(303);

        }

    }

    protected function renderField($field, $response = []) {

        $fieldContent = '';
        $attr = [];
        $fld = '';
        $input = 'input';

        $fieldContent = $response['data'][$field['name']] ?? '';

        switch($field['type']){
            case 'boolean':
                $attr['type'] = 'checkbox';
                if(in_array($fieldContent, [1, 'on', 'yes', 'ja']))
                    $attr['checked'] = 'checked';
                break;
            case 'textarea':
                $input = 'textarea';
                break;
            case 'select':
                return $this->renderSelectField($field, $response);
                break;
            default:
                $attr['type'] = 'text';
                $attr['value'] = $fieldContent;
        }

        $attr['name'] = $attr['id'] = $field['name'];

        if(isset($field['required']) && $field['required'])
        $attr['required'] = 'required';

        // add defined attributes from form builder
        if(isset($field['options']['attr']))
            foreach($field['options']['attr'] as $key => $val)
                $attr[$key] = $val;

        $fld .= "<$input";

        // add attributes to string
        foreach($attr as $key => $val) {
            $fld .= ' '.$key.'="'.$val.'"';
        }

        switch($input) {
            case 'textarea':
                $fld .= ">$fieldContent</textarea>";
                break;
            default:
                $fld .= " />";
        }

        // label
        if (isset($field['options']['validate']['honeypot']) && $field['options']['validate']['honeypot']['fieldname'] == $field['name']){
            $label = ''; // no label for honeypot
        } else {

            $label_attr = [];
            $label = "<label";

            $label_attr['for'] = $field['name'];

            // if(isset($field['info']) && !empty($field['info']))
                // $label_attr['title'] = $field['info'];

            // add attributes to string
            foreach($label_attr as $key => $val) {
                $label .= ' '.$key.'="'.$val.'"';
            }

            $required = empty($field['required']) ? '' : '<span class="required" title="'.$this('i18n')->get('required').'">*</span>';
            
            $label .= ">" . $this('i18n')->get($field['label']) . $required . "</label>";

        }

        // errors
        $error = false;

        if (isset($response['error'][$field['name']])) {

            $error = $response['error'][$field['name']];
            $error = is_string($error) ? $error : implode('<br>', $error);

        }

        // field info
        $info = false;
        if (!empty($field['info'])) {
            // translate info before route replacement
            $info = $this('i18n')->get($field['info']);
            $info = str_replace('{{route}}', MP_BASE_URL, $info);
        }

        // field output
        $out = '';
        $out .= '<div class="width-' . $field['width'] . '">' . "\r\n";
        $out .=  $fld . $label . "\r\n";

        if($error)
            $out .=  '<p class="message error">' . $error . '</p>' . "\r\n";

        if($info)
            $out .=  '<p class="info">' . $info . '</p>' . "\r\n";

        $out .=  '</div>' . "\r\n";

        return $out;

    }

    protected function renderSelectField($field, $response = []) {

        // to do:
        // * set attributes
        // * handle key/value options
        // * Should I translate value, too?

        $fld = '';

        $fieldContent = $response['data'][$field['name']] ?? '';

        $options = $field['options']['options'];

        foreach($options as $option) {

            $checked = '';
            if ($fieldContent == $option) {
                $checked = ' checked';
            }

            $fld .= '<input type="radio" name="'.$field['name'].'" id="'.$field['name'].'_'.urlencode($option).'" value="'.$option.'"'.$checked.'>';

            $fld .= '<label for="'.$field['name'].'_'.urlencode($option).'">'.$this('i18n')->get($option).'</label>';

        }

        // field output
        $out = '';
        $out .= '<div class="width-' . $field['width'] . '">' . "\r\n";
        $out .=  '<span class="form_label">'.$this('i18n')->get($field['label'] ?? $field['name']).': </span>' . "\r\n";
        $out .=  $fld . "\r\n";

        $out .=  '</div>' . "\r\n";

        return $out;

    }

    protected function seriousError($fields, $response) {

        // display errors from validator or from Mailer Exceptions (coming soon)

        if (!isset($response['error'])) return false;

        $error = [];
        $fieldnames = array_column($fields, 'name');

        foreach ($response['error'] as $key => $val) {
            if (!in_array($key, $fieldnames)) {
                $error[$key] = $val;
            } 
        }
        if (empty($error)) return false;

        $out = '';
        foreach ($error as $key => $val) {
            $out .= "<strong>$key: </strong><br>";
            $out .= is_string($val) ? $val : implode('<br>', $val);
        }

        return $out;

    }

}
