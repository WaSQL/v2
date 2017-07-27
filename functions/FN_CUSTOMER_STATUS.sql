drop function commissions.fn_customer_status;
create function commissions.fn_customer_status(
	ps_locale varchar(50) default 'US-en')
	returns table (
		STATUS_ID 		integer
		, DESCRIPTION 	nvarchar(50)
		, HAS_EARNINGS 	integer)
	LANGUAGE SQLSCRIPT
	sql security invoker
	DEFAULT SCHEMA commissions
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			5/23/2017
*
* @describe     returns customer status id's and descriptions
*
* @param		varchar [ps_locale]
*
* @returns 		table
*				integer status_id
*				nvarchar description
*				integer has_earnings
* @example      select * from commissions.fn_customer_status()
-------------------------------------------------------*/
AS
BEGIN
	return 
	select status_id
		, description
		, has_earnings
	from customer_status
	order by status_id;
END;