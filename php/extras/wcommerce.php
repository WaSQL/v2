<?php
/*
	wcommerce - WaSQL functions to support an e-commerce site
	References:
		https://ps.w.org/woocommerce/assets/screenshot-1.jpg?rev=2366418

*/
//$progpath=dirname(__FILE__);
$ok=wcommerceInit();
//create wcommerce_files on document root if it does not exist
$wcommerce_files_path=$_SERVER['DOCUMENT_ROOT'].'/wcommerce_files';
buildDir($wcommerce_files_path);
if(!is_dir($wcommerce_files_path)){
	echo "wcommerce ERROR: unable to create {$wcommerce_files_path}.  Manually create it.";
	exit;
}
function wcommerceBuyersList(){
	global $PAGE;
	$opts=array(
		'-table'=>'_users',
		'-tableclass'=>"table striped bordered responsive",
		'-filter'=>"_id in (select user_id from wcommerce_orders)",
		'-sorting'=>1,
		'-listfields'=>'_id,firstname,lastname,city,state,zip,country,order_count,last_order,note',
		'-edit'=>'note'
	);
	return databaseListRecords($opts);
}
function wcommerceOrdersList(){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/list",
		'-onsubmit'=>"return pagingSubmit(this,'wcommerce_orders_content');",
		'-tableclass'=>"table striped bordered responsive",
		'-edit'=>1,
		'-sorting'=>1,
		'-listfields'=>'_id,user_id,date_ordered,date_shipped,tracking_number,date_delivered,items_count,order_total,shipto_city,shipto_state,shipto_country',
		'-order'=>'date_shipped desc'
	);
	$opts['-quickfilters_class']='btn w_blue';
	$opts['-quickfilters']=array(
		'Add'=>array(
			'icon'=>'icon-plus',
			'onclick'=>'wcommerceNav(this);',
			'data-href'=>"/t/1/{$PAGE['name']}/manage_orders/addedit/0",
			'data-div'=>"centerpop",
			'class'=>"btn w_white"
			),
		'Open'=>array(
			'icon'=>'icon-mark',
			'filter'=>'date_shipped ib',
			'class'=>"btn w_blue"
			),
		'Shipped'=>array(
			'icon'=>'icon-package',
			'filter'=>'date_shipped nb',
			'class'=>"btn w_green"
			),
		'In Transit'=>array(
			'icon'=>'icon-spin8',
			'filter'=>'date_shipped nb;date_delivered ib',
			'class'=>"btn w_yellow"
			),
		'Delivered'=>array(
			'icon'=>'icon-spin8',
			'filter'=>'date_shipped nb;date_delivered nb',
			'class'=>"btn w_gray"
			),
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:space-between;align-items:center">
	<div class="w_small w_gray">Click on Id to edit entire record. Click on <span class="icon-edit"></span> to edit a single field.</div>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceBuildField($field,$rec=array(),$val2=''){
	$params=array();
	if(isset($rec[$field])){$params['value']=$params['-value']=$rec[$field];}
	switch(strtolower($field)){
		case 'shipto_state':
			$opts=wasqlGetStates(2,$val2);
			$params['message']=' ----- ';
			return buildFormSelect($field,$opts,$params);
		break;
		case 'size':
			$params['onclick']="wcommerceChangeProductAttribute(this);";
			$params['data-product_guid']=$rec['guid'];
			$params['data-product_name']=$rec['name'];
			$params['data-product_attr']=1;
			$opts=array();
			if(is_array($rec['sizes'])){
				if(count($rec['sizes'])==1){
					return $rec['sizes'][0]['name'];
				}
				foreach($rec['sizes'] as $size){
					$opts[$size['name']]="{$size['name']}";
				}
				return buildFormButtonSelect('size',$opts,$params);
			}
			return $rec['size'];
		break;
		case 'color':
			$params['onclick']="wcommerceChangeProductAttribute(this);";
			$params['data-product_guid']=$rec['guid'];
			$params['data-product_name']=$rec['name'];
			$params['data-product_attr']=1;
			$opts=array();
			if(is_array($rec['colors'])){
				if(count($rec['colors'])==1){
					return $rec['colors'][0]['name'];
				}
				foreach($rec['colors'] as $color){
					$opts[$color['name']]="{$color['name']}";
				}
				return buildFormButtonSelect('color',$opts,$params);
			}
			return $rec['color'];
		break;
		case 'material':
			$params['onclick']="wcommerceChangeProductAttribute(this);";
			$params['data-product_guid']=$rec['guid'];
			$params['data-product_name']=$rec['name'];
			$params['data-product_attr']=1;
			$opts=array();
			if(is_array($rec['materials'])){
				if(count($rec['materials'])==1){
					return $rec['materials'][0]['name'];
				}
				foreach($rec['materials'] as $material){
					$opts[$material['name']]="{$material['name']}";
				}
				return buildFormButtonSelect('material',$opts,$params);
			}
			return $rec['material'];
		break;
			if(is_array($val2)){
				if(count($val2)==1){
					return $val2[0]['name'];
				}
				$opts=array();
				foreach($val2 as $val){
					$opts[$val['name']]="{$val['name']}";
				}
				return buildFormButtonSelect('material',$opts,$params);
			}
			return $val1;
		break;
	}
}
function wcommerceOrdersAddedit($id=0){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-enctype'=>'multipart/form-data',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/list",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_orders_content')",
		'-fields'=>getView('manage_orders_addedit_fields'),
		'-style_all'=>'width:100%',
		'name_options'=>array(
			'inputtype'=>'text',
		),
		'shipto_country_options'=>array(
			'inputtype'=>'select',
			'onchange'=>"wcommerceNav(this);",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_redraw",
			'data-field'=>'shipto_state',
			'data-div'=>'shipto_state_content',
			'tvals'=>wasqlGetCountries(),
			'dvals'=>wasqlGetCountries(1)
		),
		'shipto_state_options'=>array(
			'inputtype'=>'select',
			'tvals'=>wasqlGetStates(),
			'dvals'=>wasqlGetStates(1)
		),
		'shipto_address_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'50'
		),
		'related_products_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'150'
		)
	);
	if($id > 0){
		$opts['_id']=$id;
	}
	else{
		$opts['shipto_country']='US';
	}
	return addEditDBForm($opts);
}

