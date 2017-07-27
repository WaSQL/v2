DROP PROCEDURE SP_COMMISSION_RUN;
create Procedure Commissions.sp_Commission_Run
/*-------------------------------------------------------
* @author		Larry Cardon
* @category		Stored Procedure
* @date			5-Jul-2017
*
* @describe		Runs all commission engines according to flags set for the period batch
*
* @param		integer		pn_Period_id 		Commission Period
* @param		integer		pn_Period_Batch_id 	Commission Batch
*
* @example		call Commissions.sp_Cap_Req_Snap(10);
-------------------------------------------------------*/
(pn_Period_id		integer
,pn_Period_Batch_id	integer)
	LANGUAGE SQLSCRIPT
   	DEFAULT SCHEMA Commissions
AS

Begin
	declare ln_Clear				   	Integer;
	declare ln_Set_Level				integer;
	declare ln_Set_Volume           	Integer;
	declare ln_Set_Volume_Lrp       	Integer;
	declare ln_Set_Volume_FS        	Integer;
	declare ln_Set_Volume_Retail    	Integer;
	declare ln_Set_Volume_EGV       	Integer;
	declare ln_Set_Volume_TV       		Integer;
	declare ln_Set_Volume_TW_CV     	Integer;
	declare ln_Set_Volume_Org       	Integer;
   	declare ln_Set_Rank             	Integer;
   	declare ln_Set_Earning_1  			Integer;
   	declare ln_Set_Earning_2         	Integer;
   	declare ln_Set_Earning_3  			Integer;
   	declare ln_Set_Earning_4         	Integer;
   	declare ln_Set_Earning_5         	Integer;
   	declare ln_Set_Earning_6         	Integer;
   	declare ln_Set_Earning_7         	Integer;
   	declare ln_Set_Earning_8         	Integer;
   	declare ln_Set_Earning_9         	Integer;
   	declare ln_Set_Earning_10        	Integer;
   	declare ln_Set_Earning_11        	Integer;
   	declare ln_Set_Earning_12        	Integer;
   	declare ln_Set_Earning_13        	Integer;
	
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		ln_Clear = 1;
		ln_Set_Level = 1;
		ln_Set_Volume = 1;
		ln_Set_Volume_Lrp = 1;
		ln_Set_Volume_FS = 1;
		ln_Set_Volume_Retail = 1;
		ln_Set_Volume_EGV = 1;
		ln_Set_Volume_TV = 1;
		ln_Set_Volume_TW_CV = 1;
		ln_Set_Volume_Org = 1;
		ln_Set_Rank = 1;
		ln_Set_Earning_1 = 0;
		ln_Set_Earning_2 = 0;
		ln_Set_Earning_3 = 0;
		ln_Set_Earning_4 = 0;
		ln_Set_Earning_5 = 0;
		ln_Set_Earning_6 = 0;
		ln_Set_Earning_7 = 0;
		ln_Set_Earning_8 = 0;
		ln_Set_Earning_9 = 0;
		ln_Set_Earning_10 = 0;
		ln_Set_Earning_11 = 0;
		ln_Set_Earning_12 = 0;
		ln_Set_Earning_13 = 0;
	else
		Select 
        	  clear_flag
        	, set_level
      		, set_volume
      		, set_volume_lrp
 			, set_volume_fs
  			, set_volume_retail
  			, set_volume_egv
  			, set_volume_tv
  			, set_volume_tw_cv
  			, set_volume_org
			, set_rank
			, set_Earning_1
			, set_Earning_2
			, set_Earning_3
			, set_Earning_4
			, set_Earning_5
			, set_Earning_6
			, set_Earning_7
			, set_Earning_8
			, set_Earning_9
			, set_Earning_10
			, set_Earning_11
			, set_Earning_12
			, set_Earning_13
		Into 
        	  ln_Clear
        	, ln_Set_Level
      		, ln_Set_Volume
			, ln_Set_Volume_Lrp
			, ln_Set_Volume_FS
			, ln_Set_Volume_Retail
			, ln_Set_Volume_EGV
			, ln_Set_Volume_TV
			, ln_Set_Volume_TW_CV
			, ln_Set_Volume_Org
			, ln_Set_Rank
			, ln_Set_Earning_1
			, ln_Set_Earning_2
			, ln_Set_Earning_3
			, ln_Set_Earning_4
			, ln_Set_Earning_5
			, ln_Set_Earning_6
			, ln_Set_Earning_7
			, ln_Set_Earning_8
			, ln_Set_Earning_9
			, ln_Set_Earning_10
			, ln_Set_Earning_11
			, ln_Set_Earning_12
			, ln_Set_Earning_13
		From  period_batch
		Where period_id = :pn_Period_id
		and batch_id = :pn_Period_Batch_id;
	end if;

	-- Clear Commission
	call sp_Commission_Clear(:pn_Period_id, :pn_Period_Batch_id);
   
   Update period_batch
   Set
       beg_date_run = current_timestamp
      ,end_date_run = Null
   Where period_id = :pn_Period_id
   and batch_id = :pn_Period_Batch_id;
   
   commit;
	
   -- Set Level
   If :ln_Set_Level = 1 Then
      call sp_Customer_Hier_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
	
   -- Set Volumes
   If :ln_Set_Volume = 1 Then
      call sp_Volume_Pv_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set Retail Volumes
   If :ln_Set_Volume_Retail = 1 Then
      call sp_Volume_Retail_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   -- Set LRP Volumes
   If ln_Set_Volume_Lrp = 1 Then
      call sp_Volume_Lrp_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
        
   -- Set Fast Start Volumes
   If :ln_Set_Volume_FS = 1 Then
      call sp_Volume_Fs_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
      
   -- Set EGV Volumes
   If :ln_Set_Volume_EGV = 1 Then
      call sp_Volume_Egv_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set TV Volumes
   If :ln_Set_Volume_TV = 1 Then
      call sp_Volume_Tv_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
   
   -- Set Taiwan Volumes
   If :ln_Set_Volume_TW_CV = 1 Then
      call sp_Volume_Tw_Set(:pn_Period_id, :pn_Period_Batch_id);
   End If;
         
   :pn_Period_Batch_id) a
			left outer join customer_type at
			 	on at.type_id = a.type_id
			left outer join gl_Exchange(:pn_Period_id) x2
			  	on x2.currency = a.currency
			left outer join customer_type t2
			  	on a.type_id = t2.type_id
			, gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
		Where t.customer_id = a.customer_id
		and c.customer_id = a.sponsor_id
		And ifnull(t2.has_retail,-1) = 1
		And ifnull(t1.has_downline,-1) = 1;
	--end if;
	
