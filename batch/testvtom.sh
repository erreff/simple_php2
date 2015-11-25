#!/bin/ksh

CR=0
if [ ${#} -ne 0 ];then	#{
	CR=$1
fi	#}

WAIT_LOOP=0
if [ ${#} -eq 2 ];then	#{
	WAIT_LOOP=$2
fi	#}


echo "testvtom : exit $CR"

for (( i=1; i <= $WAIT_LOOP; i++ ))
do
 echo "Attente totale $i secondes"
 # Attente 1 seconde
 sleep 1 
done



exit $CR
