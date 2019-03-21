<?php
function translateListRecords($locale){
	global $PAGE;
	return databaseListRecords(array(
		'-table'=>'_translations',
		'-formaction'=>"/{$PAGE['name']}/locale/{$locale}",
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-trclass'=>'w_pointer',
		'-listfields'=>'page,template,source,translation,confirmed',
		'-searchfields'=>'source,translation,p_id,t_id',
		'p_id_displayname'=>'PageID',
		't_id_displayname'=>'TemplateID',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/edit/%_id%','modal',{setprocessing:0,cp_title:'Add/Edit Translation'})",
		'locale'=>$locale,
		'-sort'=>1,
		'-results_eval'=>'translateAddExtraInfo',
	));
}
function translateAddExtraInfo($recs){
	$locale=$recs[0]['locale'];
	$p_ids=array();
	$t_ids=array();
	foreach($recs as $rec){
		if(!in_array($rec['p_id'],$p_ids)){$p_ids[]=$rec['p_id'];}
		if(!in_array($rec['t_id'],$t_ids)){$t_ids[]=$rec['t_id'];}
	}
	if(!count($p_ids)){return $recs;}
	$p_idstr=implode(',',$p_ids);
	$t_idstr=implode(',',$t_ids);
	//sourcemap
	$source_locale=translateGetSourceLocale();	
	$opts=array(
		'-table'=>'_translations',
		'-where'=>"locale='{$source_locale}' and p_id in ($p_idstr)",
		'-index'=>'identifier',
		'-fields'=>'identifier,translation'
	);
	$sourcemap=getDBRecords($opts);
	//pagemap
	$opts=array(
		'-table'=>'_pages',
		'-where'=>"_id in ($p_idstr)",
		'-index'=>'_id',
		'-fields'=>'_id,name'
	);
	$pagemap=getDBRecords($opts);
	//echo printValue($opts).printValue($pagemap);exit;
	//templatemap
	$opts=array(
		'-table'=>'_templates',
		'-where'=>"_id in ($t_idstr)",
		'-index'=>'_id',
		'-fields'=>'_id,name'
	);
	$templatemap=getDBRecords($opts);
	//echo printValue($opts).printValue($templatemap);exit;
	if(!count($sourcemap)){return $recs;}
	foreach($recs as $i=>$rec){
		$key=$rec['identifier'];
		if(isset($sourcemap[$key])){
			$recs[$i]['source']=$sourcemap[$key]['translation'];
		}
		$id=$rec['p_id'];
		if(isset($pagemap[$id])){
			$recs[$i]['page']=$id.' - '.$pagemap[$id]['name'];
		}
		$id=$rec['t_id'];
		if(isset($templatemap[$id])){
			$recs[$i]['template']=$id.' - '.$templatemap[$id]['name'];
		}
		if($recs[$i]['confirmed']==1){
			$recs[$i]['confirmed']='<span class="icon-mark w_success"></span>';
		}
		else{
			$recs[$i]['confirmed']='<span class="icon-block w_danger"></span>';
		}
	}
	return $recs;
}
function translateListLocales(){
	global $PAGE;
	global $MODULE;
	$opts=array(
		'-list'=>translateGetLocalesUsed(),
		'-hidesearch'=>1,
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered w_pointer condensed striped bordered hover',
		'-listfields'=>'flag4x3,locale,entry_cnt,confirmed_cnt,failed_cnt',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/list/%locale%','translate_results',{setprocessing:0});",
		'flag4x3_displayname'=>'Flag',
		'flag4x3_image'=>1,
		'entry_cnt_displayname'=>'Entries',
		'entry_cnt_style'=>'text-align:right;',
		'confirmed_cnt_displayname'=>'<span class="icon-mark w_success"></span>',
		'failed_cnt_displayname'=>'<span class="icon-block w_danger"></span>',
		'failed_cnt_style'=>'text-align:center;'
	);
	if(isset($MODULE['showflags']) && $MODULE['showflags']==0){
		$opts['-listfields']='locale,entry_cnt,confirmed_cnt,failed_cnt';
	}
	return databaseListRecords($opts);
}
function translateEditRec($rec){
	global $PAGE;
	$opts=array(
		'-action'=>"/t/1/{$PAGE['name']}/list/{$rec['locale']}",
		'-onsubmit'=>"return ajaxSubmitForm(this,'translate_results');",
		'setprocessing'=>0,
		'_menu'=>'translate',
		'func'=>'list',
		'locale'=>$rec['locale'],
		'-table'=>'_translations',
		'-fields'=>translateEditFields(),
		'translation_inputtype'=>'textarea',
		'translation_class'=>'form-control browser-default',
		'translation_style'=>'width:100%;height:150px;',
		'confirmed'=>1,
		'_id'=>$rec['_id'],
		'-hide'=>'clone,delete'
	);
	//return $opts['-fields'];
	return addEditDBForm($opts);
}
function translateEditFields(){
	return <<<ENDOFFIELDS
	<div>[translation]</div>
ENDOFFIELDS;
}
?>