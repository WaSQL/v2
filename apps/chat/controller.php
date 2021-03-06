<?php
//require user
global $USER;
global $PASSTHRU;
global $PAGE;
global $CONFIG;
global $APP;
//check for minimun APP settings required to run.  url, timer, users
if(!isset($APP['-url'])){
	if(isset($PAGE['name'])){
		$APP['-url']='/'.$PAGE['name'];
	}
}
if(!isset($APP['-group'])){
	$APP['-group']='GENERAL';
}
if(!isset($APP['-ajaxurl'])){
	if(isset($PAGE['name'])){
		$APP['-ajaxurl']='/t/1/'.$PAGE['name'];
	}
}
if(!isset($APP['-timer'])){
	$APP['-timer']=15;
}

if(!isUser()){
	setView('login',1);
	return;
}
switch(strtolower($PASSTHRU[0])){
	case 'app_chat_config':
		setView('config',1);
		return;
	break;
	case 'app_chat_playsound':
		$soundfile="{$APP['-app_path']}/notify.mp3";
		header('Content-Type: audio/mpeg');
		header('Content-Disposition: inline;filename="notify.mp3"');
		header('Content-length: '.filesize($soundfile));
    	header('Cache-Control: no-cache');
		header("Content-Transfer-Encoding: chunked");
		readfile($soundfile);
		exit;
	break;
	case 'app_chat_check_for_new_messages':
		$last_message_id=(integer)$_REQUEST['last_message'];
		$cnt=chatCheckForNewMessages($last_message_id);
		if($cnt > 0){
			//new messages - go get them
			setView('get_new_messages',1);
		}
		else{
			setView('do_nothing',1);
		}
		return;
	break;
	case 'app_chat_get_new_messages':
		$messages=chatGetMessages();
		setView(array('messages','notify'),1);
		return;
	break;
	case 'app_chat_edit':
		$id=(integer)$PASSTHRU[1];
		setView('message_edit',1);
		return;
	break;
	case 'app_chat_edit_processed':
		$id=(integer)$PASSTHRU[1];
		$rec=getDBRecordById('app_chat',$id);
		setView('message_edit_processed',1);
		return;
	break;
	case 'app_chat_delete':
		$id=(integer)$PASSTHRU[1];
		$opts=array('-table'=>'app_chat','-where'=>"_id={$id} and _cuser={$USER['_id']}");
		$rec=getDBRecord($opts);
		if(isset($rec['_id'])){
			$ok=delDBRecord($opts);
			if(strlen($rec['attachments'])){
				$json=json_decode($rec['attachments'],true);
				foreach($json as $type=>$files){
					foreach($files as $file){
						$afile=$_SERVER['DOCUMENT_ROOT'].$file;
						if(file_exists($afile)){
							unlink($afile);
						}
					}
				}
			}
		}
		
		//echo printValue($ok).printValue($opts);exit;
		$messages=chatGetMessages();
		setView(array('messages'),1);
		return;
	break;
	case 'app_chat_msg':
		$messages=chatAddMessage($_REQUEST);
		setView(array('messages','clearinput'),1);
		return;
	break;
	default:
		$ok=chatSetup();
		$messages=chatGetMessages();
		setView('default');
	break;
}
?>