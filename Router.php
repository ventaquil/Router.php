<?php
	namespace ventaquil;

	abstract class Router implements router_interface {
		private static $as_object=FALSE; # Return data as objects
		private static $custom_array=array(); # Array to CUSTOM mode
		private static $decoded=[ # For every mode start value is false
			self::ROUTER_GET=>FALSE,
			self::ROUTER_POST=>FALSE,
			self::ROUTER_CUSTOM=>FALSE,
		]; # $decoded
		private static $exception_mode=FALSE; # When is true Router execute exceptions
		private static $mode=self::ROUTER_GET; # Current Router mode

		/*
		 * @arg: (bool) new mode
		 * @ret: (bool) true or false
		 * @desc: Method returns true when mode changed successful, false otherwise.
		 */
		public static function setExceptionMode($mode){
			if(($mode==TRUE)||($mode==FALSE)){
				self::$exception_mode=$mode;
				return TRUE;
			} # if()
			else{
				return FALSE;
			} # else
		} # setExceptionMode()

		/*
		 * @arg: (bool) new mode
		 * @desc: Method set new mode when true sent.
		 */
		public static function setObjectMode($mode){
			self::$as_object=($mode==TRUE) ? TRUE : FALSE;
		} # setObjectMode()

		/*
		 * @arg: (int) new Router mode
		 * @ret: (bool) true or false
		 * @desc: Method returns true if mode change correctly or false otherwise.
		 */
		public static function setMode($mode){
			if(self::checkMode($mode)){
				self::$mode=$mode;
				return TRUE;
			} # if()
			else{
				return FALSE;
			} # else
		} # setMode()

		/*
		 * @arg: (int) mode to check
		 * @ret: (bool) true or false
		 * @desc: Method checks sent mode and if it's correct return true, false otherwise.
		 */
		private static function checkMode($mode){
			if(($mode==self::ROUTER_GET)||($mode==self::ROUTER_POST)||($mode==self::ROUTER_CUSTOM)){
				return TRUE;
			} # if()
			else{
				return FALSE;
			} # else
		} # checkMode()

		/*
		 * @arg: (string) link to decode
		 * @arg^: (int) mode which link will be decoded, default NULL
		 * @arg&^: (array) return array to CUSTOM mode
		 * @desc: Method decode sent link.
		 */
		public static function decodeLink($link,$mode=NULL,&$custom_array=array()){
			if($mode===NULL){ # Check mode, if null read mode from $mode private variable
				$mode=self::$mode;
			} # if()
			elseif(!self::checkMode($mode)){ # If mode not correctly throw exception
				self::runException('Unknown mode');
			} # elseif()

			$array=array();

			$link=preg_replace( # Prepare link to decode, remove unnecessary chars
				array(
					'/[\/]+/',
					'/^[\/]/',
					'/[\/]$/',
					'/[\=]+/',
					'/[\;]+/',
					'/[\,]+/'
				), # array()
				array(
					'/',
					NULL,
					NULL,
					'=',
					';',
					','
				), # array()
				$link
			); # $link

			if(!empty($link)){ # If link is not empty start decoding
				if(preg_match('/^([a-zA-Z0-9\-\_\.]+([\=]([a-zA-Z0-9\-\_\.]+([\,][a-zA-Z0-9\-\_\.]+)?[\;]?)+)?[\/]?)+$/',$link)){ # Check link syntax
					$link=explode('/',$link);
					$subview_path='';
					foreach($link as $key=>$subview){
						if(strpos($subview,'=')){
							list($subview,$params)=explode('=',$subview);
							$params=explode(';',$params);

							$subview_path.=(empty($subview_path))?$subview:'/'.$subview;

							$array[$subview_path]=array();
							foreach($params as $content){
								if(strpos($content,',')){
									list($name,$value)=explode(',',$content);
									$array[$subview_path][$name]=$value;
								} # if()
								else{
									$array[$subview_path][$content]=NULL;
								} # else
							} # foreach()
						} # if()
						else{
							$subview_path.=(empty($subview_path))?$subview:'/'.$subview;

							$array[$subview_path]=NULL;
						} # else
					} # foreach()
				} # if()
				else{
					self::runException('Incorrect link format'); # Throw exception if link syntax is wrong
				} # else

				if(self::$as_object){
					$array=new RouterObject($array);
				} # if()

				switch($mode){ # Set global variables $_GET and $_POST after decoding
					case self::ROUTER_GET:
						$_GET=$array;
						break;
					case self::ROUTER_POST:
						$_POST=$array;
						break;
					case self::ROUTER_CUSTOM:
						self::$custom_array=$custom_array=$array;
						break;
				} # switch()
			} # if()
			else{
				if(self::$as_object){
					$array=new RouterObject($array);
				} # if()

				switch($mode){ # Set global variables $_GET and $_POST after decoding
					case self::ROUTER_GET:
						$_GET=$array;
						break;
					case self::ROUTER_POST:
						$_POST=$array;
						break;
					case self::ROUTER_CUSTOM:
						self::$custom_array=$custom_array=$array;
						break;
				} # switch()
			} # else

			self::$decoded[$mode]=TRUE;
		} # decodeLink()

		/*
		 * @ret: (array) custom array from $custom_array private variable
		 * @desc: Method returns custom array.
		 */
		public static function getCustom(){
			return self::$custom_array;
		} # getCustom()

		/*
		 * @arg: (array) rules to check params
		 * @arg^: (int) optional mode
		 * @ret: (bool) true or false
		 * @desc: Method returs true when params meet rules or false otherwise.
		 */
		public static function checkParams($rules,$mode=NULL){
			if($mode===NULL){ # Check mode, if null read mode from $mode private variable
				$mode=self::$mode;
			} # if()
			elseif(!self::checkMode($mode)){
				self::runException('Unknown mode');
			} # elseif()

			if(self::$decoded[$mode]){
				switch($mode){
					case self::ROUTER_GET:
						$work_array=$_GET;
						break;
					case self::ROUTER_POST:
						$work_array=$_POST;
						break;
					case self::ROUTER_CUSTOM:
						$work_array=self::getCustom();
						break;
				} # switch()

				if(empty($work_array)){
					return TRUE;
				} # if()
				else{
					if(is_object($work_array)){
						$work_array=$work_array->params();
					} # if()
					echo '<pre>'.print_r($work_array,true).'</pre>';

					$return=TRUE;

					foreach($work_array as $path=>$params_array){ # Check each data in analyzes array
						if(isset($rules[$path])&&!empty($params_array)){
							$return&=self::validateArguments($rules[$path],$params_array);
						} # if()

						if($return==FALSE){ # Break if return value is false
							break;
						} # if()
					} # foreach()
					return $return;
				} # else
			} # if()
			else{
				self::runException('Execute decodeLink() before run this method');
			} # else
		} # checkParams()

		/*
		 * @arg: (int) value to check
		 * @ret: (bool) true or false
		 * @desc: Method checks \p characteristic.
		 */
		private static function checkEmpty($value){
			return empty($value);
		} # checkEmpty()

		/*
		 * @arg: (int) value to check
		 * @arg: (int) condition in characteristic
		 * @ret: (bool) true or false
		 * @desc: Method checks \b characteristic.
		 */
		private static function checkBoole($value,$data){
			$value=intval($value);
			if(empty($data)){
				return ($value===0)||($value===1);
			} # if()
			else{
				return $value===intval($data[1]);
			} #else
		} # checkBoole()

		/*
		 * @arg: (int) value to check
		 * @arg: (int) conditions in characteristic
		 * @ret: (bool) true or false
		 * @desc: Method checks \n characteristic.
		 */
		private static function checkNatural($value,$data){
			if(!empty($data[0])){
				$value=self::convert($value,$data[0]);
			} # if()

			if(isset($data[1])){
				switch($data[1]){
					case '+':
						return $value>0;
						break;
					case '<'.$data[3]:
						return $value<$data[3];
						break;
					case '<='.$data[3]:
						return $value<=$data[3];
						break;
					case '>'.$data[3]:
						return $value>$data[3];
						break;
					case '>='.$data[3]:
						return $value>=$data[3];
						break;
					case '='.$data[3]:
						return $value==$data[3];
						break;
					default:
						if(($data[3]!='n')&&($data[4]!='n'||$data[4]!='i'||$data[4]!='i+')){
							switch($data[1]){
								case '<'.$data[2].';'.$data[3].')':
								case '('.$data[2].';'.$data[3].'>':
								case '('.$data[2].';'.$data[3].')':
									$condition=$data[2]>=$data[3];
									break;
								default:
									$condition=$data[2]>$data[3];
							} # switch()
						} # if()
						else{
							$condition=TRUE;
						} # else

						if($condition){
							switch($data[2]){
								case '(':
									return (($data[3]!='n'&&$data[3]<$value)||($data[3]=='n'));
									break;
								case '<':
									return (($data[3]!='n'&&$data[3]<=$value)||($data[3]=='n'));
									break;
							} # switch()

							switch($matches[5]){
								case ')':
									return (($matches[4]!='n'&&$matches[4]>$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
									break;
								case '>':
									return (($matches[4]!='n'&&$matches[4]!='i'&&$matches[4]!='i+'&&$matches[4]>=$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
									break;
								} # switch()
							} # if()
							else{
								self::runException('Bad characteristics modification');
							} # else
				} # switch()
			} # if()

			return TRUE;
		} # checkNatural()

		/*
		 * @arg: (int) value to check
		 * @arg: (int) conditions in characteristic
		 * @ret: (bool) true or false
		 * @desc: Method checks \i characteristic.
		 */
		private static function checkInteger($value,$data){
			if(!empty($data[0])){
				$value=self::convert($value,$data[0]);
			} # if()

			if(isset($data[1])){
				switch($data[1]){
					case '+':
						return $value>0;
						break;
					case '-':
						return $value<0;
						break;
					case '<'.$data[3]:
						return $value<$data[3];
						break;
					case '<='.$data[3]:
						return $value<=$data[3];
						break;
					case '>'.$data[3]:
						return $value>$data[3];
						break;
					case '>='.$data[3]:
						return $value>=$data[3];
						break;
					case '='.$data[3]:
						return $value==$data[3];
						break;
					default:
						if(($data[3]!='n'||$data[3]!='i-')&&($data[4]!='n'||$data[4]!='i'||$data[4]!='i+')){
							switch($data[1]){
								case '<'.$data[2].';'.$data[3].')':
								case '('.$data[2].';'.$data[3].'>':
								case '('.$data[2].';'.$data[3].')':
									$condition=$data[2]>=$data[3];
									break;
								default:
									$condition=$data[2]>$data[3];
							} # switch()
						} # if()
						else{
							$condition=TRUE;
						} # else

						if($condition){
							switch($data[2]){
								case '(':
									return (($data[3]!='n'&&$data[3]!='i-'&&$data[3]<$value)||($data[3]=='n'||$data[3]=='i-'));
									break;
								case '<':
									return (($data[3]!='n'&&$data[3]!='i-'&&$data[3]<=$value)||($data[3]=='n'||$data[3]=='i-'));
									break;
							} # switch()

							switch($data[5]){
								case ')':
									return (($data[4]!='n'&&$data[4]>$value)||($data[4]=='n'||$data[4]=='i'||$data[4]=='i+'));
									break;
								case '>':
									return (($data[4]!='n'&&$data[4]!='i'&&$data[4]!='i+'&&$data[4]>=$value)||($data[4]=='n'||$data[4]=='i'||$data[4]=='i+'));
									break;
							} # switch()
						} # if()
						else{
							self::runException('Bad characteristics modification');
						} # else
				} # switch()
			} # if()

			return TRUE;
		} # checkInteger()

		/*
		 * @arg: (int) value to check
		 * @arg: (int) conditions in characteristic
		 * @ret: (bool) true or false
		 * @desc: Method checks \f characteristic.
		 */
		private static function checkFloat($value,$data){
			if(!empty($data[0])){
				$value=self::convert($value,$data[0]);
			} # if()

			if(isset($data[1])){
				switch($data[1]){
					case '+':
						$return&=$value>0;
						break;
					case '-':
						$return&=$value<0;
						break;
					case '<'.$data[3]:
						$return&=$value<$data[3];
						break;
					case '<='.$data[3]:
						$return&=$value<=$data[3];
						break;
					case '>'.$data[3]:
						$return&=$value>$data[3];
						break;
					case '>='.$data[3]:
						$return&=$value>=$data[3];
						break;
					case '='.$data[3]:
						$return&=$value==$data[3];
						break;
					default:
						if(($data[3]!='n'||$data[3]!='i-')&&($data[4]!='n'||$data[4]!='i'||$data[4]!='i+')){
							switch($data[1]){
								case '<'.$data[2].';'.$data[3].')':
								case '('.$data[2].';'.$data[3].'>':
								case '('.$data[2].';'.$data[3].')':
									$condition=$data[2]>=$data[3];
									break;
								default:
									$condition=$data[2]>$data[3];
							} # switch()
						} # if()
						else{
							$condition=TRUE;
						} # else

						if($condition){
							switch($data[2]){
								case '(':
									$return&=(($data[3]!='n'&&$data[3]!='i-'&&$data[3]<$value)||($data[3]=='n'||$data[3]=='i-'));
									break;
								case '<':
									$return&=(($data[3]!='n'&&$data[3]!='i-'&&$data[3]<=$value)||($data[3]=='n'||$data[3]=='i-'));
									break;
							} # switch()

							switch($data[5]){
								case ')':
									$return&=(($data[4]!='n'&&$data[4]>$value)||($data[4]=='n'||$data[4]=='i'||$data[4]=='i+'));
									break;
								case '>':
									$return&=(($data[4]!='n'&&$data[4]!='i'&&$data[4]!='i+'&&$data[4]>=$value)||($data[4]=='n'||$data[4]=='i'||$data[4]=='i+'));
									break;
							} # switch()
						} # if()
						else{
							self::runException('Bad characteristics modification');
						} # else
				} # switch()
			} # if()

			return TRUE;
		} # checkFloat()

		/*
		 * @arg: (int) value to check
		 * @arg: (int) condition in characteristic
		 * @ret: (bool) true or false
		 * @desc: Method checks \c characteristic.
		 */
		private static function checkChar($value,$data){
			if(empty($data)){
				return strlen($value)==1;
			} # if()
			else{
				return $data[1]==$value;
			} # else
		} # checkChar()

		/*
		 * @arg: (int) value to check
		 * @arg: (int) conditions in characteristic
		 * @ret: (bool) true or false
		 * @desc: Method checks \s characteristic.
		 */
		private static function checkString($value,$data){
			if(isset($data[1])){
				switch($data[0]){
					case ':'.$data[1]:
						if(($data[1][0]=='/')&&($data[1][count($data[1])-1]=='/')){
							return preg_match($data[1],$value);
						} # if()
						else{
							return $value==$data[1];
						} # else
						break;
					case '+':
						return strlen($value)>0;
						break;
					case '<'.$data[2]:
						return strlen($value)<$data[2];
						break;
					case '<='.$data[2]:
						return strlen($value)<=$data[2];
						break;
					case '>'.$data[2]:
						return strlen($value)>$data[2];
						break;
					case '>='.$data[2]:
						return strlen($value)>=$data[2];
						break;
					case '='.$data[2]:
						return strlen($value)==$data[2];
						break;
					default:
						if(($data[3]!='n')&&($data[4]!='n'||$data[4]!='i'||$data[4]!='i+')){
							switch($data[1]){
								case '<'.$data[2].';'.$data[3].')':
								case '('.$data[2].';'.$data[3].'>':
								case '('.$data[2].';'.$data[3].')':
									$condition=$data[2]>=$data[3];
									break;
								default:
									$condition=$data[2]>$data[3];
							} # switch()
						} # if()
						else{
							$condition=TRUE;
						} # else

						if($condition){
							$strlen=strlen($value);
							switch($data[1]){
								case '(':
									return (($data[2]!='n'&&$data[2]<$strlen)||($data[2]=='n'));
									break;
								case '<':
									return (($data[2]!='n'&&$data[2]<=$strlen)||($data[2]=='n'));
									break;
							} # switch()

							switch($data[4]){
								case ')':
									return (($data[3]!='n'&&$data[3]>$strlen)||($data[3]=='n'||$data[3]=='i'||$data[3]=='i+'));
									break;
								case '>':
									return (($data[3]!='n'&&$data[3]!='i'&&$data[3]!='i+'&&$data[3]>=$strlen)||($data[3]=='n'||$data[3]=='i'||$data[3]=='i+'));
									break;
							} # switch()
						} # if()
						else{
							self::runException('Bad characteristics modification');
						} # else
				} # switch()
			} # if()

			return TRUE;
		} # checkString()

		private static function validateArguments($rules,$params){
			$return=TRUE;

			foreach($params as $name=>$value){
				if(isset($rules[$name])){
					$characteristic=substr($rules[$name],1,1);
					switch($characteristic){
						case 'p':
							if($rules[$name]==='\p'){
								$return&=self::checkEmpty($value);
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						case 'b':
							if(reg_match('/^\\\\b(\=([01]))?$/',$rules[$name],$matches)){
								$return&=self::checkBoole($value,$matches);
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						case 'n':
							if(reg_match('/^\\\\n([oh]?)([+]|(\()([0-9]+|n)\;([0-9]+|i[+]?|n)(\))|(\<)([0-9]+)\;([0-9]+|i[+]?|n)(\))|(\()([0-9]+|n)\;([0-9]+)(\>)|(\<)([0-9]+)\;([0-9]+)(\>)|([\>\<][\=]?|[\=])([0-9]+))?$/',substr($rules[$name],0),$matches)){
								if($value>=0){
									$return&=self::checkNatural($value,$matches);
								} # if()
								else{
									$return&=FALSE;
								} # else
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						case 'i':
							if(reg_match('/^\\\\i([oh]?)([+-]|(\()([-]?[0-9]+|n|i-)\;([-]?[0-9]+|i[+]?|n)(\))|(\<)([-]?[0-9]+)\;([-]?[0-9]+|i[+]?|n)(\))|(\()([-]?[0-9]+|n|i-)\;([-]?[0-9]+)(\>)|(\<)([-]?[0-9]+)\;([-]?[0-9]+)(\>)|([\>\<][\=]?|[\=])([-]?[0-9]+))?$/',substr($rules[$name],0),$matches)){
								$return&=self::checkInteger($value,$matches);
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						case 'f':
							if(reg_match('/^\\\\f([oh]?)([+-]|(\()([-]?[0-9]+(\.[0-9]+)?|n|i-)\;([-]?[0-9]+(\.[0-9]+)?|i[+]?|n)(\))|(\<)([-]?[0-9]+(\.[0-9]+)?)\;([-]?[0-9]+(\.[0-9]+)?|i[+]?|n)(\))|(\()([-]?[0-9]+(\.[0-9]+)?|n|i-)\;([-]?[0-9]+(\.[0-9]+)?)(\>)|(\<)([-]?[0-9]+(\.[0-9]+)?)\;([-]?[0-9]+(\.[0-9]+)?)(\>)|([\>\<][\=]?|[\=])([-]?[0-9]+(\.[0-9]+)?))?$/',substr($rules[$name],0),$matches)){
								$return&=self::checkFloat($value,$matches);
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						case 'c':
							if(reg_match('/^\\\\c([:](.))?$/',$rules[$name],$matches)){
								$return&=self::checkChar($value,$matches);
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						case 's':
							if(reg_match('/^\\\\s([:](.*)|(\()([0-9]+|n)\;([0-9]+|i[+]?|n)(\))|(\<)([0-9]+)\;([0-9]+|i[+]?|n)(\))|(\()([0-9]+|n)\;([0-9]+)(\>)|(\<)([0-9]+)\;([0-9]+)(\>)|([\>\<][\=]?|[\=])([0-9]+))?$/',$rules[$name],$matches)){
								$return&=self::checkString($value,$matches);
							} # if()
							else{
								$return&=FALSE;
								self::runException('Bad characteristics modification');
							} # else
							break;
						default:
							self::runException('Unknown characteristics');
					} # switch()
				} # if()

				if($return==FALSE){ # Break loop if return value is false
					break;
				} # if()
			} # foreach()

			return $return;
		} # validateArguments()

		/*
		 * @arg: (mixed) number to convert
		 * @arg: (char) convert mode
		 * @ret: (int) converted value
		 * @desc: Method converts sent data to decimal number from octal or hexadecimal.
		 */
		private static function convert($number,$mode){
			switch($mode){
				case 'o':
					return octdec($number);
				case 'h':
					return hexdec($number);
				default:
					self::runException('Unknown mode');
			} # switch()
		} # convert()

		/*
		 * @arg: (string) page  name
		 * @ret: (bool) true or false
		 * @desc: Method checks sent page - if is available now then return true, false otherwise.
		 */
		public static function page($name){
			$callback=$mode=NULL;

			$args=func_get_args();
			if(isset($args[1])){
				if(is_callable($args[1])){
					$callback=$args[1];
					if(isset($args[2])){
						$mode=$args[2];
					} # if()
				} # if()
				else{
					if(isset($args[2])){
						$callback=$args[1];
						$mode=$args[2];
					} # if()
					else{
						$mode=$args[1];
					} # else
				} # else
			} # if()

			if($mode===NULL){ # Check mode, if null read mode from $mode private variable
				$mode=self::$mode;
			} # if()
			elseif(!self::checkMode($mode)){
				self::runException('Unknown mode');
			} # elseif()

			if(self::$decoded[$mode]){
				$name=preg_replace(
					array(
						'/[\/]+/',
						'/^[\/]/',
						'/[\/]$/'
					), # array()
					array(
						'/',
						NULL,
						NULL
					), # array()
					$name
				); # $name

				if(self::$as_object){
					switch($mode){
						case self::ROUTER_GET:
							$return=in_array($name,$_GET->routes());
							break;
						case self::ROUTER_POST:
							$return=in_array($name,$_POST->routes());
							break;
						case self::ROUTER_CUSTOM:
							$return=in_array($name,self::getCustom()->routes());
							break;
					} # switch()
				} # if()
				else{
					switch($mode){
						case self::ROUTER_GET:
							$return=array_key_exists($name,$_GET);
							break;
						case self::ROUTER_POST:
							$return=array_key_exists($name,$_POST);
							break;
						case self::ROUTER_CUSTOM:
							$return=array_key_exists($name,self::getCustom());
							break;
					} # switch()
				} # else

				if($callback===NULL){
					return $return;
				} # if()
				else{
					if(is_callable($callback)){
						if($return){
							$callback();
							return TRUE;
						} # if()
						else{
							return FALSE;
						} # else
					} # if()
					else{
						self::runException('Send argument is not callable');
					} # else
				} # else
			} # if()
			else{
				self::runException('Execute decodeLink() before run this method');
			} # else
		} # page()

		/*
		 * @arg: (string) page  name
		 * @ret: (bool) true or false
		 * @desc: Method checks sent page - if is available now then return true, false otherwise. Level must be the same!
		 */
		public static function pageonly($name){
			$callback=$mode=NULL;

			$args=func_get_args();
			if(isset($args[1])){
				if(is_callable($args[1])){
					$callback=$args[1];
					if(isset($args[2])){
						$mode=$args[2];
					} # if()
				} # if()
				else{
					if(isset($args[2])){
						$callback=$args[1];
						$mode=$args[2];
					} # if()
					else{
						$mode=$args[1];
					} # else
				} # else
			} # if()

			if($mode===NULL){ # Check mode, if null read mode from $mode private variable
				$mode=self::$mode;
			} # if()
			elseif(!self::checkMode($mode)){
				self::runException('Unknown mode');
			} # elseif()

			if(self::$decoded[$mode]){
				$name=preg_replace(
					array(
						'/[\/]+/',
						'/^[\/]/',
						'/[\/]$/'
					), # array()
					array(
						'/',
						NULL,
						NULL
					), # array()
					$name
				); # $name

				if(self::$as_object){
					switch($mode){
						case self::ROUTER_GET:
							$keys=$_GET->routes();
							break;
						case self::ROUTER_POST:
							$keys=$_POST->routes();
							break;
						case self::ROUTER_CUSTOM:
							$keys=self::getCustom()->routes();
							break;
					} # switch()
				} # if()
				else{
					switch($mode){
						case self::ROUTER_GET:
							$keys=array_keys($_GET);
							break;
						case self::ROUTER_POST:
							$keys=array_keys($_POST);
							break;
						case self::ROUTER_CUSTOM:
							$keys=array_keys(self::getCustom());
							break;
					} # switch()
				} # else

				if(!empty($keys)){
					$return=$keys[count($keys)-1]==$name;
				} # if()
				else{
					$return=$name==NULL;
				} # else

				if($return){
					if(is_callable($callback)&&$return){
						$callback();
						return TRUE;
					} # if()
					else{
						return $return;
					} # else
				} # if()
				else{
					return FALSE;
				} # else
			} # if()
			else{
				self::runException('Execute decodeLink() before run this method');
				return FALSE;
			} # else
		} # pageonly()

		/*
		 * @arg&: (string) base to edit
		 * @desc: Method edits sent base by deleting multiple slashes.
		 */
		private static function editbase(&$base){
			if(empty($base)){
				$base=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			} # if()
			else{
				$base=preg_replace(
					array(
						'/[\/]+/',
						'/^[\/]/',
						'/[\/]$/'
					), # array()
					array(
						'/',
						NULL,
						NULL
					), # array()
					$base
				); # $base
			} # else
		} # editbase()

		/*
		 * @arg^: (string) base
		 * @ret: (int) base number
		 * @desc: Method count base level.
		 */
		public static function countbase($base=NULL){
			self::editbase($base);	
			$base=explode('/',$base);
			return count($base)-1;
		} # countbase()

		/*
		 * @arg: (string) characteristics to create link
		 * @arg^: (string) base
		 * @ret: (string) generated link
		 * @desc: Method generates link.
		 */
		public static function link($string,$base=NULL){
			if(reg_match('/^(\&|(\%)([!]{0,2}))?([0-9]*)?(.*?)(\*)?(?:\[(.*)\])?$/',$string,$matches)){
				self::editbase($base);

				switch($matches[0]){
					case NULL:
						return 'http://'.$matches[1];
					case '&':
						if(is_numeric($matches[1])){
							$matches[2]=explode('=',$matches[2]);

							$base=explode('/',$base);

							if(empty($matches[3])){
								if(isset($matches[4])){
									self::runException('Bad characteristics modification');
								} # if()

								for($a=0,$b=count($base);$a<$b;$a++){
									if($a==$matches[1]){
										$base[$a+1]=implode('=',$matches[2]);
									} # if()
									elseif(($a>$matches[1])){
										unset($base[$a+1]);
									} # else
								} # for()
							} # if()
							else{
								$view=$base[$matches[1]+1];
								$view_strpos=strpos($view,'=');
								$view=(($view_strpos>0)?substr($view,0,$view_strpos):$view);

								if(isset($matches[4])){
									$matches[4]=explode(',',$matches[4]);
									foreach($matches[4] as $key=>$arg){
										$matches[4][$key]='/'.$matches[2][0].'\=(.*?[\;])?'.$arg.'(?:[\,](?:.*?[\;]|.*)|[\;])?/';
									} # foreach()
									$base[$matches[1]+1]=preg_replace($matches[4],$matches[2][0].'=$1',$base[$matches[1]+1]);
								} # if()

								if(isset($matches[2][1])){
									if($view==$matches[2][0]){
										$explode=explode(';',$matches[2][1]);
										foreach($explode as $key=>$value){
											$strpos=strpos($value,',');
											if($strpos>0){
												$explode[$key]=substr($value,0,$strpos);
											} # if()
										} # foreach()
										$intopregmatch=implode('|',$explode);

										if(preg_match('/'.$view.'=(?:.*?)?(?:'.$intopregmatch.')[\;]?/',$base[$matches[1]+1])){
											$replace=explode(';',$matches[2][1]);
											$to=$from=array();
											foreach($replace as $key=>$value){
												$strpos=strpos($value,',');
												if($strpos>0){
													$from[$key]='/'.substr($value,0,$strpos).'([\,].*?[\;]|[\,].*)?/';
												} # if()
												else{
													$from[$key]='/'.$value.'([\,].*?[\;])?/';
												} # else
												$to[$key]=$value.';';
											} # foreach()

											$base[$matches[1]+1]=preg_replace($from,$to,$base[$matches[1]+1]);
										} # if()
										else{
											if($view_strpos>0){
												$base[$matches[1]+1].=';'.$matches[2][1];
											} # if()
											else{
												$base[$matches[1]+1].='='.$matches[2][1];
											} # else
										} # else
									} # if()
									else{
										$base[$matches[1]+1]=implode('=',$matches[2]);
									} # else
								} # if()
								else{
									if($view!=$matches[2][0]){
										$base[$matches[1]+1]=implode('=',$matches[2]);
									} # if()
								} # else
							} # else

							$last_char=substr($base[$matches[1]+1],-1);
							if($last_char==';'||$last_char=='='){
								$base[$matches[1]+1]=substr($base[$matches[1]+1],0,strlen($base[$matches[1]+1])-1);
							} # if()

							return 'http://'.implode('/',$base);
						} # if()
						else{
							return 'http://'.$base.((substr($base,-1)!='/')?'/':NULL).$matches[2];
						} # else
					case '%':
					case '%!':
					case '%!!':
						if(empty($matches[4])){
							$base=explode('/',$base);

							if(is_numeric($matches[3])){
								$base_size=count($base)-2;
								if($base_size>=$matches[3]){
									switch($matches[2]){
										case '!':
											$index=$matches[3]+1;
											$strpos=strpos($base[$index],'=');
											if($strpos){
												$base[$index]=substr($base[$index],0,$strpos);
											} # if()
											break;
										case '!!':
											for($a=1,$b=$matches[3]+1;$a<=$b;$a++){
												$c=strpos($base[$a],'=');
												if($c){
													$base[$a]=substr($base[$a],0,$c);
												} # if()
											} # for()
											break;
									} # switch()

									for($a=$matches[3];$a<$base_size;$a++){
										unset($base[$a+2]);
									} # for()

									return 'http://'.implode('/',$base);
								} # if()
								else{
									return 'http://'.implode('/',$base);
								} # else
							} # if()
							else{
								unset($base[count($base)-1]); # delete last element

								switch($matches[2]){
									case '!':
										$index=count($base)-1;
										$base[$index]=substr($base[$index],0,strpos($base[$index],'='));
										break;
									case '!!':
										for($a=0,$b=count($base);$a<$b;$a++){
											if(is_numeric(strpos($base[$a],'='))){
												$base[$a]=substr($base[$a],0,strpos($base[$a],'='));
											} # if()
										} # for()
										break;
								} # switch()

								return 'http://'.implode('/',$base);
							} # else
						} # if()
						else{
							self::runException('Incorrect link format');
						} # else
				} # switch()
			} # if()
		} # link()

		/*
		 * @arg: (string) content to 'a' HTML element
		 * @arg: (string) characteristics to create link
		 * @arg: (array) attributes to 'a' HTML element
		 * @arg^: (string) base
		 * @ret: (string) generated link
		 * @desc: Method generates HTML link.
		 */
		public static function htmllink($content,$string,$attributes=array(),$base=NULL){
			if(!empty($attributes)){
				$attr='';
				foreach($attributes as $name=>$value){
					if(empty($value)){
						$attr.=' '.$name;
					}
					else{
						$attr.=' '.$name.'="'.$value.'"';
					}
				} # foreach()
			} # if()

			return '<a href="'.self::link($string,$base).'"'.((isset($attr))?$attr:NULL).'>'.$content.'</a>';
		} # htmllink()

		/*
		 * @arg: (string) message text
		 * @desc: Method runs exception when $exception_mode is true.
		 */
		private static function runException($message){
			if(self::$exception_mode){
				throw new RouterException($message);
			} # if()
		} # runException()
	} # Router

	class RouterException extends \Exception {}

	class RouterObject {
		protected $path;
		protected $params;

		/*
		 * @arg^: (array) router params
		 * @ret: (object) RouterObject
		 * @desc: When new object are created methods put params to $params protected variable.
		 */
		public function __construct($params=array()){
			if(is_array($params)){
				$this->params=$params;
				$this->path=NULL;
				return $this;
			} # if()
			else{
				throw new RouterObjectException('No array sent');
			} # else
		} # __construct()

		/*
		 * @arg: (string) route path
		 * @ret: (object) RouteObject
		 * @desc: Method set $path protected variable.
		 */
		protected function withPath($path){
			$this->path=$path;
			return $this;
		} # withPath()

		/*
		 * @arg^: (string) route path
		 * @ret: (object) RouterObject with new path
		 * @desc: Method creates new RouterObject, set them sent path and return it.
		 */
		public function path($path=NULL){
			$obj=new RouterObject($this->params);
			return $obj->withPath($path);
		} # path()

		/*
		 * @ret: (mixed) array or null
		 * @desc: Method returns array from current path or null if element is empty.
		 */
		public function all(){
			if(empty($this->params[$this->path])){
				return NULL;
			} # if()
			else{
				return $this->params[$this->path];
			} # else
		} # all()

		/*
		 * @arg^: (string) parameter to check
		 * @ret: (mixed) array or null
		 * @desc: Method returns value of sent parameter from current path or null if parameter is empty.
		 */
		public function param($param=NULL){
			if(empty($this->params[$this->path])){
				return NULL;
			} # if()
			else{
				if(isset($this->params[$this->path][$param])){
					return $this->params[$this->path][$param];
				} # if()
				else{
					return NULL;
				} # else
			} # else
		} # param()

		/*
		 * @ret: (array) all routes in current url
		 * @desc: Method returns array with all stored routes.
		 */
		public function routes(){
			return array_keys($this->params);
		} # routes()

		/*
		 * @ret: (array) all params with routes
		 * @desc: Method returns array with all routes and all params.
		 */
		public function params(){
			return $this->params;
		} # params()
	} # RouterObject

	class RouterObjectExceptions extends \Exception {}
