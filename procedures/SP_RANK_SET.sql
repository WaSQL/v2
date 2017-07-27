DROP PROCEDURE SP_RANK_SET;
create procedure Commissions.sp_Rank_Set(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Max_Level	integer;
	declare ln_Cust_Level	integer;
	declare ld_Curr_Date	timestamp;
    
	Update period_batch
	Set beg_date_rank = current_timestamp
      ,end_date_rank = Null
   	Where period_id = :pn_Period_id
   	and batch_id = :pn_Period_Batch_id;
                  
   	Commit;
   	
   	-- ------------------------------------------------------------------------------------------------------------------------------
	if gl_Period_isOpen(:pn_Period_id) = 1 then
		select current_timestamp
		into ld_Curr_Date
		from dummy;
	   		
		lc_Customer =
			select
				 c.customer_id							as customer_id
				,c.sponsor_id							as sponsor_id
				,c.enroller_id							as enroller_id
				,c.rank_id								as rank_id
				,c.type_id								as type_id
				,c.status_id							as status_id
				,c.vol_1								as vol_1
				,c.vol_4								as vol_4
				,c.vol_11								as vol_11
				,c.vol_13								as vol_13
				,ifnull(v.version_id,1)					as version_id
				,ifnull(w.flag_type_id,0)				as flag_type_id
				,ifnull(w.flag_value,0)					as flag_value
			from customer c
				left outer join version v
					on c.country = v.country
					and v.version_id in (1,2)
				left outer join customer_flag w
					on c.customer_id = w.customer_id
					and w.flag_type_id in (3,4,5);
			
		lc_Cust_Flag =
			select
				 customer_id
				,flag_type_id
				,flag_value
			from customer_flag
			where flag_type_id = 1;
			
		lc_Require_Leg =
			select *
			from rank_req_template;
			
		lc_Cust_Level = 
			select
				 node_id 						as customer_id
				,parent_id 						as sponsor_id
				,enroller_id					as enroller_id
				,rank_id						as rank_id
				,type_id						as type_id
				,status_id						as status_id
				,vol_1							as vol_1
				,vol_4							as vol_4
				,vol_11							as vol_11
				,vol_13							as vol_13
				,version_id						as version_id
				,flag_type_id					as flag_type_id
				,flag_value						as flag_value
				,hierarchy_level				as hier_level
			from HIERARCHY ( 
				 	SOURCE ( select customer_id AS node_id, sponsor_id AS parent_id, t.*
				             from :lc_Customer t
				             --order by customer_id
				           )
		    		Start where customer_id = 3);
		    		        
	    select max(hier_level)
	    into ln_Max_Level
	    from :lc_Cust_Level;
		
		-- Process All Distributors From the Bottom Up
		for ln_Cust_Level in reverse 0..:ln_Max_Level do
			lc_Qual_Leg =
				select ql.customer_id, ql.sponsor_id, ql.leg_customer_id, ql.leg_enroller_id, ql.leg_rank_id, ifnull(f.flag_type_id,0) as flag_type_id
				from customer_qual_leg ql
					 	left outer join customer_history_flag f
						on f.customer_id = ql.customer_id;
	 		
			lr_dst_level = 
				select -- Find Distributors matching requirments
					   h.customer_id
					 , h.sponsor_id
					 , h.enroller_id
					 , case h.flag_type_id
					 		when 3 then	greatest(h.flag_value,max(q.rank_id))
					 		when 4 then least(h.flag_value,max(q.rank_id))
					 		when 5 then h.flag_value
					 		else max(q.rank_id) end as new_rank_id
					 , 1 as rank_qual
				from :lc_Cust_Level h, :lc_Require_Leg q
				where q.version_id = h.version_id
			   	And h.type_id = 1
			   	and h.status_id in (1, 4)
			   	and h.hier_level = :ln_Cust_Level
				and ((h.vol_1 + h.vol_4) >= q.vol_1 or (h.vol_11 >= q.vol_3 and h.version_id = 2))
				and h.vol_13 >= q.vol_2
				and (select count(*)
					 from (
						 select customer_id, sponsor_id, max(leg_rank_id) as leg_rank_id 
						 from :lc_Qual_Leg
						 where sponsor_id = h.customer_id
						 and sponsor_id = leg_enroller_id
						 --and leg_rank_id >= 1
						 and leg_rank_id >= q.leg_rank_id
						 group by customer_id, sponsor_id)) >= q.leg_rank_count
				group by h.customer_id, h.sponsor_id, h.enroller_id, h.flag_type_id, h.flag_value;
				
			-- Update Level with Calculated New Ranks	
			replace customer (customer_id, rank_id, rank_qual)
			select customer_id, new_rank_id, rank_qual
			from :lr_dst_level;
			
			commit;
	
			-- Write Ranks To Qual Leg Table
			replace customer_qual_leg (customer_id, leg_customer_id, entry_date, sponsor_id, leg_enroller_id, leg_rank_id) 
			select 
				 customer_id
				,customer_id as leg_customer_id
				,:ld_Curr_Date
				,sponsor_id
				,enroller_id as leg_enroller_id
				,new_rank_id
			from :lr_dst_level
			union all
			select
				 l.customer_id
				,h.leg_customer_id
				,:ld_Curr_Date
				,l.sponsor_id
				,h.leg_enroller_id
				,h.leg_rank_id
			from :lc_Cust_Level l, :lc_Qual_Leg  h
			where l.customer_id = h.sponsor_id
			and l.hier_level = :ln_Cust_Level
			and h.leg_enroller_id <> h.sponsor_id;
			
			commit;
	
			-- Clean Up Garbage
			delete
			from customer_qual_leg
			where sponsor_id  :pn_Period_Batch_id) a
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
	   	And c.period_id = :pn_Period_id
	   	and c.batch_id = :pn_Period_Batch_id
	   	And ifnull(t1.has_downline,-1) = 1
	   	and ifnull(t.type_id,4) <> 0
	   	And days_between(ifnull(c.comm_status_date,c.entry_date),ifnull(r.entry_date,t.entry_date)) <= 60;
	--end if;

