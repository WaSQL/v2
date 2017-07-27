drop procedure Commissions.sp_Customer_Clear;
create procedure Commissions.sp_Customer_Clear
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			20-Jul-2017
*
* @describe		Clears Customer summary values according to flags set for the period batch
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @example		call Commissions.sp_Customer_Clear(10, 0);
-------------------------------------------------------*/
(pn_Period_id		Integer
,pn_Period_Batch_id	Integer)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Set_Volume           	Integer;
    declare ln_Set_Volume_Lrp       	Integer;
    declare ln_Set_Volume_FS        	Integer;
    declare ln_Set_Volume_Retail    	Integer;
    declare ln_Set_Volume_EGV       	Integer;
    declare ln_Set_Volume_TV       		Integer;
    declare ln_Set_Volume_TW_CV     	Integer;
    declare ln_Set_Volume_Org       	Integer;
    declare ln_isOpen					integer = gl_Period_isOpen(:pn_Period_id);
    
    if :ln_isOpen = 1 then
		update customer
		set vol_1 = 0
		  , vol_2 = 0
		  , vol_3 = 0
		  , vol_4 = 0
		  , vol_5 = 0
		  , vol_6 = 0
		  , vol_7 = 0
		  , vol_8 = 0
		  , vol_9 = 0
		  , vol_10 = 0
		  , vol_11 = 0
		  , vol_12 = 0
		  , vol_13 = 0
		  , vol_14 = 0
		  , vol_15 = 0;
	else
		Select 
	        set_volume
	      , set_volume_lrp
	      , set_volume_fs
	      , set_volume_retail
	      , set_volume_egv
	      , set_volume_tv
	      , set_volume_tw_cv
	      , set_volume_org
		Into 
	        ln_Set_Volume
	      , ln_Set_Volume_Lrp
	      , ln_Set_Volume_FS
	      , ln_Set_Volume_Retail
	      , ln_Set_Volume_EGV
	      , ln_Set_Volume_TV
	      , ln_Set_Volume_TW_CV
	      , ln_Set_Volume_Org
		From  period_batch
		Where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;

		if 
			   :ln_Set_Volume = 1 
			or :ln_Set_Volume_Lrp = 1
			or :ln_Set_Volume_FS = 1
			or :ln_Set_Volume_Retail = 1
			or :ln_Set_Volume_EGV = 1
			or :ln_Set_Volume_TV = 1
			or :ln_Set_Volume_TW_CV = 1
			or :ln_Set_Volume_Org = 1
		then
			update customer_history
			set vol_1 = 	map(:ln_Set_Volume,1,0,vol_1)
			  , vol_2 = 	map(:ln_Set_Volume_Lrp,1,0,vol_2)
			  , vol_3 = 	0
			  , vol_4 = 	map(:ln_Set_Volume_Retail,1,0,vol_4)
			  , vol_5 = 	map(:ln_Set_Volume_FS,1,0,vol_5)
			  , vol_6 = 	map(:ln_Set_Volume,1,0,vol_6)
			  , vol_7 = 	map(:ln_Set_Volume_Lrp,1,0,vol_7)
			  , vol_8 = 	0
			  , vol_9 = 	map(:ln_Set_Volume_Retail,1,0,vol_9)
			  , vol_10 = 	map(:ln_Set_Volume_FS,1,0,vol_10)
			  , vol_11 =  	map(:ln_Set_Volume_EGV,1,0,vol_11)
			  , vol_12 = 	0
			  , vol_13 = 	map(:ln_Set_Volume_Org,1,0,vol_13)
			  , vol_14 = 	map(:ln_Set_Volume_TV,1,0,vol_14)
			  , vol_15 = 	map(:ln_Set_Volume_TW_CV,1,0,vol_15)
			where period_id = :pn_Period_id
			and batch_id = :pn_Period_Batch_id;
		end if;
	end if;
	
	commit;

end;