<?php

// Site Specific GLobals:
define('LIVE_SERVER', 'mysite.com'); // specify without http or www.
if(getenv('SERVER_NAME') != LIVE_SERVER){
  define('DEBUG', true);
  
  // Lets Turn On Some Errors Huh?
  ini_set('html_errors',true);
  ini_set('display_errors', true);
  
} else {
  define('DEBUG', false)
}



// Set up the exception handling. 
// This add's a custom exception handler so that the exceptions get displayed
// in a pretty format for readability
function show_exception($e){
  print '<pre style="font:italic 15px georgia; color: #333;">'.$e.'</pre>';
}
set_exception_handler('show_exception');
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  print '<pre style="font:italic 15px georgia; color: #333;">'.$errstr."\n<strong>File:</strong> ". $errfile."\n<strong>Line:</strong> ". $errline.'</pre>';
//  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler", E_ALL);

// Autoloader: this loads class' dynamically as they are required and stops
// them from being loaded more than once.
function __autoload($class){
  if(is_readable(dirname(__FILE__)."/includes/".$class.".class.php")){
    include_once(dirname(__FILE__)."/includes/".$class.".class.php");
  } else if(is_readable("./includes/special_pages/".$class.".php")){
    include_once("./includes/special_pages/".$class.".php");
  } else {
    throw new Exception($class.' Not avalible for inclusion');
  }
}
header('Content-type: text/html; charset=utf-8');

$s = new Site();
echo $s;

?>
