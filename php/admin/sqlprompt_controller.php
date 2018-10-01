<?php
	global $CONFIG;
	switch(strtolower($_REQUEST['func'])){
		case 'setdb':
			switch(strtolower($_REQUEST['db'])){
				case 'postgresql':
					loadExtras('postgresql');
					$tables=postgresqlGetDBTables();
				break;
				default:
					$tables=getDBTables();
				break;
			}
			setView('tables_fields',1);
			return;
		break;
		case 'sql':
			$view='results';
			$_SESSION['sql_full']=$_REQUEST['sql_full'];
			$sql_select=stripslashes($_REQUEST['sql_select']);
			$sql_full=stripslashes($_REQUEST['sql_full']);
			if(strlen($sql_select) && $sql_select != $sql_full){
				$_SESSION['sql_last']=$sql_select;
				$view='block_results';
			}
			else{
				$_SESSION['sql_last']=$sql_full;
				//run the query where the cursor position is
				$queries=preg_split('/\;/',$sql_full);
				//echo printValue($queries);exit;
				$cpos=$_REQUEST['cursor_pos'];
				if(count($queries) > 1){
					$p=0;
					foreach($queries as $query){
						$end=$p+strlen($query);
						if($cpos > $p && $cpos < $end){
							$_SESSION['sql_last']=$query;
							$view='block_results';
							break;
						}
						$p=$end;
					}
				}
				else{
					$_SESSION['sql_last']=$sql_full;
					$view='results';
				}
			}
			switch(strtolower($_REQUEST['db'])){
				case 'postgresql':
					loadExtras('postgresql');
					$recs=postgresqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				default:
					$recs=getDBRecords($_SESSION['sql_last']);
				break;
			}
			setView($view,1);
			return;
		break;
		case 'export':
			$recs=getDBRecords($_SESSION['sql_last']);
			$csv=arrays2CSV($recs);
			pushData($csv,'csv');
			exit;
		break;
		case 'fields':
			$table=addslashes($_REQUEST['table']);
			$fields=getDBFieldInfo($table);
			//echo printValue($fields);exit;
			setView('fields',1);
			return;
		break;
		case 'export':
			$id=addslashes($_REQUEST['id']);
			$report=getDBRecord(array('-table'=>'_reports','active'=>1,'_id'=>$id));
			$report=reportsRunReport($report);
			$csv=arrays2CSV($report['recs']);
			pushData($csv,'csv',$report['name'].'.csv');
			exit;
			return;
		break;
		default:
			$tabs=array();
			if(isset($CONFIG['postgresql_dbname'])){$tabs[]=array('name'=>'postgresql');}
			if(isset($CONFIG['oracle_dbname'])){$tabs[]=array('name'=>'oracle');}
			if(isset($CONFIG['mssql_dbname'])){$tabs[]=array('name'=>'mssql');}
			if(isset($CONFIG['sqlite_dbname'])){$tabs[]=array('name'=>'sqlite');}
			//echo printValue($CONFIG).printValue($tabs);exit;
			$tables=getDBTables();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
