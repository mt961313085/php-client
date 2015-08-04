<?php
	
	pcntl_signal( SIGCHLD, SIG_IGN );
	
	$l_ip = '192.168.31.37';
	$l_port = 2015;
	
	$sock = socket_create( AF_INET, SOCK_STREAM, 0 );
	socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>10, "usec"=>0 ) );
	socket_set_option( $sock, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>3, "usec"=>0 ) );
	socket_set_option( $sock, SOL_SOCKET, SO_REUSEADDR, 1 );
	
	if( socket_bind($sock, $l_ip, $l_port)===FALSE ) {       		// 绑定 ip、port
		error_log( "water-M socket_bind failed!\r\n", 3, '/tmp/water-M.log' );
		exit;
	}
	
	socket_listen( $sock );      						 // 监听端口
	$clients = array( $sock );
	echo "hare_ware server is running!\r\n";
	
	$sock_ids = array();	// 对应每个连接进来的 sock
	$ds = array();			// 保持每台设备的全部数据

    while(TRUE) {
		$read = $clients;
        if( socket_select($read, $write=NULL, $except=NULL, NULL) < 1 )
            continue;
		
        if( in_array($sock, $read) ) {
            $sock_ids[] = $clients[] = $newsock = socket_accept( $sock );	
            $key = array_search( $sock, $read );
            unset( $read[$key] );
        }

		// loop through all the clients that have data to read from
        foreach( $read as $read_sock ) {
            // read until newline or 1024 bytes
            // socket_read while show errors when the client is disconnected, so silence the error messages
            $data = @socket_read( $read_sock, 1300, PHP_BINARY_READ );
            // check if the client is disconnected
            if( $data===false ) {
                $key = array_search( $read_sock, $clients );
				$k2 = array_search( $read_sock, $sock_ids );
				socket_close( $read_sock );
                unset( $clients[$key] );				
	
				echo 'client disconnected  data: '.$ds[$k2]."\t\t".date("Y-m-d H:i:s")."\r\n";	
				unset( $sock_ids[$k2] );
				unset( $ds[$k2] );	
				echo "client disconnected!\t\t".date("Y-m-d H:i:s")."\r\n";	
                continue;
            }
			else {
				$k2 = array_search( $read_sock, $sock_ids );
				if( !empty( $data ) ) {
					if( empty($ds[$k2]) )
						$ds[$k2] = $data;
					else
						$ds[$k2] = $ds[$k2].$data;	
					
					// 处理数据
					echo "e1-\tclient send: ".$ds[$k2]."\t".date("Y-m-d H:i:s")."\r\n";
					if( $ds[$k2]=="[001,0,0000c]" ) {
						$buff = "[001,1,0101c]";
						socket_write( $read_sock, $buff );
					}
					
					if( $ds[$k2]=="[001,6,0000c]" ) {
						$buff = "[001,3,000c]";
						socket_write( $read_sock, $buff );
					}
					
					$ds[$k2] = '';
					
				}
				else {
					echo "e2-\tclient has send all data: ".$ds[$k2]."\t".date("Y-m-d H:i:s")."\r\n";
							
					// 处理数据
					if( substr($data,-2)==="\r\n" ) {
						error_log( "e2-\t\t$ds[$k2]\t\t".date("Y-m-d H:i:s")."\r\n", 3, '/tmp/water-M.log' );
					}

					$key = array_search( $read_sock, $clients );
					socket_close( $read_sock );
					unset( $clients[$key] );
					unset( $sock_ids[$k2] );
					unset( $ds[$k2] );
				}
					
			}
			
		}			
		
	}
	
	socket_close( $sock );
?>