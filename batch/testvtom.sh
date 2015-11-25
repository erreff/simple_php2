#!/bin/ksh

CR=0
if [ ${#} -ne 0 ];then	#{
	CR=$1
fi	#}


echo "testvtom : exit $CR"
exit $CR
