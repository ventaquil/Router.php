<?php
	/*
	 * Exceptions:
	 *  1) Incorrect link format
	 *  2) Unknown mode
	 *  3) Bad characteristics modification
	 *  4) Unknown characteristics
	 */

	namespace ventaquil;

	abstract class Router implements router_interface {
		public static function decodeLink($link,$mode=self::ROUTER_GET){
			$array=array();

			$link=preg_replace(
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

			if(!empty($link)){
				if(preg_match('/^([a-zA-Z][a-zA-Z0-9]{0,}([\=]([a-zA-Z0-9]+([\,][a-zA-Z0-9]+)?[\;]?)+)?[\/]?)+$/',$link)){
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
					throw new RouterException(1);
				} # else

				switch($mode){
					case self::ROUTER_GET:
						$_GET=$array;
						break;
					case self::ROUTER_POST:
						$_POST=$array;
						break;
					default:
						throw new RouterException(2);
				} # switch()
			} # if()
		} # decodeLink()

		public static function checkParams($rules,$mode=self::ROUTER_GET){
			switch($mode){
				case self::ROUTER_GET:
					$work_array=$_GET;
					break;
				case self::ROUTER_POST:
					$work_array=$_POST;
					break;
				default:
					throw new RouterException(2);
			} # switch()

			if(isset($work_array)){
				$return=TRUE;

				foreach($work_array as $path=>$params_array){
					if(isset($rules[$path])&&!empty($params_array)){
						$return&=self::validateArguments($rules[$path],$params_array);
					} # if()
				} # foreach()
				return $return;
			} # if()
			else{
				return TRUE;
			} # else
		} # checkParams()

		private static function validateArguments($rules,$params){
			$return=TRUE;

			foreach($params as $name=>$value){
				if($return==FALSE){
					break;
				} # if()

				if(isset($rules[$name])){
					$characteristic=substr($rules[$name],1,1);
					switch($characteristic){
						case 'p':
							if($rules[$name]==='\p'){
								$return&=empty($value);
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						case 'b':
							if(reg_match('/^\\\\b(\=([01]))?$/',$rules[$name],$matches)){
								$value=intval($value);
								if(!empty($matches)){
									$return&=$value===intval($matches[1]);
								} # if()
								else{
									$return&=($value===0||$value===1);
								} #else
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						case 'n':
							if(reg_match('/^\\\\n([oh]?)([+]|(\()([0-9]+|n)\;([0-9]+|i[+]?|n)(\))|(\<)([0-9]+)\;([0-9]+|i[+]?|n)(\))|(\()([0-9]+|n)\;([0-9]+)(\>)|(\<)([0-9]+)\;([0-9]+)(\>)|([\>\<][\=]?|[\=])([0-9]+))?$/',substr($rules[$name],0),$matches)){
								if($value>=0){
									if(!empty($matches[0])){
										$value=self::convert($value,$matches[0]);
									} # if()

									if(isset($matches[1])){
										switch($matches[1]){
											case '+':
												$return&=$value>0;
												break;
											case '<'.$matches[3]:
												$return&=$value<$matches[3];
												break;
											case '<='.$matches[3]:
												$return&=$value<=$matches[3];
												break;
											case '>'.$matches[3]:
												$return&=$value>$matches[3];
												break;
											case '>='.$matches[3]:
												$return&=$value>=$matches[3];
												break;
											case '='.$matches[3]:
												$return&=$value==$matches[3];
												break;
											default:
												if(($matches[3]!='n')&&($matches[4]!='n'||$matches[4]!='i'||$matches[4]!='i+')){
													switch($matches[1]){
														case '<'.$matches[2].';'.$matches[3].')':
														case '('.$matches[2].';'.$matches[3].'>':
														case '('.$matches[2].';'.$matches[3].')':
															$condition=$matches[2]>=$matches[3];
															break;
														default:
															$condition=$matches[2]>$matches[3];
													} # switch()
												} # if()
												else{
													$condition=TRUE;
												} # else

												if($condition){
													switch($matches[2]){
														case '(':
															$return&=(($matches[3]!='n'&&$matches[3]<$value)||($matches[3]=='n'));
															break;
														case '<':
															$return&=(($matches[3]!='n'&&$matches[3]<=$value)||($matches[3]=='n'));
															break;
													} # switch()

													switch($matches[5]){
														case ')':
															$return&=(($matches[4]!='n'&&$matches[4]>$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
															break;
														case '>':
															$return&=(($matches[4]!='n'&&$matches[4]!='i'&&$matches[4]!='i+'&&$matches[4]>=$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
															break;
													} # switch()
												} # if()
												else{
													throw new RouterException(3);
												} # else
										} # switch()
									} # if()
								} # if()
								else{
									$return&=FALSE;
								} # else
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						case 'i':
							if(reg_match('/^\\\\i([oh]?)([+-]|(\()([-]?[0-9]+|n|i-)\;([-]?[0-9]+|i[+]?|n)(\))|(\<)([-]?[0-9]+)\;([-]?[0-9]+|i[+]?|n)(\))|(\()([-]?[0-9]+|n|i-)\;([-]?[0-9]+)(\>)|(\<)([-]?[0-9]+)\;([-]?[0-9]+)(\>)|([\>\<][\=]?|[\=])([-]?[0-9]+))?$/',substr($rules[$name],0),$matches)){
								if(!empty($matches[0])){
									$value=self::convert($value,$matches[0]);
								} # if()

								if(isset($matches[1])){
									switch($matches[1]){
										case '+':
											$return&=$value>0;
											break;
										case '-':
											$return&=$value<0;
											break;
										case '<'.$matches[3]:
											$return&=$value<$matches[3];
											break;
										case '<='.$matches[3]:
											$return&=$value<=$matches[3];
											break;
										case '>'.$matches[3]:
											$return&=$value>$matches[3];
											break;
										case '>='.$matches[3]:
											$return&=$value>=$matches[3];
											break;
										case '='.$matches[3]:
											$return&=$value==$matches[3];
											break;
										default:
											if(($matches[3]!='n'||$matches[3]!='i-')&&($matches[4]!='n'||$matches[4]!='i'||$matches[4]!='i+')){
												switch($matches[1]){
													case '<'.$matches[2].';'.$matches[3].')':
													case '('.$matches[2].';'.$matches[3].'>':
													case '('.$matches[2].';'.$matches[3].')':
														$condition=$matches[2]>=$matches[3];
														break;
													default:
														$condition=$matches[2]>$matches[3];
												} # switch()
											} # if()
											else{
												$condition=TRUE;
											} # else

											if($condition){
												switch($matches[2]){
													case '(':
														$return&=(($matches[3]!='n'&&$matches[3]!='i-'&&$matches[3]<$value)||($matches[3]=='n'||$matches[3]=='i-'));
														break;
													case '<':
														$return&=(($matches[3]!='n'&&$matches[3]!='i-'&&$matches[3]<=$value)||($matches[3]=='n'||$matches[3]=='i-'));
														break;
												} # switch()

												switch($matches[5]){
													case ')':
														$return&=(($matches[4]!='n'&&$matches[4]>$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
														break;
													case '>':
														$return&=(($matches[4]!='n'&&$matches[4]!='i'&&$matches[4]!='i+'&&$matches[4]>=$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
														break;
												} # switch()
											} # if()
											else{
												throw new RouterException(3);
											} # else
									} # switch()
								} # if()
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						case 'f':
							if(reg_match('/^\\\\f([oh]?)([+-]|(\()([-]?[0-9]+(\.[0-9]+)?|n|i-)\;([-]?[0-9]+(\.[0-9]+)?|i[+]?|n)(\))|(\<)([-]?[0-9]+(\.[0-9]+)?)\;([-]?[0-9]+(\.[0-9]+)?|i[+]?|n)(\))|(\()([-]?[0-9]+(\.[0-9]+)?|n|i-)\;([-]?[0-9]+(\.[0-9]+)?)(\>)|(\<)([-]?[0-9]+(\.[0-9]+)?)\;([-]?[0-9]+(\.[0-9]+)?)(\>)|([\>\<][\=]?|[\=])([-]?[0-9]+(\.[0-9]+)?))?$/',substr($rules[$name],0),$matches)){
								if(!empty($matches[0])){
									$value=self::convert($value,$matches[0]);
								} # if()

								if(isset($matches[1])){
									switch($matches[1]){
										case '+':
											$return&=$value>0;
											break;
										case '-':
											$return&=$value<0;
											break;
										case '<'.$matches[3]:
											$return&=$value<$matches[3];
											break;
										case '<='.$matches[3]:
											$return&=$value<=$matches[3];
											break;
										case '>'.$matches[3]:
											$return&=$value>$matches[3];
											break;
										case '>='.$matches[3]:
											$return&=$value>=$matches[3];
											break;
										case '='.$matches[3]:
											$return&=$value==$matches[3];
											break;
										default:
											if(($matches[3]!='n'||$matches[3]!='i-')&&($matches[4]!='n'||$matches[4]!='i'||$matches[4]!='i+')){
												switch($matches[1]){
													case '<'.$matches[2].';'.$matches[3].')':
													case '('.$matches[2].';'.$matches[3].'>':
													case '('.$matches[2].';'.$matches[3].')':
														$condition=$matches[2]>=$matches[3];
														break;
													default:
														$condition=$matches[2]>$matches[3];
												} # switch()
											} # if()
											else{
												$condition=TRUE;
											} # else

											if($condition){
												switch($matches[2]){
													case '(':
														$return&=(($matches[3]!='n'&&$matches[3]!='i-'&&$matches[3]<$value)||($matches[3]=='n'||$matches[3]=='i-'));
														break;
													case '<':
														$return&=(($matches[3]!='n'&&$matches[3]!='i-'&&$matches[3]<=$value)||($matches[3]=='n'||$matches[3]=='i-'));
														break;
												} # switch()

												switch($matches[5]){
													case ')':
														$return&=(($matches[4]!='n'&&$matches[4]>$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
														break;
													case '>':
														$return&=(($matches[4]!='n'&&$matches[4]!='i'&&$matches[4]!='i+'&&$matches[4]>=$value)||($matches[4]=='n'||$matches[4]=='i'||$matches[4]=='i+'));
														break;
												} # switch()
											} # if()
											else{
												throw new RouterException(3);
											} # else
									} # switch()
								} # if()
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						case 'c':
							if(reg_match('/^\\\\c([:](.))?$/',$rules[$name],$matches)){
								if(empty($matches)){
									$return&=strlen($value)==1;
								} # if()
								else{
									$return&=$matches[1]==$value;
								} # else
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						case 's':
							if(reg_match('/^\\\\s([:](.*)|(\()([0-9]+|n)\;([0-9]+|i[+]?|n)(\))|(\<)([0-9]+)\;([0-9]+|i[+]?|n)(\))|(\()([0-9]+|n)\;([0-9]+)(\>)|(\<)([0-9]+)\;([0-9]+)(\>)|([\>\<][\=]?|[\=])([0-9]+))?$/',$rules[$name],$matches)){
								if(isset($matches[1])){
									switch($matches[0]){
										case ':'.$matches[1]:
											$return&=$value==$matches[1];
											break;
										case '+':
											$return&=strlen($value)>0;
											break;
										case '<'.$matches[2]:
											$return&=strlen($value)<$matches[2];
											break;
										case '<='.$matches[2]:
											$return&=strlen($value)<=$matches[2];
											break;
										case '>'.$matches[2]:
											$return&=strlen($value)>$matches[2];
											break;
										case '>='.$matches[2]:
											$return&=strlen($value)>=$matches[2];
											break;
										case '='.$matches[2]:
											$return&=strlen($value)==$matches[2];
											break;
										default:
											if(($matches[3]!='n')&&($matches[4]!='n'||$matches[4]!='i'||$matches[4]!='i+')){
												switch($matches[1]){
													case '<'.$matches[2].';'.$matches[3].')':
													case '('.$matches[2].';'.$matches[3].'>':
													case '('.$matches[2].';'.$matches[3].')':
														$condition=$matches[2]>=$matches[3];
														break;
													default:
														$condition=$matches[2]>$matches[3];
												} # switch()
											} # if()
											else{
												$condition=TRUE;
											} # else

											if($condition){
												$strlen=strlen($value);
												switch($matches[1]){
													case '(':
														$return&=(($matches[2]!='n'&&$matches[2]<$strlen)||($matches[2]=='n'));
														break;
													case '<':
														$return&=(($matches[2]!='n'&&$matches[2]<=$strlen)||($matches[2]=='n'));
														break;
												} # switch()

												switch($matches[4]){
													case ')':
														$return&=(($matches[3]!='n'&&$matches[3]>$strlen)||($matches[3]=='n'||$matches[3]=='i'||$matches[3]=='i+'));
														break;
													case '>':
														$return&=(($matches[3]!='n'&&$matches[3]!='i'&&$matches[3]!='i+'&&$matches[3]>=$strlen)||($matches[3]=='n'||$matches[3]=='i'||$matches[3]=='i+'));
														break;
												} # switch()
											} # if()
											else{
												throw new RouterException(3);
											} # else
									} # switch()
								} # if()
							} # if()
							else{
								$return&=FALSE;
								throw new RouterException(3);
							} # else
							break;
						default:
							throw new RouterException(4);
					} # switch()
				} # if()
			} # foreach()

			return $return;
		} # validateArguments()

		private static function convert($number,$mode){
			switch($mode){
				case 'o':
					return octdec($number);
				case 'h':
					return hexdec($number);
				default:
					throw new RouterException(2);
			} # switch()
		} # convert()

		public static function page($name,$mode=self::ROUTER_GET){
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

			switch($mode){
				case self::ROUTER_GET:
					return array_key_exists($name,$_GET);
					break;
				case self::ROUTER_POST:
					return array_key_exists($name,$_POST);
					break;
				default:
					throw new RouterException(2);
			} # switch()
		} # page()

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

		public static function countbase($base=NULL){
			self::editbase($base);	
			$base=explode('/',$base);
			return count($base)-1;
		} # countbase()

		public static function link($string,$base=NULL){
			if(reg_match('/^(\&|(\%)([!]{0,2}))?([0-9]?)?(.*)$/',$string,$matches)){ 
				self::editbase($base);

				switch($matches[0]){
					case NULL:
						return 'http://'.$matches[1];
						break;
					case '&':
						if(is_numeric($matches[1])){
							$base=explode('/',$base);
							for($a=0,$b=count($base);$a<$b;$a++){
								if($a==$matches[1]){
									$base[$a+1]=$matches[2];
								} # if()
								elseif($a>$matches[1]){
									unset($base[$a+1]);
								} # else
							} # for()
							return 'http://'.implode('/',$base);
						} # if()
						else{
							return 'http://'.$base.'/'.$matches[2];
						} # else
						break;
					case $matches[1].$matches[2]:
						if(empty($matches[4])){
							$base=explode('/',$base);

							if(is_numeric($matches[3])){
								$base_size=count($base)-2;
								if($base_size>=$matches[3]){
									switch($matches[2]){
										case '!':
											$index=$matches[3]+1;
											$base[$index]=substr($base[$index],0,strpos($base[$index],'='));
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

									for($a=$matches[3]+2;$a<$base_size;$a++){
										unset($base[$a]);
									} # for()

									return 'http://'.implode('/',$base);
								} # if()
								else{
									return 'http://'.implode('/',$base);
								} # else
							} # if()
							else{
								unset(end($base));//$base[count($base)-1]);

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
							throw new RouterException(1);
						} # else
						break;
				} # switch()
			} # if()
		} # link()

		public static function htmllink($content,$string,$attributes=array(),$base=NULL){
			$link=self::link($string,$base);

			if(!empty($attributes)){
				$attr='';
				foreach($attributes as $name=>$value){
					$attr.=' '.$name.'="'.$value.'"';
				} # foreach()
			} # if()

			return '<a href="'.$link.'"'.((isset($attr))?$attr:NULL).'>'.$content.'</a>';
		} # htmllink()
	} # Router

	class RouterException extends \Exception {};
