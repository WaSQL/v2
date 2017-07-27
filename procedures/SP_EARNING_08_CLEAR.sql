drop procedure Commissions.sp_Earning_08_Clear;
create procedure Commissions.sp_Earning_08_Clear(
					 pn_Period_id		int
					,pn_Period_Batch_id	int)
   LANGUAGE SQLSCRIPT
   DEFAULT SCHEMA Commissions
AS

begin
	
	update pool_head
	set  count = 0
		,shares = 0
		,shares_extra = 0
		,volume = 0
		,percent = 0
		,fund = 0
		,share_value = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id
	and pool_id = 4;
	
	commit;
	
	update customer_history
	set Earning_8 = 0
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	
	commit;
	
	delete
	from Earning_08
	where period_id = :pn_Period_id
	and batch_id = :pn_Period_Batch_id;
	  
	commit;
	
end;