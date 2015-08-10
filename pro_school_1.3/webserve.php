<?php
	$socket=socket_create( AF_INET, SOCK_STREAM, SOL_TCP);
	$i=socket_connect(socket, '192.168.31.5',2023)
		while(TURE){
		if(socket_select($socket, write, except, tv_sec)){
			$time=time();
			$buff="[$time,OPEN,201]"
			socket_write($socket,$buff,strlen($buff));
			echo "已发送：$buff \r\n";
			$buff=socket_read($socket,1024);
			echo "已接受：$buff\r\n";
			$time=$time-time();
			echo "时间差：$time\r\n";

		}
	}
?>