end; 										as sponsor_id
			,c.enroller_id													as enroller_id
			,c.country														as country
			,e.currency														as currency
			,e.rate															as exchange_rate
			,e.round_factor													as round_factor
			,ifnull(c.comm_status_date,
				case when ifnull(t1.has_faststart,0) = 1 then c.entry_date						-- Type Wellness, Professional and Wholesale default to entry_date
				else to_date('1/1/2000','mm/dd/yyyy') end) 					as comm_status_date -- All other default to 1/1/2000
			,c.entry_date													as entry_date
			,ifnull(v.version_id,1)				              g      create procedure commissions.CUSTOMER_FLAG_DELETE
/*--------------------------------------------------
* @author       Del Stirling
* @category     stored procedure
* @date			5/2/2017
*
* @describe     Deletes records in the customer_flag table based on JSON input
*
* @param		nvarchar pn_json
* @out_param	varchar ps_result
* @example      call customer_flag_delete('[{"pn_Customer_id":1247,"pn_Period_id":13,"pn_flag_type_id":2,"pn_flag_value":"USA","pn_beg_date":null,"pn_end_date":null,"pn_flag":1}{"pn_Customer_id":1248,"pn_Period_id":14,"pn_flag_type_id":1,"pn_flag_value":"CHN","pn_beg_date":null,"pn_end_date":null,"pn_flag":1}]', ?);
-------------------------------------------------------*/
	(
	pn_json 			nvarchar(5000)
	, out ps_result 	varchar(100))
	
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
	DEFAULT SCHEMA commissions
as
BEGIN
	declare ln_record_num integer = 0;
	declare ln_column_num integer;
	declare ls_record varchar(5000) = '';
	declare ls_column_name varchar(5000);
	declare ls_column_val varchar(5000);
	
	declare la_customer_id integer array;
	declare la_flag_type_id integer array;
	declare la_flag_value varchar(100) array;
	declare la_beg_date date array;
	declare la_end_date date array;
	
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
	  ps_result = 'Error ' || ::SQL_ERROR_CODE || ' - ' || ::SQL_ERROR_MESSAGE;
	END;

	while :ls_record is not null do
		ln_record_num = ln_record_num + 1;
		ln_column_num = 1;
		ls_column_name = '';
		select substr_regexpr('({[^{}]*})' in :pn_json occurrence :ln_record_num)
		into ls_record 
		from dummy;

		while (:ls_column_name is not null) do 
			select substr_regexpr('"([a-z0-9_]+)":"?([a-z0-9]*)"?[,}]' flag 'i' in :ls_record occurrence :ln_column_num group 1) 
				, substr_regexpr('"([a-z0-9_]+)":"?([a-z0-9]*)"?[,}]' flag 'i' in :ls_record occurrence :ln_column_num group 2)
			into ls_column_name
				, ls_column_val
			from dummy;
			ln_column_num = :ln_column_num + 1;
			if (:ls_column_name is not null) then
				if lower(:ls_column_val) = 'null' then 
					ls_column_val = null; 
				end if;
				if lower(:ls_column_name) = 'pn_customer_id' then
					la_customer_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'pn_flag_type_id' then
					la_flag_type_id[:ln_record_num] = to_number(:ls_column_val);
				elseif lower(:ls_column_name) = 'pn_flag_value' then
					la_flag_value[:ln_record_num] = :ls_column_val;
				elseif lower(:ls_column_name) = 'pn_beg_date' then
					la_beg_date[:ln_record_num] = to_date(ls_column_val);
				elseif lower(:ls_column_name) = 'pn_end_date' then
					la_end_date[:ln_record_num] = to_date(ls_column_val);
				end if;
			end if;
		end while;
	end while;
	value_tab = UNNEST(:la_customer_id,:la_flag_type_id,:la_flag_value,:la_beg_date,:la_end_date) 
		AS ("CUSTOMER_ID","FLAG_TYPE_ID","FLAG_VALUE","BEG_DATE","END_DATE");

	delete 
	from customer_flag
	where exists (select customer_id, flag_type_id from :value_tab t where t.customer_id = customer_flag.customer_id and t.flag_type_id = customer_flag.flag_type_id);
	
	ps_result :='success';
END; stomer c
			left outer join :lc_Exchange x1
			  	on x1.currency = c.currency
			left outer join customer_type t1
			  	on c.type_id = t1.type_id
			left outer join customer_type ct
			 	on ct.type