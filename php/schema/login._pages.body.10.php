<?
/*filetype:php*/
if(!isAjax()){echo '<h1><span>Login</span></h1>'."\n";}
if(isUser()){echo 'You are now logged in'."\n";}
else{echo userLoginForm();}
?>
