drop function commissions.FN_CUSTOMER_HISTORY_RANK;
CREATE function commissions.FN_CUSTOMER_HISTORY_RANK 
/*--------------------------------------------------
* @author       Del Stirling
* @category     function
* @date			6/15/2017
*
* @describe     returns rank history
*
* @param		integer pn_customer_id
*
* @returns 		table
*				varchar entry_date
*				varchar rank
*				nvarchar rank_type
* @example      select * from commissions.customer_history_rank(1001)
-------------------------------------------------------*/
(pn_customer_id integer)
	returns table(
		  ENTRY_DATE 		varchar(20)
		, RANK 				varchar(39)
		, RANK_TYPE 		nvarchar(34)
		)
	LANGUAGE SQLSCRIPT
	SQL SECURITY INVOKER
   	DEFAULT SCHEMA Commissions
AS

BEGIN
	return
	SELECT 
		  to_char(min(ifnull(rnk.effective_date,rnk.entry_date)),'dd-Mon-yyyy')				as ENTRY_DATE
		, rnk.rank_id || ' - ' || rtyp.description 					as RANK
		, rnk.customer_rank_type_id || ' - ' || rti.description 	as RANK_TYPE
	FROM customer_rank_history rnk
		, rank rtyp
		, rank_type rti
	WHERE rtyp.rank_id = rnk.rank_id
		and rti.rank_type_id = rnk.customer_rank_type_id
		and rnk.customer_id = :pn_customer_id
	GROUP BY rnk.rank_id
		, rtyp.description
		, rnk.customer_rank_type_id
		, rti.description
	ORDER BY rnk.rank_id desc;
END;