end; urrency				varchar(5)
			,pv							decimal(18,8)
			,cv							decimal(18,8))
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

begin
	/*
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,t.from_country
		     ,t.from_currency
		     ,t.to_currency
		     ,t.pv
		     ,t.cv
		From  gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  on t.transaction_ref_id = r.transaction_id
			, customer c
			  left outer join customer_type t1
			  on c.type_id = t1.type_id
	   	Where t.customer_id = c.customer_id
	   	And t.period_id = :pn_Period_id
	   	And ifnull(t1.has_downline,-1) = 1
	   	and ifnull(t.type_id,4) <> 0
	   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.entry_date,t.entry_date)) <= 60;
	else
	*/
		return
		Select 
		      t.period_id
		     ,t.batch_id
		     ,t.customer_id
		     ,t.transaction_id
		     ,t.transaction_ref_id
		     ,t.type_id
		     ,t.category_id
		     ,t.entry_date
		     ,t.order_number
		     ,t.from_country
		     ,t.from_currency
		     ,t.to_currency
		     ,t.pv
		     ,t.cv
		From  gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) t
			  left outer join gl_Volume_Pv_Detail(:pn_Period_id, :pn_Period_Batch_id) r
			  	on t.transaction_ref_id = r.transaction_id
			, gl_Customer(:pn_Period_id, :pn_Period_Batch_id) c
			  left outer join customer_type t1
			  	on c.type_id = t1.type_id
	   	Where t.customer_id = c.customer_id
	   	And t.period_id = c.period_id
	   	And c.per