function wcommerceGetSettings(){
	$params['-table']='wcommerce_settings';
	$params['active']=1;
	$recs=getDBRecords($params);
	$settings=array();
	foreach($recs as $rec){
		$key=strtolower($rec['name']);
		$value=$rec['value'];
		$settings[$key]=$value;
	}
	return $settings;
}
function wcommerceProducts($params=array()){
	$params['-table']='wcommerce_products';
	$params['active']=1;
	$recs=getDBRecords($params);
	//group by size,color,material
	$products=array();
	foreach($recs as $rec){
		$key=strtolower(trim($rec['name']));
		$products[$key][]=$rec;
	}
	$recs=array();
	foreach($products as $key=>$precs){
		$mods=array();
		foreach($precs as $prec){
			if(strlen($prec['size'])){
				$mods['sizes'][$prec['size']]['name']=$prec['size'];
				$mods['sizes'][$prec['size']]['quantity']+=$prec['quantity'];
			}
			if(strlen($prec['color'])){
				$mods['colors'][$prec['color']]['name']=$prec['color'];
				$mods['colors'][$prec['color']]['quantity']+=$prec['quantity'];
			}
			if(strlen($prec['material'])){
				$mods['materials'][$prec['material']]['name']=$prec['material'];
				$mods['materials'][$prec['material']]['quantity']+=$prec['quantity'];
			}
		}
		$rec=$precs[0];
		$rec['guid']=md5($rec['name']);
		foreach($mods as $k=>$mod){
			$mrecs=array();
			foreach($mod as $x=>$v){$mrecs[]=$v;}
			$rec[$k]=$mrecs;
		}
		$recs[]=$rec;
	}
	return $recs;
}
function wcommerceProductsList(){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_products',
		'-action'=>"/t/1/{$PAGE['name']}/manage_products/list",
		'-onsubmit'=>"return pagingSubmit(this,'wcommerce_products_content');",
		'-tableclass'=>"table striped bordered responsive",
		'setprocessing'=>0,
		'-listfields'=>'_id,active,featured,onsale,name,category,quantity,price,sale_price,sku,size,color,material,weight,photo_1,photo_2',
		'photo_1_image'=>1,
		'photo_2_image'=>1,
		'-editfields'=>'name,quantity,category,price,sale_price,sku,size,color,material,weight',
		'-sorting'=>1,
		'-export'=>1,
		'-order'=>'active desc,name',
		'quantity_class'=>'align-right',
		'price_class'=>'align-right',
		'name_class'=>'w_nowrap',
		'sale_price_class'=>'align-right',
		'weight_options'=>array(
			'class'=>'align-right',
			'displayname'=>'Weight (oz)'
		),
		'active_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'active',
			'data-value'=>"%active%",
			'data-one'=>'icon-mark w_success',
			'data-zero'=>'icon-mark w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-mark w_success',
			'icon_0'=>'icon-mark w_lgray'
		),
		'onsale_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'onsale',
			'data-value'=>"%onsale%",
			'data-one'=>'icon-tag w_success',
			'data-zero'=>'icon-tag w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-tag w_success',
			'icon_0'=>'icon-tag w_lgray'
		),
		'featured_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'featured',
			'data-value'=>"%featured%",
			'data-one'=>'icon-optimize w_warning',
			'data-zero'=>'icon-optimize w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-optimize w_warning',
			'icon_0'=>'icon-optimize w_lgray'
		),
		'_id_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_products/addedit/%_id%",
			'data-div'=>'centerpop'
		),
	);
	$opts['-quickfilters']=array(
		'Add'=>array(
			'icon'=>'icon-plus',
			'onclick'=>'wcommerceNav(this);',
			'data-href'=>"/t/1/{$PAGE['name']}/manage_products/addedit/0",
			'data-div'=>"centerpop",
			'class'=>"btn w_white"
			),
		'Featured'=>array(
			'icon'=>'icon-optimize',
			'filter'=>'featured eq 1',
			'class'=>"btn w_yellow"
			),
		'On Sale'=>array(
			'icon'=>'icon-tag',
			'filter'=>'onsale eq 1',
			'class'=>"btn w_green"
			),
		'Inactive'=>array(
			'icon'=>'icon-mark',
			'filter'=>'active eq 0',
			'class'=>"btn w_gray"
			)
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:space-between;align-items:center">
	<div class="w_small w_gray">Click on Id to edit entire record. Click on <span class="icon-edit"></span> to edit a single field.</div>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceProductsAddedit($id=0){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_products',
		'-enctype'=>'multipart/form-data',
		'-action'=>"/t/1/{$PAGE['name']}/manage_products/list",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_products_content')",
		'-fields'=>getView('manage_products_addedit_fields'),
		'-style_all'=>'width:100%',
		'name_options'=>array(
			'inputtype'=>'text',
		),
		'category_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(category) from wcommerce_products order by category",
			'autocomplete'=>'off'
		),
		'price_options'=>array(
			'inputtype'=>'number',
			'step'=>'any',
			'min'=>0
		),
		'sale_price_options'=>array(
			'inputtype'=>'number',
			'step'=>'any',
			'min'=>0
		),
		'size_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(size) from wcommerce_products order by size",
			'autocomplete'=>'off'
		),
		'color_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(color) from wcommerce_products order by color",
			'autocomplete'=>'off'
		),
		'material_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(material) from wcommerce_products order by material",
			'autocomplete'=>'off'
		),
		'details_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'150'
		),
		'related_products_options'=>array(
			'inputtype'=>'checkbox',
			'tvals'=>"select _id from wcommerce_products order by name",
			'dvals'=>"select _id,'. ',name from wcommerce_products order by name",
			'width'=>'3'
		)
	);
	//set 10 photo field options
	for($x=1;$x<11;$x++){
		$opts["photo_{$x}_options"]=array(
			'inputtype'=>'file',
			'autonumber'=>1,
			'path'=>'wcommerce_files'
		);
	}
	if($id > 0){
		$opts['_id']=$id;
		$opts['related_products_options']=array(
			'inputtype'=>'checkbox',
			'tvals'=>"select _id from wcommerce_products where _id <> {$id} order by name",
			'dvals'=>"select _id,'. ',name from wcommerce_products where _id <> {$id} order by name",
			'width'=>'3'
		);
	}
	else{
		$opts['active']=1;
	}
	return addEditDBForm($opts);
}
function wcommerceSettingsList(){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_settings',
		'-tableclass'=>"table striped bordered responsive",
		'-edit'=>1,
		'-sorting'=>1,
		'-order'=>'name',
		'-navonly'=>1,
		'name_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_settings/addedit/%_id%",
			'data-div'=>'centerpop'
		)
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:flex-end;align-items:center">
	<button type="button" class="button is-small btn btn-small is-success w_success" onclick="wcommerceNav(this);" data-href="/t/1/{$PAGE['name']}/manage_settings/addedit/0" data-div="centerpop"><span class="icon-plus"></span> <translate>Add</translate></button>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceSettingsAddedit($id=0){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_settings',
		'-action'=>"/t/1/{$PAGE['name']}/manage_settings",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_content')",
		'name_options'=>array(
			'inputtype'=>'text',
			'class'=>'input',
			'width'=>400
		),
		'value_options'=>array(
			'inputtype'=>'textarea',
			'class'=>'textarea',
			'width'=>400,
			'height'=>100
		)
	);
	if($id > 0){
		$opts['_id']=$id;
	}
	return addEditDBForm($opts);
}
function wcommerceInit($force=0){
	//check for wcommerce page
	$rtn='';
	$rec=getDBRecord(array('-table'=>'_pages','-where'=>"name='wcommerce' or permalink='wcommerce'",'-fields'=>'_id,name,permalink'));
	if($force==1 || !isset($rec['_id'])){
		//create a wcommerce page
		$opts=array(
			'-table'=>'_pages',
			'name'=>'wcommerce',
			'title'=>'wcommerce',
			'permalink'=>'wcommerce',
			'body'=>wcommercePageBody(),
			'js'=>wcommercePageJs(),
			'controller'=>wcommercePageController(),
			'description'=>'wcommerce management page',
			'-upsert'=>'body,controller,js',
			'_template'=>2
		);
		$ok=addDBRecord($opts);
		if(isNum($ok)){
			$rtn.="updated wcommerce page. <br />".PHP_EOL;
		}
		else{
			$rtn .= "ERROR updating wcommerce page:".printValue($ok);
		}
	}
	//check for schema
	if($force==1 || !isDBTable('wcommerce_products')){
		$ok=databaseAddMultipleTables(wcommerceSchema());
		$rtn.="updated wcommerce schema. <br />".PHP_EOL;
	}
	return $rtn;
}
function wcommercePageBody(){
	return <<<ENDOFBODY
<view:manage_portal>
<style type="text/css">

.product{
	position:relative;
}
.product .popup{
	max-height:0px;
	overflow:hidden;
	transition:* 0.5s ease-out;
}
.product:hover .popup{
	max-height:600px;
	transition: * 0.5s ease-in;;
}
</style>
<div class="w_bold w_biggest w_success" id="wcommerce" data-page="<?=pageValue('name');?>">
	<span class="icon-handshake w_success"></span> wCOMMERCE
</div>
<ul class="nav-tabs w_black">
	<!-- Orders -->
	<li class="active"><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_orders"><span class="icon-package"></span> <translate>Orders</translate></a></li>
	<!-- Products -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_products"><span class="icon-tag"></span> <translate>Products</translate></a></li>
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_products_preview"><span class="icon-eye"></span> <translate>Products Preview</translate></a></li>
	<!-- Settings -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_settings"><span class="icon-gear"></span> <translate>Settings</translate></a></li>
	<!-- Buyers -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_buyers"><span class="icon-users"></span> <translate>Buyers</translate></a></li>
	<!-- User menu -->
	<li><a href="#" onclick="return false" class="dropdown"><span class="icon-user"></span> <?=\$USER['username'];?></a>
		<div>
			<ul class="nav-list">
				<!-- Logoff -->
				<li class="right"><a href="/<?=pageValue('name');?>?_logoff=1"><span class="icon-logout"></span> <translate>Logoff</translate></a></li>
				<!-- Update -->
				<li style="margin-top:10px;border-top:1px solid #ccc;padding-top:10px;"><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_init" data-confirm="This will update the wcommerce module. Are you sure?"><span class="icon-refresh w_danger"></span> <translate>Update</translate></a></li>
			</ul>
		</div>
	</li>
</ul>
<div id="wcommerce_content">
	<?=renderView('manage_orders',\$orders,'orders');?>
</div>
<div style="display:none"><div id="wcommerce_nulldiv"></div></div>
</view:manage_portal>

<view:manage_redraw><?=wcommerceBuildField(\$field,'',\$value);?></view:manage_redraw>
<view:manage_init>
	<div class="w_bold w_big">wCommerce has been Updated</div>
	<div class="w_small w_gray">Restart postedit and clear your cache</div>
	<div><?=\$rtn;?></div>
</view:manage_init>

<view:manage_orders>
<div id="wcommerce_orders_content" style="margin-top:10px;">
	<?=wcommerceOrdersList();?>
</div>
</view:manage_orders>

<view:manage_orders_list>
	<?=wcommerceOrdersList();?>
	<?=buildOnLoad("removeId('centerpop');");?>
</view:manage_orders_list>

<view:manage_orders_addedit>
<div class="w_centerpop_title">Orders AddEdit</div>
<div class="w_centerpop_content" style="width:70vw;">
	<?=wcommerceOrdersAddedit(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_orders_addedit>

<view:manage_orders_addedit_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>User_ID</translate></label>[user_id]</div>
	<div style="margin:5px;"><label><translate>Date Ordered</translate></label>[date_ordered]</div>
	<div style="margin:5px;"><label><translate>Coupon</translate></label>[coupon]</div>
	<div style="margin:5px;"><label><translate>Discount</translate></label>[discount]</div>
</div>
<div style="font-size:1.2rem;background:#eee;padding:0 10px;margin:5px 0;font-weight: bold;color:#999;"><span class="icon-user2"></span> SHIP TO</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Firstname</translate></label>[shipto_firstname]</div>
	<div style="margin:5px;"><label><translate>Lastname</translate></label>[shipto_lastname]</div>
	<div style="margin:5px;"><label><translate>Email</translate></label>[shipto_email]</div>
	<div style="margin:5px;"><label><translate>Phone</translate></label>[shipto_phone]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="flex:1;margin:5px;"><label><translate>Address</translate></label>[shipto_address]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Country</translate></label>[shipto_country]</div>
	<div style="margin:5px;"><label><translate>State</translate></label><div id="shipto_state_content">[shipto_state]</div></div>
	<div style="margin:5px;"><label><translate>City</translate></label>[shipto_city]</div>
	<div style="margin:5px;"><label><translate>Postal Code</translate></label>[shipto_zip]</div>
	
</div>
<div style="font-size:1.2rem;background:#eee;padding:0 10px;margin:5px 0;font-weight: bold;color:#999;"><span class="icon-package"></span> SHIPPING</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Code</translate></label>[shipmethod_code]</div>
	<div style="margin:5px;"><label><translate>Price</translate></label>[shipmethod_price]</div>
	<div style="margin:5px;"><label><translate>Tracking Number</translate></label>[tracking_number]</div>
	<div style="margin:5px;"><label><translate>Date Shipped</translate></label>[date_shipped]</div>
	<div style="margin:5px;"><label><translate>Date Delivered</translate></label>[date_delivered]</div>
</div>
<div style="font-size:1.2rem;background:#eee;padding:0 10px;margin:5px 0;font-weight: bold;color:#999;"><span class="icon-cc"></span> PAYMENT</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Method</translate></label>[payment_name]</div>
	<div style="margin:5px;"><label><translate>Code</translate></label>[payment_code]</div>
	<div style="margin:5px;"><label><translate>Description</translate></label>[payment_description]</div>
	<div style="margin:5px;"><label><translate>Status</translate></label>[payment_status]</div>
	<div style="margin:5px;"><label><translate>Response</translate></label>[payment_response]</div>
</div>
</view:manage_orders_addedit_fields>

<view:manage_products>
<div id="wcommerce_products_content" style="margin-top:10px;">
	<?=wcommerceProductsList();?>
</div>
</view:manage_products>

<view:manage_products_list>
	<?=wcommerceProductsList();?>
	<?=buildOnLoad("removeId('centerpop');");?>
</view:manage_products_list>

<view:manage_products_addedit>
<div class="w_centerpop_title">Products AddEdit</div>
<div class="w_centerpop_content" style="width:70vw;">
	<?=wcommerceProductsAddedit(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_products_addedit>

<view:manage_products_addedit_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Active</translate></label>[active]</div>
	<div style="margin:5px;flex-grow:1;"><label><translate>Name</translate></label>[name]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>SKU</translate></label>[sku]</div>
	<div style="margin:5px;"><label><translate>Price</translate></label>[price]</div>
	<div style="margin:5px;"><label><translate>Sale Price</translate></label>[sale_price]</div>
	<div style="margin:5px;"><label><translate>Quantity</translate></label>[quantity]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Weight  (Ounces)</translate></label>[weight]</div>
	<div style="margin:5px;"><label><translate>Size (Optional)</translate></label>[size]</div>
	<div style="margin:5px;"><label><translate>Color (Optional)</translate></label>[color]</div>
	<div style="margin:5px;"><label><translate>Material (Optional)</translate></label>[material]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_1]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_2]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_3]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_4]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_5]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_6]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_7]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_8]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_9]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_10]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;flex-grow:1;"><label><translate>Details</translate></label>[details]</div>
	<div style="margin:5px;flex-grow:1;"><label><translate>Related Products</translate></label>[related_products]</div>
