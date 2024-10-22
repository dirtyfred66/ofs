<?php

	set_time_limit( 0 );

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'cj.php';
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'of.php';

	class OFS extends CJ {

		public $OF;

		function __construct( $test = false ) {

			parent:: __construct();
			$this-> init( $test );

		}

		function init( $test ) {

			$this-> OF = new OF( array(

				'cookie_data'=> $this-> option[ 'cookie_data' ],
				'user_agent'=> $this-> option[ 'user_agent' ],
				'user_id'=> $this-> option[ 'user_id' ],
				'x_bc'=> $this-> option[ 'x_bc' ]

			) );

			if( $test ) {

				$this-> test();

			} else {

				$result = $this-> mysqli-> query( "SELECT * FROM `" . $this-> prefix . "profiles` ORDER BY `profile_id` DESC" );
				$row_cnt = $result-> num_rows;
				$row = $result-> fetch_assoc();

				if( $row_cnt == 0 || $row[ 'done' ] ) {

					$profiles = $this-> OF-> subscriptions_all();

					$done = count( $profiles ) > 0 ? 0 : 1;

					$json = array();

					foreach( $profiles as $profile ) {

						$json[] = array(
							'id'=> $profile[ 'id' ],
							'name'=> $profile[ 'name' ],
							'uri'=> $profile[ 'username' ],
							'done'=> 0
						);

					}

					$json = json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
					$json = $this-> mysqli-> real_escape_string( $json );

					$sql = "INSERT INTO `" . $this-> prefix . "profiles` ( `json`, `done` ) VALUES ( '" . $json . "', " . $done . " )";

					if( $this-> mysqli-> query( $sql ) === TRUE ) {
						echo "New record created successfully";
					} else {
						echo "Error: " . $sql . "<br>" . $this-> mysqli-> error;
					}

				} else {

					$profiles = json_decode( $row[ 'json' ], true );
					$undone_profiles = array();

					foreach( $profiles as $profile ) {

						if( ! $profile[ 'done' ] ) {
							$undone_profiles[] = array(
								'id'=> $profile[ 'id' ],
								'name'=> $profile [ 'name' ],
								'uri'=> $profile[ 'uri' ]
							);
						}

					}

					if( count( $undone_profiles ) > 0 ) {

						$shifted_profile = array_shift( $undone_profiles );
						$profile_id = $shifted_profile[ 'id' ];
						$profile_name = $shifted_profile[ 'name' ];
						$profile_uri = $shifted_profile[ 'uri' ];

						$new_profiles = array();

						foreach( $profiles as $profile ) {

							$profile_done = $profile[ 'id' ] == $profile_id ? 1 : $profile[ 'done' ];

							$new_profiles[] = array(
								'id'=> $profile[ 'id' ],
								'name'=> $profile[ 'name' ],
								'uri'=> $profile[ 'uri' ],
								'done'=> $profile_done
							);

						}

						$new_json = json_encode( $new_profiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
						$new_json = $this-> mysqli-> real_escape_string( $new_json );

						$sql = "UPDATE `" . $this-> prefix . "profiles` SET `json`='" . $new_json . "' WHERE `profile_id`=" . $row[ 'profile_id' ];

						if( $this-> mysqli-> query( $sql ) === TRUE ) {
							echo "Updated successfully";
						} else {
							echo "Error: " . $sql . "<br>" . $this-> mysqli-> error;
						}

						$posts = $this-> OF-> posts_all( $profile_id, 1 );

						if( count( $posts ) > 0 ) {

							// <old>

							$query = "";

							foreach( $posts as $id=> $post ) {

								// preg_match_all( "/@(.*?) /", $post[ 'rawText' ], $usernames );
								preg_match_all( "/@([0-9a-z-_]+)/", strip_tags($post[ 'text' ]), $usernames );
								$usernames = json_encode( $usernames[ 1 ] );

								if( array_key_exists( 'media', $post ) && count( $post[ 'media' ] ) > 0 ) {

									$photos = 0;
									$videos = array();

									foreach( $post[ 'media' ] as $media ) {

										if( $media[ 'type' ] == 'photo' ) {

											$photos++; // type gif

										} elseif( $media[ 'type' ] == 'video' ) {

											// $videos[ $media[ 'id' ] ] = array( 'duration'=> $media[ 'source' ][ 'duration' ], 'created'=> $media[ 'createdAt' ] );
											$videos[ $media[ 'id' ] ] = array( 'duration'=> $media[ 'duration' ], 'created'=> $post[ 'postedAt' ] );

										} else {}

									}

									$price = $post[ 'price' ];

									if( count( $videos ) > 0 && $price > 0 ) {

										// id
										$posted = date( 'Y-m-d H:i:s', $post[ 'postedAtPrecise' ] );
										$post_id = $post[ 'id' ];
										$title = $this-> mysqli-> real_escape_string( strip_tags($post[ 'text' ]) ); // $post[ 'text' ];
										// duration
										// separated
										// $profile_id
										// $profile_name;
										$price = $post[ 'price' ];
										$link = 'https://onlyfans.com/' . $post[ 'id' ] . '/' . $profile_uri;
										// thumb
										// $photos
										$videos = json_encode( $videos ); // , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ?

										$query.= "INSERT INTO `" . $this-> prefix . "posts` ( `posted`, `post_id`, `title`, `profile_id`, `profile_name`, `profile_username`, `usernames`, `price`, `link`, `photos`, `videos` ) VALUES ( '" . $posted . "', " . $post_id . ", '" . $title . "', " . $profile_id . ", '" . $profile_name . "', '" . $profile_uri . "', '" . $usernames . "', " . $price . ", '" . $link . "', " . $photos . ", '" . $videos . "' ); ";

									} else {}

								} else {}

							}

							if( strlen( $query ) > 0 ) {

								$this-> mysqli-> multi_query( substr( $query, 0, -2 ) ) or die( $this-> mysqli-> error );
								while( $this-> mysqli-> next_result() ) { ; } // flush multi_queries

							} else {}

							// </old>

						}

					} else {

						$sql = "UPDATE `" . $this-> prefix . "profiles` SET `done`=1 WHERE `profile_id`=" . $row[ 'profile_id' ];

						if( $this-> mysqli-> query( $sql ) === TRUE ) {
							echo "Updated successfully";
						} else {
							echo "Error: " . $sql . "<br>" . $this-> mysqli-> error;
						}

						$this-> mysqli-> query( "DELETE t1 FROM " . $this-> prefix . "posts t1 INNER JOIN " . $this-> prefix . "posts t2 WHERE t1.id < t2.id AND t1.post_id = t2.post_id" );
						// $this-> mysqli-> query( "TRUNCATE TABLE `profiles`" );

					}

				}

			}

		}

		function test() {

			print '<pre>'; print_r( $this-> OF-> subscriptions() );
			// print '<pre>'; print_r( $this-> OF-> posts( 278587525 ) );

		}

	}

	$test = isset( $_GET[ 'test' ] ) ? true : false;

	$OFS = new OFS( $test );

?>
