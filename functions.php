<?php
	function optimizeRegexArray($matches){
		$array=array();
		$a=0;
		foreach($matches as $key=>$value){
			if(($key>0)&&($value[1]!=-1)){
				$array[$a]=$value[0];
				$a++;
			} # if()
		} # foreach()
		return $array;
	} # optimalizeRegexArray()

	function optimizeRegexArrayAll($matches,$pos=FALSE){
		$array=array();
		$a=0;
		foreach($matches as $key=>$value){
			$b=0;
			$c=0;
			foreach($value as $key2=>$value2){
				if(($key2>0)&&($value2[1]!=-1)){
					$array[$a][$c]=$value2;
					if(!$pos){
						unset($array[$a][$c][1]);
					} # if()
					$c++;
				} # if()
				$b++;
			} # foreach()
			$a++;
		} # foreach()

		return $array;
	} # optimizeRegexArrayAll()

	define('REG_MATCH_NEW',1);
	define('REG_MATCH_OLD',2);
	function reg_match($pattern,$subject,&$matches=NULL,$mode=REG_MATCH_NEW){
		switch($mode){
			case REG_MATCH_NEW:
				$a=preg_match($pattern,$subject,$matches,PREG_OFFSET_CAPTURE);
				$matches=optimizeRegexArray($matches);
				return $a;
			case REG_MATCH_OLD:
				return preg_match($pattern,$subject,$matches);
			default:
				throw new Exception('Unknown mode');
		} # switch()
	} # reg_match()

	function reg_match_all($pattern,$subject,&$matches=NULL,$flags=PREG_PATTERN_ORDER,$mode=REG_MATCH_NEW){
		switch($mode){
			case REG_MATCH_NEW:
				$a=preg_match_all($pattern,$subject,$matches,$flags|PREG_OFFSET_CAPTURE);
				$matches=optimizeRegexArrayAll($matches,$flags&PREG_OFFSET_CAPTURE);
				return $a;
			case REG_MATCH_OLD:
				return preg_match_all($pattern,$subject,$matches,$flags);
			default:
				throw new Exception('Unknown mode');
		} # switch()
	} # reg_match_all()
