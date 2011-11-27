<?php
/**
 * Site.class.php
 * this should handle all requests to index.php
 * it works out what page should be displayed
*/
class Site {
  
  protected $actions = array(
    'action');
  protected $output = '';
  
  public function __construct(){
    $handled = false;
    foreach($this->actions as $action){
      if(!empty($_GET[$action])){
        $handled = true;
        return $this->$action($_GET[$action]);
      }
    }
    if(!$handled){
      $this->defaultResponse();
    }
  }
  
  public function __toString(){
    try {
      $t = new Template('./templates/main.html');
      $t->content = $this->output;
      return $t.'';
    } catch(Exception $e){
      show_exception($e);
    }
  }
  
  protected function defaultResponse(){
    $this->output = "You aren't supposed to be here. Get back to class!";
  }

}

?>
