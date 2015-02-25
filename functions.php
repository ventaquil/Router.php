<?php
	function optimalizeRegexArray($matches){
		$array=array();
		$a=0;
		foreach($matches as $key=>$value){
			if(($key>0)&&($value[1]!=-1)){
				$array[$a]=$value[0];
				$a++;
			} # if()
		} # foreach()
		return $array;
	}

	define('REG_MATCH_NEW',1);
	define('REG_MATCH_OLD',2);
	function reg_match($pattern,$subject,&$matches,$mode=REG_MATCH_NEW){
		switch($mode){
			case REG_MATCH_NEW:
				$a=preg_match($pattern,$subject,$matches,PREG_OFFSET_CAPTURE);
				$matches=optimalizeRegexArray($matches);
				return $a;
				break;
			case REG_MATCH_OLD:
				return preg_match($pattern,$subject,$matches);
				break;
			default:
				throw new Exception('Unknown mode');
		} # switch()
	} # reg_match()
