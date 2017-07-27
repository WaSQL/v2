drop procedure Commissions.sp_Customer_Snap;
create procedure Commissions.sp_Customer_Snap(
					  pn_Period_id		int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	declare ln_Batch_id			integer = 0;
	
	select max(batch_id)
	into ln_Batch_id
	from period_batch 
	where period_id = :pn_Period_id;
		 
	if :ln_Batch_id = 0 then
		insert into customer_history
		select
			 customer_id				as customer
			,:pn_Period_id				as period_id
			,:ln_Batch_id				as batch_id
			,customer_name				as customer_name
			,source_key_id				as source_key_id
			,source_id					as source_id
			,type_id					as type_id
			,status_id					as status_id
			,sponsor_id					as sponsor_id
			,enroller_id				as enroller_id
			,country					as country
			,currency					as currency
			,comm_status_date			as comm_status_date
			,entry_date					as entry_date
			,termination_date			as termination_date
			,rank_id					as rank_id
			,rank_high_id				as rank_high_id
			,rank_high_type_id			as rank_high_type_id
			,rank_qual					as rank_qual
			,0							as hier_level
			,0							as hier_rank
			,vol_1						as vol_1
			,vol_2						as vol_2
			,vol_3						as vol_3
			,vol_4						as vol_4
			,vol_5						as vol_5
			,vol_6						as vol_6
			,vol_7						as vol_7
			,vol_8						as vol_8
			,vol_9						as vol_9
			,vol_10						as vol_10
			,vol_11						as vol_11
			,vol_12						as vol_12
			,vol_13						as vol_13
			,vol_14						as vol_14
			,vol_15						as vol_15
			,0							as payout_1_cap
			,0							as payout_1
			,0							as payout_2
			,0							as payout_3
			,0							as payout_4
			,0							as payout_5
			,0							as payout_6
			,0							as payout_7
			,0							as payout_8
			,0							as payout_9
			,0							as payout_10
			,0							as payout_11
			,0							as payout_12
			,0							as payout_13
		from customer;
	else
		insert into customer_history
		select
			 customer_id				as customer
			,:pn_Period_id				as period_id
			,:ln_Batch_id				as batch_id
			,customer_name				as customer_name
			,source_key_id				as source_key_id
			,source_id					as source_id
			,type_id					as type_id
			,status_id					as status_id
			,sponsor_id					as sponsor_id
			,enroller_id				as enroller_id
			,country					as country
			,currency					as currency
			,comm_status_date			as comm_status_date
			,entry_date					as entry_date
			,termination_date			as termination_date
			,rank_id					as rank_id
			,rank_high_id				as rank_high_id
			,rank_high_type_id			as rank_high_type_id
			,rank_qual					as rank_qual
			,0							as hier_level
			,0							as hier_rank
			,vol_1						as vol_1
			,vol_2						as vol_2
			,vol_3						as vol_3
			,vol_4						as vol_4
			,vol_5						as vol_5
			,vol_6						as vol_6
			,vol_7						as vol_7
			,vol_8						as vol_8
			,vol_9						as vol_9
			,vol_10						as vol_10
			,vol_11						as vol_11
			,vol_12						as vol_12
			,vol_13						as vol_13
			,vol_14						as vol_14
			,vol_15						as vol_15
			,0							as payout_1_cap
			,0							as payout_1
			,0							as payout_2
			,0							as payout_3
			,0							as payout_4
			,0							as payout_5
			,0							as payout_6
			,0							as payout_7
			,0							as payout_8
			,0							as payout_9
			,0							as payout_10
			,0							as payout_11
			,0							as payout_12
			,0							as payout_13
		from customer_history
		where period_id = :pn_Period_id
		and batch_id = 0;
	end if;
	  
	commit;
	
	
end;