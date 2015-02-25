<?php
	/*
	 * Exceptions:
	 *  1) Incorrect link format
	 *  2) Unknown mode
	 *  3) Bad characteristics modification
	 *  4) Unknown characteristics
	 */

	abstract class Router {
		const ROUTER_GET=1;
		const ROUTER_POST=2;

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
			);

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
	} # Router

	class RouterException extends Exception {};
