<?php

	class CJ {

		public $mysqli;
		public $prefix;
		public $option = array();

		function __construct() {

			require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

			$this-> mysqli = new mysqli( $hostname, $username, $password, $database );

			$this-> mysqli-> set_charset( $charset );

			$this-> prefix = $prefix;

			$result = $this-> mysqli-> query( "SELECT * FROM `" . $this-> prefix . "options`" );

			while( $row = $result-> fetch_assoc() ) {

				$this-> option[ $row[ 'option_name' ] ] = $row[ 'option_value' ];

			}

		}

	}

?>
