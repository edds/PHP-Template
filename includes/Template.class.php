<?php

class Template
{

  protected $___file=false;
  protected $___template=array();
  protected $___contents=null;
  
  public function __construct($tfile) {
    if(is_file($tfile)) {
      $this->___file = $tfile;
    } else {
      error_log('file not found: '.$tfile);
    }
  }
  
  // set
  public function setValue($key, $val) {
    return ($this->___template[$key] = $val);
  }
  
  // append
  public function appendValue($key, $val) {
    return ($this->___template[$key] .= $val);
  }

    public function merge($row, $prefix=''){
        foreach($row as $key => $val){
            $this->___template[$prefix.$key] = $val;
        }
        return true;
    }

  public function reset() {
    $this->___template = array();
  }
  
  // parse
  public function render() {
    if($this->___file != false && is_readable($this->___file)) {
      // If template is not cached, do so.
      if($this->___contents == null) {
        $this->___contents = file_get_contents($this->___file);
      }
      $parse =& $this->___template;

      // Parse
      $str = addslashes($this->___contents);
      eval("\$str = \"$str\";");
      $str = stripslashes($str);
      return $str;
    } else {
      throw new Exception('Template not included: '.$this->___file);  
    }
  }
  
  public function __get($k) {
    if(array_key_exists($k, $this->___template))
      return $this->___template[$k];
    else
      return null;
  }
  
  public function __set($k,$v) {
    $this->setValue($k,$v);
  }
  
  public function __toString() {
    try  {
      $str = $this->render();
      return (string) $str;
    }
    catch (Exception $e) {
      error_log($e);
    }
  }
}

class DynamicTemplate extends Template {
  
  public function render() {
    if($this->___file != false) {
      // If template is not cached, do so.
      if($this->___contents == null) {
        $this->___contents = file_get_contents($this->___file);
      }
      foreach($this->___template as $k => $v){
        $$k = $v;
      }

      ob_start();
      eval("?>" . $this->___contents . "<?"); 
      $c = ob_get_contents(); 
      ob_end_clean();
      return $c;
    }
  }
}

?>
