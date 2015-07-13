<?php
/* 
	LDAP functions
	References:
		http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
		http://php.net/manual/en/function.ldap-connect.php
		https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
		http://stackoverflow.com/questions/14815142/php-ldap-bind-on-secure-remote-server-windows-fail
		
        http://pear.php.net/package/Net_LDAP2/download
        http://stackoverflow.com/questions/8276682/wamp-2-2-install-pear

*/
$progpath=dirname(__FILE__);
//---------- begin function LDAP Auth--------------------
/**
* @describe used ldap to authenticate user and returns user ldap record
* @param params array
*	-host - LDAP host
*	-username
*	-password
*	[-secure] - prepends ldaps:// to the host name. Use for secure ldap servers
* @return mixed - ldap user record array on success, error msg on failure
* @usage $rec=LDAP Auth(array('-host'=>'myldapserver','-username'=>'myusername','-password'=>'mypassword'));



*/
function ldapAuth($params=array()){
	if(!isset($params['-host'])){return 'LDAP Auth Error: no host';}
	if(!isset($params['-username'])){return 'LDAP Auth Error: no username';}
	if(!isset($params['-password'])){return 'LDAP Auth Error: no password';}
	global $CONFIG;
	if(!isset($params['-bind'])){$params['-bind']="{$params['-username']}@{$params['-host']}";}
	$ldap_base_dn = array();
	$hostparts=preg_split('/\./',$params['-host']);
	foreach($hostparts as $part){
		$ldap_base_dn[]="dc={$part}";
	}
	$params['basedn']=implode(',',$ldap_base_dn);
	if($params['-secure']){
		$params['-host']='ldaps://'.$params['-host'];
	}

	//connect
	$params['-host']='ldap://'.$params['-host'];
	global $ldapInfo;
	$ldapInfo=array();
	$ldapInfo['connection'] = ldap_connect($params['-host']);
	if(!$ldapInfo['connection']){return 'LDAP Auth Error: unable to connect to host';}
	// We need this for doing an LDAP search.
	ldap_set_option($ldapInfo['connection'], LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_NETWORK_TIMEOUT, 5);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_SIZELIMIT, 5);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_TIMELIMIT, 5);
    $enum=ldap_errno($ldapInfo['connection']);
    $msg=ldap_err2str( $enum );
    //echo printValue($params);exit;
	//bind
    $bind=ldap_bind($ldapInfo['connection'],$params['-bind'],$params['-password']);
    if(!$bind){
        $enum=ldap_errno($ldapInfo['connection']);
        $msg=ldap_err2str( $enum );
		ldap_unbind($ldapInfo['connection']); // Clean up after ourselves.
		return "LDAP Auth Error: auth failed. Err:{$enum}, Msg:{$msg} .. ".printValue($params);
	}
	foreach($params as $k=>$v){
    	$ldapInfo[$k]=$v;
	}
	//set paging to 1000
	ldap_control_paged_result($ldapInfo['connection'], 500, true, $cookie);
    //now get this users record and return it
	$rec=array();
	//$search_filter = "(&(objectCategory=person))";
	//set search filter to be the current username so we get the current user record back
	$ldapInfo['lastsearch'] = "(&(objectClass=user)(sAMAccountName={$params['-username']}))";
	$result = ldap_search($ldapInfo['connection'], $ldapInfo['basedn'], $ldapInfo['lastsearch']);
	//echo "result".printValue($result).printValue($params);exit;
	if (FALSE !== $result){
		$entries = ldap_get_entries($ldapInfo['connection'], $result);
	    if ($entries['count'] == 1){
	    	$rec=ldapParseEntry($entries[0]);
	    	echo printValue($rec);ldapClose();exit;
	    	return $rec;
		}
		ldapClose();
		//ldap_unbind($ldap_connection); // Clean up after ourselves.
    	return 'LDAP Auth Error: unable to get a unique LDAP user object'.printValue($entries);
	}
	ldapClose();
    return 'LDAP Auth Error: unable to search'.printValue($result);
}
//---------- begin function ldapConvert2UserRecord--------------------
/**
* @describe converts an ldap user record to a record for the _users table
* @param params array
* @return array
* @usage $rec=ldapConvert2UserRecord($rec);
*/
function ldapClose(){
	global $ldapInfo;
	ldap_unbind($ldapInfo['connection']);
	$ldapInfo=array();
}
//---------- begin function ldapConvert2UserRecord--------------------
/**
* @describe converts an ldap user record to a record for the _users table
* @param params array
* @return array
* @usage $rec=ldapConvert2UserRecord($rec);
*/
function ldapConvert2UserRecord($lrec=array()){
	global $CONFIG;
	//unset($lrec['objectsid']);
	$rec=array('active'=>1,'utype'=>1,'ldap'=>printValue($lrec));
	foreach($lrec as $key=>$val){
		switch(strtolower($key)){
          	case 'samaccountname':$rec['username']=$val;break;
          	case 'dn':$rec['password']=substr(encodeBase64($val),10,30);break;
          	case 'l':$rec['city']=$val;break;
          	case 'c':$rec['country']=$val;break;
          	case 'sn':$rec['lastname']=$val;break;
          	case 'givenname':$rec['firstname']=$val;break;
          	case 'displayname':$rec['name']=$val;break;
          	case 'department':$rec['department']=$val;break;
          	case 'title':$rec['title']=$val;break;
          	case 'mail':$rec['email']=$val;break;
          	case 'company':$rec['company']=$val;break;
          	case 'homephone':$rec['phone_home']=$val;break;
          	case 'mobile':$rec['phone_mobile']=$val;break;
          	case 'postalcode':$rec['zip']=$val;break;
          	case 'st':$rec['state']=$val;break;
          	case 'streetaddress':$rec['address1']=$val;break;
          	case 'telephonenumber':$rec['phone']=$val;break;
          	case 'url':$rec['url']=$val;break;
          	case 'primarygroupid':$rec['primarygroupid']=$val;break;
          	case 'manager':
          		if(preg_match('/CN\=(.+?)\,/',$val,$m)){$rec['manager']=$m[1];}
          		elseif(preg_match('/CN\=(.+?)$/',$val,$m)){$rec['manager']=$m[1];}
				else{$rec['manager']=$val;}
			break;
          	case 'memberof':
				$rec['memberof']=$val;
			break;
          	//case 'samaccountname':$rec['username']=$val;break;
          	//case 'samaccountname':$rec['username']=$val;break;
		}
	}
	unset($rec['ldap']);
	ksort($rec);
	return $rec;
}
//---------- begin function ldapGetRecords--------------------
/**
* @describe returns a list of LDAP records based on parameters
* @param params array
* @return array or null if blank
* @usage $recs=ldapGetRecords($params);
* @exclude - not ready yet
* @reference https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
*/
function ldapGetUsers($params=array()) {
	global $ldapInfo;
	//set the pageSize
	ldap_get_option($ldapInfo['connection'],LDAP_OPT_SIZELIMIT,$ldapInfo['page_size']);
	//echo $pageSize;
	$ldapInfo['lastsearch'] = "(&(objectClass=user)(objectCategory=person))";
	$cookie='';
	$recs=array();
	//loop through based on pageSize and get the records
	do {
        ldap_control_paged_result($ldapInfo['connection'], $ldapInfo['page_size'], true, $cookie);
        $result = ldap_search($ldapInfo['connection'], $ldapInfo['basedn'], $ldapInfo['lastsearch']);
        //echo printValue($cookie).printValue($ldapInfo);exit;
        $entries = ldap_get_entries($ldapInfo['connection'], $result);
        foreach ($entries as $e) {
		 	$recs[]=ldapParseEntry($e);
        }
    	ldap_control_paged_result_response($ldapInfo['connection'], $result, $cookie);

	} while($cookie !== null && $cookie != '');
	return $recs;
}
function ldapParseEntry($lrec=array()){
	$rec=array('active'=>1,'utype'=>1);
	foreach($lrec as $key=>$val){
    	if(is_numeric($key)){continue;}
        if($key=='objectguid' || $key=='objectsid' || $key=='msexchsafesendershash' || $key=='count'){continue;}
        switch(strtolower($key)){
            case 'whencreated':
            case 'whenchanged':
            case 'badpasswordtime':
            case 'dscorepropagationdata':
            case 'accountexpires':
            case 'lastlogontimestamp':
            case 'lastlogon':
            case 'pwdlastset':
                $rec[$key]=ldapValue($val);
                $rec["{$key}_unix"]=ldapTimestamp($val[0]);
                $rec["{$key}_date"]=date('Y-m-d h:i a',$rec["{$key}_unix"]);
            break;
            case 'memberof':
                $tmp=preg_split('/\,/',ldapValue($val));
                $parts=array();
                foreach($tmp as $part){
                    if(!in_array($part,$parts)){$parts[]=$part;}
				}
				$rec[$key]=implode(',',$parts);
            break;
            case 'samaccountname':$rec['username']=$val;break;
          	case 'dn':$rec['password']=substr(encodeBase64($val),10,30);break;
          	case 'l':$rec['city']=$val;break;
          	case 'c':$rec['country']=$val;break;
          	case 'sn':$rec['lastname']=$val;break;
          	case 'givenname':$rec['firstname']=$val;break;
          	case 'displayname':$rec['name']=$val;break;
          	case 'department':$rec['department']=$val;break;
          	case 'title':$rec['title']=$val;break;
          	case 'mail':$rec['email']=$val;break;
          	case 'company':$rec['company']=$val;break;
          	case 'homephone':$rec['phone_home']=$val;break;
          	case 'mobile':$rec['phone_mobile']=$val;break;
          	case 'postalcode':$rec['zip']=$val;break;
          	case 'st':$rec['state']=$val;break;
          	case 'streetaddress':$rec['address1']=$val;break;
          	case 'telephonenumber':$rec['phone']=$val;break;
          	case 'url':$rec['url']=$val;break;
          	case 'primarygroupid':$rec['primarygroupid']=$val;break;
          	case 'manager':
          		if(preg_match('/CN\=(.+?)\,/',$val,$m)){$rec['manager']=$m[1];}
          		elseif(preg_match('/CN\=(.+?)$/',$val,$m)){$rec['manager']=$m[1];}
				else{$rec['manager']=$val;}
			break;
            default:
                $rec[$key]=ldapValue($val);
            break;
		}
	}
	return $rec;
}
function ldapTimestamp($ad) {
	if(stringContains($ad,'.')){
     	//YYYYMMDDHHIISS
     	$str=substr($ad,0,4).'-'.substr($ad,4,2).'-'.substr($ad,6,2).' '.substr($ad,8,2).':'.substr($ad,10,2).':'.substr($ad,12,2);
		return strtotime($str);
	}
  $seconds_ad = $ad / (10000000);
   //86400 -- seconds in 1 day
   $unix = ((1970-1601) * 365 - 3 + round((1970-1601)/4) ) * 86400;
   $timestamp = $seconds_ad - $unix;
   return $timestamp;
}
function ldapValue($val){
	if(!isset($val['count'])){return $val;}
	unset($val['count']);
	return implode(',',$val);
}
?>
