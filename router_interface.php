<?php
	namespace ventaquil;

	interface router_interface {
		const ROUTER_GET=1;
		const ROUTER_POST=2;
		const ROUTER_CUSTOM=3;

		public static function decodeLink($link,$mode);

		public static function checkParams($rules,$mode);

		public static function page($name,$mode);

		public static function pageonly($name,$mode);

		public static function link($string,$base);

		public static function htmllink($content,$string,$attributes,$base);

		public static function getCustom();
	} # router_interface
