<?php
	interface router_interface {
		const ROUTER_GET=1;
		const ROUTER_POST=2;

		public static function decodeLink($link,$mode);

		public static function checkParams($rules,$mode);

		public static function page($name,$mode);
	} # router_interface