</div>
</view:manage_products_addedit_fields>

<view:manage_products_preview>
<div style="display:flex;justify-content: center;flex-wrap: wrap;align-items: flex-start;padding:1px;">
	<view:product>
	<div class="w_shadow" id="product_<?=\$product['guid'];?>" style="margin:15px;border-radius: 8px;display:flex;justify-content: flex-start;align-items: flex-end;">
		<div class="product" style="margin:15px;display:flex;flex-direction: column;">
			<img src="<?=\$product['photo_1'];?>"  style="width:250px;height:auto;border-radius: 10px;" />
			<div class="align-left" style="margin:5px;"><?=wcommerceBuildField('size',\$product);?></div>
				<div class="align-left" style="margin:5px;"><?=wcommerceBuildField('color',\$product);?></div>
				<div class="align-left" style="margin:5px;"><?=wcommerceBuildField('material',\$product);?></div>
		</div>
		<div class="product" style="margin:15px;display:flex;flex-direction: column;justify-content: space-between;align-self: stretch;">
			<div>
				<div class="align-right w_gray"><?=\$product['category'];?></div>
				<div class="align-left w_biggest"><?=\$product['name'];?></div>
				<div class="align-right w_bigger w_bold w_green">\$ <?=\$product['price'];?></div>
				<div class="align-left w_smaller" style="max-height:250px;max-width:200px;overflow: auto;"><?=nl2br(\$product['details']);?></div>
			</div>
			<div style="display:flex;justify-content: flex-start;">
				<input type="number" step="1" min="0" value="1" style="width:50px;" />
				<button class="btn btn-green" type="button" onclick="wcommerceAdd2Cart(this);" data-product_id="<?=\$product['_id'];?>" data-product_name="<?=\$product['name'];?>" style="margin-top:5px;display:flex;align-self: center;justify-content: center;"><span>ADD TO CART </span><span class="icon-heart" style="margin-left:5px;"></span></button>
			</div>
		</div>
	</div>
	</view:product>
	<?=renderEach('product',\$products,'product');?>
