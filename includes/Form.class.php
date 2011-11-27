<?php

class Form {
  public $template;
  private $fields = array();
  private $submit = false;
  private $error = 'Please amend the values of the highlighted fields.';
  
  public function __construct($template, $args=array()){
    $this->template = new Template($template);
    $this->template->formid = !empty($args['formid']) ? $args['formid'] : '';
    if(!empty($args['error'])){
      $this->error = $args['error'];
    }
    $this->submit = $this->isSubmit();
  }
    
  public function __get($k) {
    return $this->fields[$k];
  }

  public function __set($k,$v) {
    if(gettype($v) == 'object'){
      $v->setName($k);
      $v->setFormId($this->template->formid);
      if(!empty($_POST[$k]) || !empty($_FILES[$k]['name'])){
        $tmp = !empty($_POST[$k]) ? $_POST[$k] : $_FILES[$k]['name'];
        $v->value($tmp);
      }
      $this->fields[$k] = $v;
    } else {
      //  something has gone wrong;
      error_log('Object not given in Form::__set');
    }
  }

  public function __toString() {
    try {
      $this->template->error = '';
      foreach($this->fields as $k => $v){
        $v->submit = $this->submit;
        $this->template->$k = $v->buildForm();
      }
      if($this->submit){
        $this->template->error = '<p class="notice">'.$this->error.'</p>';
      }
      return $this->template.'';
    } catch(Exception $e){
      die($e);
    }
  }
  
  public function getValues() {
    if(!$this->submit){
      return false;
    } else {
      $return = array();
      foreach($this->fields as $k => $v){
        if(!$v->validate()){
          return false;
        }
        $return[$k] = $v.'';
        $return[$k.'_safe'] = $v->getValue();
      }
      return $return;
    }
  }
  
  public function mail($to,$from,$subject,$template) {
    $t = new Template($template);
    foreach($this->fields as $k => $v){
      $t->setValue($k,$v);
    }
    mail( $to, $subject, $t.'', 'From: '.$from );
    
  }

  public function reset() {
    foreach($this->fields as $k => $v){
      $v->value('');
    }
    $this->submit = false;
  }
  
  private function isSubmit() {
    return !empty($_POST['submit'.$this->template->formid]);
  }

}

abstract class Field {
  protected $name;
  protected $options;
  protected $value = '';
  public $submit;
  protected $formid;
  
  public function __construct($options=array()) {
    $this->options = $options;
    if(!empty($this->options['default']) && !$this->submit)
      $this->value = $this->options['default'];
  }
  
  public function __toString() {
    $db = Database::getDB();
    return $db->escape($this->value);
  }

  public function __set($k, $v){
    if($k = 'value'){
      $this->value($v);
    }
  }

  public function value($val) {
    $this->value = $val;
  }
  
  public function getValue(){
    return $this->value;
  }
  
  public function validate() {
    if(!empty($this->options['required']) && $this->options['required']){
      return !empty($this->value);
    } else {
      return true;
    }
  }
  
  public function setName($name){
    $this->name = $name;
  }
  
  public function setFormId($id){
    $this->formid = $id;
  }
  
  protected function error(){
    if($this->submit && !$this->validate()){
      return ' class="error"';
    }
    return '';
  }
  
}

class VarChar extends Field {
  protected $length;
  
  public function __construct($length=256, $options=array()) {
    parent::__construct($options);
    $this->length = $length;
    if(!empty($this->options['default']) && !$this->submit)
      $this->value = $this->options['default'];
  }
  public function buildForm() {
    return '<input'.$this->error().' type="text" id="'.$this->name.'" name="'.$this->name.'" maxlength="'.$this->length.'" value="'.$this->value.'">';
  }
  public function validate() {
    if(!empty($this->options['required']) && $this->options['required']){
      if(!empty($this->options['regex'])){
        return (!empty($this->value) && eregi($this->options['regex'], $this->value));
      } else {
        return !empty($this->value);
      }
    } else {
      return true;
    }
  }
}




class EmailField extends VarChar {
  
  public function __construct($options=array()) {
    $options['regex'] = '^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$';
    parent::__construct('320',$options);
  }
}

class Password extends VarChar {
  
  public function buildForm() {
    return '<input'.$this->error().' type="password" id="'.$this->formid.$this->name.'" name="'.$this->name.'" maxlength="'.$this->length.'" value="'.$this->value.'">';
  }
}


class TextArea extends Field {
  
  public function buildForm() {
    return '<textarea'.$this->error().' name="'.$this->name.'" id="'.$this->formid.$this->name.'">'.$this->value.'</textarea>';
  }
}

class Option extends Field {
  private $sql;
  private $values;
  private $rows = array();
  
  public function __construct($values, $options=array()){
    parent::__construct($options);
    if(is_array($values)){
      $this->rows = $values;
    } else {
      $this->sql = $values;
      $this->createDropdown();
    }
  }
  
  public function buildForm() {
    $options = '';
    if(!empty($this->options['default'])){
      $options .= '<option value="">'.$this->options['default'].'</option>'."\n";
    }
    foreach($this->rows as $id => $val){
      $selected = ($id==$this->value) ? ' selected="selected"' : '';
      $options .= '<option value="'.$id.'"'.$selected.'>'.$val.'</option>'."\n";
    }
    return '<select'.$this->error().' name="'.$this->name.'" id="id'.$this->name.'">'.$options.'</select>';
  }
  
  private function createDropdown(){
    $db = Database::getDB();
    $result = $db->query($this->sql);
    while(list($id, $val) = $result->fetch_row()){
      $this->rows[$id] = $val;
    }
  }
}

class CheckBox extends Field {
  
  public function buildForm(){
    $checked = !empty($this->value) ? ' checked="checked"' : '';
    return '<input type="checkbox" id="id'.$this->name.'" name="'.$this->name.'"'.$checked.' value="'.$this->name.'">';
  }
  
}

class FileUpload extends Field {
  private $folder;
  private $filetypes;

  public function __construct($folder, $filetypes, $options=array()){
    parent::__construct($options);
    $this->folder = $folder;
    $this->filetypes = $filetypes;
  }

  public function value($garbish){
    $this->doUpload();
  }

  public function buildForm(){
    return '<input type="file" name="'.$this->name.'">
      <input type="hidden" name="'.$this->name.'_filename" value="'.$this->value.'">';
  }
  
  public function validate() {
    if(!empty($this->options['required']) && $this->options['required']){
      return !empty($this->value);
    }
    return true;
  }
  
  private function doUpload(){
    if( in_array($_FILES[$this->name]["type"], $this->filetypes) || (sizeof($this->filetypes) == 0 && !empty($_FILES[$this->name]['name'])) || !empty($_POST['filename']) ){
      $extension = strtolower(array_pop(explode('.', $_FILES[$this->name]['name'])));
      $this->value = uniqid($this->name).'.'.$extension;
      move_uploaded_file($_FILES[$this->name]['tmp_name'], $this->folder.'/'.$this->value);
    } else if(!empty($_POST[$this->name.'_filename'])){
      $this->value = $_POST[$this->name.'_filename'];
    }
  }
}

?>
