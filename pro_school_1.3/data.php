<?php
require_once 'table.class.php';
global $table_socket;
$table=new table();
$table_socket[26]=array();
function get_instruct($read_data,$read_socket,&$table_socket,$table,$sockets_id){
	echo "recive $read_data\r\n";
	$z='/\[[^\]]*\]/';
	$i=preg_match_all($z,$read_data,$instruct);
	//$instruct�Ǹ���ά���飬�ð�����һά����
	$instruct=$instruct[0];
	//var_dump($instruct);
	foreach ($instruct as $value){
		$value=trim($value,'[]');
		//��ȡָ���һ�ֶ�
		$ins1=strtok($value,',');
				if(strlen($ins1)>3)
					{//更新服务器socket
					$table_socket[0]=$read_socket;
					//服务器消息处理
					fdata_pro($value,$table_socket,$table);
					}
					else {
					$id=intval($ins1);
					//更新硬件socket
					$table_socket[$id]=$read_socket;
					//更新心跳
					$table->table_time[$id][0]=time();
					//硬件消息处理
					hdata_pro($value,$table_socket,$table);
				}				
	}
	fdata_send($table,$table_socket);
	//$table->compare_table($table_socket);
	//$table->check_heart($table_socket,$sockets_id);
	}
/* 硬件消息处理 */
function fdata_pro($value,&$table_socket,$table){
	$ins_flag=strtok($value,',');
	$type=strtok(',');
	$num=strtok(',');
	$box='';
	$ibox='';
	$table->check_table($num,$box,$ibox);
	$intbox=intval($box);
	$intibox=intval($ibox);
	$table->table_ins_flag[$intbox][$intibox]=$ins_flag;
	switch($type){
		case 'OPEN':
			$response="[0$box,2,00".$ibox.'O]';
			$ret=$table->set_table_new($intbox,$intibox,1,$ins_flag,$table_socket);
			//该指令已存在任务表中
			if($ret=='ret'){
				echo "111111111111\r\n";
				break;
			}
			//硬件套接字不存在，上报服务器
			if(!isset($table_socket[$intbox])||$table_socket[$intbox]<=0) {
				$response="[$ins_flag,NO]";
				echo "send: $response \r\n";
				socket_write($table_socket[0],$response,strlen($response));
				break;
			}
			//硬件套接字存在
			//$response="[0$box,2,00".$ibox.'O]';
			//把单挑指令收集起来
			$table->ins_cache[$intbox][]="$intibox,1";
			echo "------mix---1---\r\n";
			/*echo "send:$response--------------\r\n";
			if(socket_write($table_socket[$intbox],$response,strlen($response))==false){
				socket_close($table_socket[$intbox]);
			}*/
			break;
		case 'CLOSE':
			$response="[0$box,2,00".$ibox.'C]';
			$ret=$table->set_table_new($intbox,$intibox,0,$ins_flag,$table_socket);
			if($ret=='ret'){
				break;
			}
			//如果发往的硬件socket无效
			if($table_socket[$intbox]<=0){
				$response="[$ins_flag,NO]";
				socket_write($table_socket[0],$response,strlen($response));
				break;
			}
			//记录下单条指令
			$table->ins_cache[$intbox][]="$intibox,0";
			echo "--------mix---2---------";
			//
			/*if(socket_write($table_socket[$intbox],$response,strlen($response))==false){
				$response="[$ins_flag,NO]";
				socket_write($table_socket[0],$response,strlen($response));
				echo "send:$response\r\n";
				socket_close($table_socket[$intbox]);
			}
			else{
				echo "send:$response----\r\n";
			}*/
			break;
		case 'READ':
			$stat=$table->get_table_old($intbox,$intibox);
			$response="[$ins_flag,$stat]";
			echo $response."\r\n";
			socket_write($table_socket[0],$response,strlen($response));
			$table->table_ins_flag[$intibox][$intibox]="";
			break;
		default:
			break;
	}
}
/*硬件消息处理*/
function hdata_pro($value,&$table_socket,$table){
	$id=strtok($value,',');
	$box=intval($id);
	$type=strtok(',');
	$num=strtok(',');
	switch ($type){
	case '0':
	$num_stat=$table->get_boxj_stat($box);
	//$num=
	$response="[$id,1,$num]";
	echo "send:$response\r\n";
	socket_write($table_socket[$box],$response,strlen($response));
	break;
	case '3':
		$stat=substr($num,-1,1);
		//跟新箱子状态
		$table->table_box[$box]=$stat;
		//echo '$num'.$num;
		$ibox=(int)substr($num,0,4);
		$ibox16=str_pad(base_convert($ibox,16,2),16,'0',STR_PAD_LEFT);
		//根据更新表
		for($i=1;$i<=16;$i++){
			$temp=substr($ibox16,-$i,1);
			//判断表中对应继电器位是否发生变化，改变才更新
			if($table->table_old[$box][$i]!=$temp){
				$table->set_table_old($box,$i,$temp);
				@$ins_flag=$table->table_ins_flag[$box][$i];
				if($ins_flag!=""){
					$table->table_ins_flag[$box][$i]='';
					$response="[$ins_flag,OK]";
					socket_write($table_socket[0],$response,strlen($response));
				}
				//可能有些已进入重发，将重发计时清零
				$table->table_time[$box][$i]=0;
			}
		}
		break;
	case '4':
		$time=time();
		$floor=$table->check_table($box);
		$response="[$time,OPEN,$box,$floor]";
		socket_write($table_socket[0],$response,strlen($response));
		$response="[$id,5,0000O]";
		socket_write($table_socket[$box],$response,strlen($response));
		echo "send:$response\r\n";
		break;
	case '6':
		//更新到旧表
		$jd=substr($num,0,4);//获取继电器状态
		$box_stat=substr($num,4,1);
		$table->table_box[$box]=$box_stat;
		$a=base_convert($jd,16,2);
		$prefix='';
		if(($len=strlen($a))<16){
			$len=16-$len;
			while ( $len--) {
				$prefix.='0';
			}		
		}
		$val_num=$prefix.$a;
		//遍历数组，更新箱子
		for($i=1;$i<=16;$i++){
			$temp=substr($val_num,-$i,1);
			$table->set_table_old($box,$i,$temp);
		}
		$stat_box=substr($num,-1,1);
		$table->table_box[$box]=$stat_box;
		break;
	default:
		break;
	}
}
function fdata_send($table,$table_socket){
	foreach ($table->ins_cache as $intbox => $value) {
		$num=$table->get_boxj_stat($intbox);
		$stat=substr($num,0,4);
		$b=substr($num,-1,1);
		echo "----16--$stat---\r\n";
		$stat=str_pad(base_convert($stat,16,2),16,'0',STR_PAD_LEFT);
		echo "--------2--$stat\r\n";
		foreach ($value as $key2 => $value2) {
			$pos=strtok($value2,',');
			$key3=strtok(',');
			$str=substr_replace($stat,$key3,-($pos),1);
		}
		echo "---------str:$str\r\n";
		$stat=str_pad(base_convert($str,2,16),4,'0',STR_PAD_LEFT).$b;
		//箱子号转换为两位的箱子号
		$response="[0$intbox,2,$stat]";
		$response=strtoupper($response);
		echo "send mix ins $response \r\n";
		socket_write($table_socket[$intbox],$response,strlen($response));
	}
	$table->ins_cache=array();
	//var_dump($table->ins_cache);
}
?>