</div>
</view:manage_products_preview>

<view:manage_buyers>
<div id="wcommerce_buyers_content" style="margin-top:10px;">
	<?=wcommerceBuyersList();?>
</div>
</view:manage_buyers>

<view:manage_settings>
<div id="wcommerce_settings_content" style="margin-top:10px;">
	<?=wcommerceSettingsList();?>
</div>
</view:manage_settings>

<view:manage_settings_addedit>
<div class="w_centerpop_title">Settings AddEdit</div>
<div class="w_centerpop_content">
	<?=wcommerceSettingsAddedit(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_settings_addedit>

<view:login>
<?=userLoginForm(array('-action'=>'/'.pageValue('name')));?>
</view:login>
ENDOFBODY;
}
function wcommercePageJs(){
	return <<<ENDOFJS
function wcommerceChangeProductAttribute(el){
	let page=wcommercePageName();
	let div='product_'+el.dataset.product_guid;
	let url='/t/1/'+page+'/manage_change_product_attribute';
	let attrs=document.querySelectorAll('#product_'+el.dataset.product_guid+' input[data-product_attr]');
	let params={setprocessing:0,field:el.name,value:el.value,name:el.dataset.product_name};
	for(let i=0;i<attrs.length;i++){
		if(!attrs[i].checked){continue;}
		params[attrs[i].name]=attrs[i].value;
	}
	return ajaxGet(url,div,params);
}
function wcommerceAdd2Cart(el){
	let pdiv=getParent(el,'div');
	let qty=pdiv.querySelector('input').value;
	let page=wcommercePageName();
	let div='wcommerce_nulldiv';
	let url='/t/1/'+page+'/add2cart';
	let params={setprocessing:0,id:el.dataset.product_id,qty:qty,name:el.dataset.product_name};
	return ajaxGet(url,div,params);
}
function wcommerceNavTab(el){
	wacss.setActiveTab(el);
	return wcommerceNav(el);
}
function wcommerceNav(el){
	if(undefined != el.dataset.confirm && !confirm(el.dataset.confirm)){
		return false;
	}
	let p=el.dataset;
	p.setprocessing=0;
	if(undefined != el.value){
		p.value=el.value;
	}
	let div=el.dataset.div||'wcommerce_content';
	let href=el.dataset.href;
	return ajaxGet(href,div,p);
}
function wcommerceManageSetValue(el){
	let p=getParent(el,'td');
	let v=parseInt(p.dataset.value);
	let s=el.querySelector('span');
	if(v==1){
		p.dataset.value=0;
		s.className=p.dataset.zero;
	}
	else{
		p.dataset.value=1;
		s.className=p.dataset.one;
	}
	let page=wcommercePageName();
	let url='/t/1/'+page+'/manage_setvalue/'+p.dataset.table+'/'+p.dataset.field+'/'+p.dataset.id+'/'+p.dataset.value;
	return ajaxGet(url,'wcommerce_nulldiv',{setprocessing:0});
}
function wcommercePageName(){
	let el=document.querySelector('#wcommerce[data-page]');
	if(undefined==el){return 'wcommerce';}
	return el.dataset.page;
}
ENDOFJS;
}
function wcommercePageController(){
	return <<<ENDOFCONTROLLER
<?php
//require user
if(!isAdmin()){
	setView('login',1);
	return;
}
global \$USER;
global \$PASSTHRU;
global \$PAGE;
loadExtras('wcommerce');
switch(strtolower(\$PASSTHRU[0])){
	case 'user_profile':
		setView(\$PASSTHRU[0],1);
	break;
	case 'add2cart':
		echo printValue(\$_REQUEST);exit;
	break;
	case 'manage_change_product_attribute':
		echo printValue(\$_REQUEST);exit;
	break;
	case 'manage_setvalue':
		\$table=\$PASSTHRU[1];
		\$field=\$PASSTHRU[2];
		\$id=(integer)\$PASSTHRU[3];
		\$value=(integer)\$PASSTHRU[4];
		\$ok=editDBRecordById(\$table,\$id,array(\$field=>\$value));
		echo "Table:{\$table}, Field:{\$field}, Id:{\$id}, Value: {\$value}, Result: ".printValue(\$ok);exit;
	break;
	case 'manage_redraw':
		\$field=\$_REQUEST['field'];
		\$value=\$_REQUEST['value'];
		setView(\$PASSTHRU[0],1);
		return;
	break;
	case 'manage_init':
		\$rtn=wcommerceInit(1);
		setView(\$PASSTHRU[0],1);
		return;
	break;
	case 'manage_orders':
		switch(strtolower(\$PASSTHRU[1])){
			case 'list':
				setView('manage_orders_list',1);
				return;
			break;
			case 'addedit':
				\$id=\$PASSTHRU[2];
				setView('manage_orders_addedit',1);
				return;
			break;
		}
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_products':
		switch(strtolower(\$PASSTHRU[1])){
			case 'list':
				setView('manage_products_list',1);
				return;
			break;
			case 'addedit':
				\$id=\$PASSTHRU[2];
				setView('manage_products_addedit',1);
				return;
			break;
		}
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_products_preview':
		\$products=wcommerceProducts();
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_settings':
		switch(strtolower(\$PASSTHRU[1])){
			case 'addedit':
				\$id=\$PASSTHRU[2];
				setView('manage_settings_addedit',1);
				return;
			break;
		}
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_buyers':
		setView(\$PASSTHRU[0],1);
	break;
	default:
		setView('manage_portal');
	break;
}
?>
ENDOFCONTROLLER;
}

