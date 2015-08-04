<?php
		
	date_default_timezone_set( 'Asia/Chongqing' );
	
	pcntl_signal( SIGCHLD, SIG_IGN );
	
	$l_ip = '192.168.31.37';
	$l_port = 2015;
	
	while(TRUE) {
		
		$sock = socket_create( AF_INET, SOCK_STREAM, 0 );
		socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>10, "usec"=>0 ) );
		socket_set_option( $sock, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>3, "usec"=>0 ) );
		socket_set_option( $sock, SOL_SOCKET, SO_REUSEADDR, 1 );
		
		if( socket_bind($sock, 0, 0)===FALSE ) {       		// 绑定 ip、port
			//error_log( "water-M socket_bind failed!\r\n", 3, '/tmp/water-M.log' );
			exit;
		}
	
		$res = socket_connect ( $sock , $l_ip, $l_port );
		if($res===FALSE) {
			sleep( 3 );
			continue;
		}
		
		echo "hard_ware is running!\r\n";
		
		$buff = "[001,0,0000c]";
		socket_write( $sock, $buff );
		
		$conns = array( $sock );
		
		$jump_heart_t = 0;
					
		while(TRUE) {
			$read = $conns;
			$sele_res = socket_select( $read, $write=NULL, $except=NULL, 10 );
			if( FALSE===$sele_res )	{	
				socket_close( $conns[0] );
				break;
			}
			elseif( $sele_res>0 ) {
				$data = @socket_read( $read[0], 1300, PHP_BINARY_READ );
				if( $data===false ) {				// 出错，包括服务器断开连接
					socket_close( $read[0] );
					break;
				}
				else {
					if( !empty($data) )
						echo "recv:  ".time()."    $data\r\n";
					else {
						echo "server connection_aborted\r\n";
						socket_close( $read[0] );
						break;
					}
						
				}
			}
			
			// 超时
			// 发送心跳
			if ( (time()-$jump_heart_t)>=10 ) {
				$buff = "[001,6,0000c]";
				$mid_res = socket_write( $sock, $buff );
				$jump_heart_t = time();
				if( $mid_res===FALSE ) {
					socket_close( $sock );
					break;
				}
			}

		}
		
	}
	
?>