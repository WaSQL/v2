<?php
	switch(strtolower($_REQUEST['func'])){
		case 'sql':
			$_SESSION['sql_full']=$_REQUEST['sql_full'];
			$sql_select=stripslashes($_REQUEST['sql_select']);
			$sql_full=stripslashes($_REQUEST['sql_full']);
			if(strlen($sql_select) && $sql_select != $sql_full){
				$_SESSION['sql_last']=$sql_select;
				$recs=getDBRecords($sql_select);
				setView('results',1);
			}
			else{
				$_SESSION['sql_last']=$sql_full;
				$recs=getDBRecords($sql_full);
				setView('results',1);
			}

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
			$tables=getDBTables();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