function wcommerceSchema(){
	return <<<ENDOFSCHEMA
wcommerce_products
	name varchar(150) NOT NULL
	sku varchar(25)
	category varchar(50)
	size varchar(25)
	color varchar(25)
	material varchar(25)
	quantity int NOT NULL Default 1
	price float(12,2)
	sale_price float(12,2)
	active tinyint(1) NOT NULL Default 0
	onsale tinyint(1) NOT NULL Default 0
	featured tinyint(1) NOT NULL Default 0
	related_products JSON
	details text
	photo_1 varchar(255)
	photo_2 varchar(255)
	photo_3 varchar(255)
	photo_4 varchar(255)
	photo_5 varchar(255)
	photo_6 varchar(255)
	photo_7 varchar(255)
	photo_8 varchar(255)
	photo_9 varchar(255)
	photo_10 varchar(255)
	weight int
wcommerce_products_reviews
	product_id int NOT NULL
	email varchar(255)
	comment varchar(500)
	review int
	active tinyint(1) NOT NULL Default 0
wcommerce_orders
	user_id int
	shipto_firstname varchar(150)
	shipto_lastname varchar(150)
	shipto_company varchar(150)
	shipto_address varchar(255)
	shipto_city varchar(30)
	shipto_state varchar(20)
	shipto_zip varchar(20)
	shipto_country varchar(5)
	shipto_phone varchar(30)
	shipto_email varchar(255)
	date_ordered datetime
	date_shipped datetime
	date_delivered datetime
	shipmethod_code varchar(25)
	shipmethod_price float(12,2)
	coupon varchar(25)
	items_count int
	items_total int
	discount float(12,2)
	tax float(12,2)
	order_total float(12,2)
	payment_description varchar(255)
	payment_name varchar(150)
	payment_response varchar(255)
	payment_status varchar(25)
	payment_code varchar(150)
	tracking_number varchar(40)
wcommerce_orders_items
	order_id int NOT NULL Default 0
	product_id int NOT NULL
	guid varchar(150) NOT NULL
	name varchar(150) NOT NULL
	sku varchar(25)
	size varchar(25)
	color varchar(25)
	material varchar(25)
	quantity int NOT NULL Default 1
	price float(12,2)
	sale_price float(12,2)
	active tinyint(1) NOT NULL Default 0
	related_products JSON
	details text
	photo_1 varchar(255)
wcommerce_coupons
	coupon varchar(25) NOT NULL UNIQUE
	description varchar(255)
	discount float(12,2)
	expire_date date
	times_used int NOT NULL Default 0
	times_valid int NOT NULL Default 1
	active tinyint(1) NOT NULL Default 0
	product_ids json
wcommerce_settings
	name varchar(100) NOT NULL UNIQUE
	value text
ENDOFSCHEMA;
}
?>