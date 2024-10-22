<?php

	class OF {

		public $cookie_data = NULL; // string
		public $user_agent = NULL; // string
		public $user_id = NULL; // string
		public $x_bc = NULL; // string

		public $cookie_path = NULL; // string
		public $cookie_file = NULL; // string
		public $data = NULL; // string

		public $app_token = '33d57ade8c02dbc5a333db99ff9ae26a';
		public $accept = 'application/json, text/plain, */*';
		public $url = 'https://onlyfans.com';

		function __construct( $data = NULL ) {

			if( ! is_null( $data ) && is_array( $data ) ) {

				foreach( array( 'app_token', 'cookie_data', 'user_agent', 'user_id', 'x_bc' ) as $var ) {

					if( array_key_exists( $var, $data ) ) {

						$this-> $var = $data [ $var ];

					} else {}

				}

			} else {}

			$this-> data(); // $this-> data

		}

		function subscriptions( $page = 1 ) {

			//     user_id =                           $OF-> subscriptions() [ $i ] [ 'id' ]
			// profile_url = 'https://onlyfans.com/' . $OF-> subscriptions() [ $i ] [ 'username' ]
			//        name =                           $OF-> subscriptions() [ $i ] [ 'name' ]

			$page = $page - 1;
			
			$offset = $page * 10;

			return $this-> browser( '/api2/v2/subscriptions/subscribes?offset=' . $offset . '&type=active&sort=desc&field=expire_date&limit=10' );

		}

		function subscriptions_all( $sleep = 2 ) {

			$subscriptions_all = array();

			$page = 1;

			$num_subscriptions = 1;

			while( $num_subscriptions > 0 ) {

				$subscriptions = $this-> subscriptions( $page );

				if( ! array_key_exists( 'error', $subscriptions ) ) {

					foreach( $subscriptions as $subscription ) {

						if( ! array_key_exists( $subscription [ 'id' ], $subscriptions_all ) ) {

							$subscriptions_all [ $subscription [ 'id' ] ] = $subscription;

						} else {}

					}

					$num_subscriptions = count( $subscriptions );

					$page++;

					sleep( $sleep );

				} else {

					$num_subscriptions = 0;

				}

			}

			return $subscriptions_all;

		}

		function posts( $profile_id = null, $post_type = 'videos', $beforePublishTime = '' ) { // post_type = pinned_posts | posts | videos | archived_posts

			// https://github.com/Amenly/onlyfans-scraper/blob/main/onlyfans_scraper/constants.py

			//              PINNED: /api2/v2/users/112404466/posts         ?                                 skip_users=all&skip_users_dups=1&                                    pinned=1&counters=0&format=infinite
			// NOT PINNED, 1. PAGE: /api2/v2/users/112404466/posts         ?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1&                                    pinned=0&           format=infinite
			//             2. PAGE: /api2/v2/users/112404466/posts         ?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1&beforePublishTime=1637591191.000000&pinned=0&counters=0&format=infinite
			//     VIDEOS, 1. PAGE: /api2/v2/users/79470046 /posts/  videos?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1&                                             counters=0&format=infinite
			//             2. PAGE: /api2/v2/users/79470046 /posts/  videos?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1&beforePublishTime=1633111876.000000&         counters=0&format=infinite
			//   ARCHIVED, 1. PAGE: /api2/v2/users/57488749 /posts/archived?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1&                                                        format=infinite
			//             2. PAGE: /api2/v2/users/57488749 /posts/archived?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1&beforePublishTime=1626210201.000000&         counters=0&format=infinite

			$posts_counter = '';

			if( strlen( $beforePublishTime ) > 0 ) {

				$beforePublishTime = '&beforePublishTime=' . $beforePublishTime;
				$posts_counter = '&counters=0';

			} else {}

			switch( $post_type ) {

				case 'pinned_posts':

					$uri = '/api2/v2/users/' . $profile_id . '/posts?skip_users=all&skip_users_dups=1&pinned=1&counters=0&format=infinite';
					break;

				case 'posts':

					$uri = '/api2/v2/users/' . $profile_id . '/posts?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1' . $beforePublishTime . '&pinned=0' . $posts_counter . '&format=infinite';
					break;

				case 'videos':

					$uri = '/api2/v2/users/' . $profile_id . '/posts/videos?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1' . $beforePublishTime . '&counters=0&format=infinite';
					break;

				case 'archived_posts':

					$uri = '/api2/v2/users/' . $profile_id . '/posts/archived?limit=10&order=publish_date_desc&skip_users=all&skip_users_dups=1' . $beforePublishTime . $posts_counter . '&format=infinite';
					break;

			}

			return $this-> browser( $uri );

		}

		function posts_all( $profile_id = null, $sleep = 2 ) {

			$posts_all = array();

			// post_type = pinned_posts
			$posts = $this-> posts( $profile_id, 'pinned_posts' ) [ 'list' ];

			sleep( $sleep );

			foreach( $posts as $post ) {

				$posts_all [ $post [ 'id' ] ] = $post;

			}

			$beforePublishTime = '';
			$hasMore = true;

			while( $hasMore ) {

				// post_type = posts
				$posts = $this-> posts( $profile_id, 'posts', $beforePublishTime );

				foreach( $posts [ 'list' ] as $post ) {

					if( ! array_key_exists( $post [ 'id' ], $posts_all ) ) {

						$posts_all [ $post [ 'id' ] ] = $post;

					} else {}

				}

				$hasMore = $posts [ 'hasMore' ];

				if( $hasMore ) {

					$beforePublishTime = $posts [ 'list' ] [ max( array_keys( $posts [ 'list' ] ) ) ] [ 'postedAtPrecise' ];

				} else {}

				sleep( $sleep );

			}

			$beforePublishTime = '';
			$hasMore = true;

			while( $hasMore ) {

				// post_type = videos
				$posts = $this-> posts( $profile_id, 'videos', $beforePublishTime );

				foreach( $posts [ 'list' ] as $post ) {

					if( ! array_key_exists( $post [ 'id' ], $posts_all ) ) {

						$posts_all [ $post [ 'id' ] ] = $post;

					} else {}

				}

				$hasMore = $posts [ 'hasMore' ];

				if( $hasMore ) {

					$beforePublishTime = $posts [ 'list' ] [ max( array_keys( $posts [ 'list' ] ) ) ] [ 'postedAtPrecise' ];

				} else {}

				sleep( $sleep );

			}

			$beforePublishTime = '';
			$hasMore = true;

			while( $hasMore ) {

				// post_type = archived_posts
				$posts = $this-> posts( $profile_id, 'archived_posts', $beforePublishTime );

				foreach( $posts [ 'list' ] as $post ) {

					if( ! array_key_exists( $post [ 'id' ], $posts_all ) ) {

						$posts_all [ $post [ 'id' ] ] = $post;

					} else {}

				}

				$hasMore = $posts [ 'hasMore' ];

				if( $hasMore ) {

					$beforePublishTime = $posts [ 'list' ] [ max( array_keys( $posts [ 'list' ] ) ) ] [ 'postedAtPrecise' ];

				} else {}

				sleep( $sleep );

			}

			return $posts_all;

		}

		function chats( $page = 1 ) {

			// /api2/v2/chats?limit=10&offset= 0&filter=&order=recent&               skip_users_dups=1
			// /api2/v2/chats?limit=10&offset=10&filter=&order=recent&skip_users=all&skip_users_dups=1

			$page = $page - 1;
			
			$offset = $page * 10;

			return $this-> browser( '/api2/v2/chats?limit=10&offset=' . $offset . '&filter=&order=recent&skip_users_dups=1' );

		}

		function chats_all( $sleep = 2 ) {

			$chats_all = array();

			$page = 1;
			$hasMore = true;

			while( $hasMore ) {

				$chats = $this-> chats( $page );

				foreach( $chats [ 'list' ] as $chat ) {

					if( ! array_key_exists( $chat [ 'withUser' ] [ 'id' ], $chats_all ) ) {

						$chats_all [ $chat [ 'withUser' ] [ 'id' ] ] = $chat;

					} else {}

				}

				$hasMore = $chats [ 'hasMore' ];

				$page++;

				sleep( $sleep );

			}

			return $chats_all;

		}

		function messages( $profile_id = null, $from_message_id = 0 ) {

			// /api2/v2/chats/168827850/messages?limit=10&offset=0&                order=desc&skip_users=all&skip_users_dups=1
			// /api2/v2/chats/168827850/messages?limit=10&offset=0&id=312293889405&order=desc&skip_users=all&skip_users_dups=1

			if( $from_message_id > 0 ) {

				$from_message_id = '&id=' . $from_message_id;

			} else {

				$from_message_id = '';

			}

			return $this-> browser( '/api2/v2/chats/' . $profile_id . '/messages?limit=10&offset=0' . $from_message_id . '&order=desc&skip_users=all&skip_users_dups=1' );

		}

		function messages_all( $profile_id = null, $sleep = 2 ) {

			$messages_all = array();

			$from_message_id = 0;
			$hasMore = true;

			while( $hasMore ) {

				$messages = $this-> messages( $profile_id, $from_message_id );

				foreach( $messages [ 'list' ] as $message ) {

					if( ! array_key_exists( $message [ 'id' ], $messages_all ) ) {

						$messages_all [ $message [ 'id' ] ] = $message;

					} else {}

				}

				$hasMore = $messages [ 'hasMore' ];

				$from_message_id = $messages [ 'list' ] [ max( array_keys( $messages [ 'list' ] ) ) ] [ 'id' ];

				sleep( $sleep );

			}

			return $messages_all;

		}

		function browser( $uri = '' ) {

			$request_headers = array(

				'accept'=> $this-> accept,
				'app-token'=> $this-> app_token,
				'cookie'=> $this-> cookie_data,
				'user-agent'=> $this-> user_agent,
				'user-id'=> $this-> user_id,
				'x-bc'=> $this-> x_bc

			);

			$request_headers = array_merge( $request_headers, $this-> sign_calc( $this-> user_id, $uri ) );
			
			$ex = new Exception();
			$trace = $ex-> getTrace();

			$this-> cookie_path = __DIR__ . DIRECTORY_SEPARATOR . 'cookie' . DIRECTORY_SEPARATOR;

			if( file_exists( $this-> cookie_path ) && is_dir( $this-> cookie_path ) ) {} else {

				mkdir( $this-> cookie_path );

			}

			$this-> cookie_file = $this-> user_id . '_' . $trace [ 1 ] [ 'function' ];

			$httpheader = array();

			foreach( $request_headers as $key=> $value ) {

				$httpheader [] = $key . ': ' . $value;

			}

			$cu = curl_init();

			curl_setopt( $cu, CURLOPT_URL, $this-> url . $uri );
			curl_setopt( $cu, CURLOPT_HTTPHEADER, $httpheader );
			curl_setopt( $cu, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $cu, CURLOPT_COOKIEFILE, $this-> cookie_path . $this-> cookie_file );
			curl_setopt( $cu, CURLOPT_COOKIEJAR, $this-> cookie_path . $this-> cookie_file );

			$response = curl_exec( $cu );

			curl_close( $cu );

			return json_decode( $response, true );

		}

        // https://raw.githubusercontent.com/DATAHOARDERS/dynamic-rules/main/onlyfans.json
        function data( $url = 'https://raw.githubusercontent.com/deviint/onlyfans-dynamic-rules/b6b1c1ae3910ed6a8bb282197a1c7dfb732fb82f/dynamicRules.json' /* 'https://raw.githubusercontent.com/Growik/onlyfans-dynamic-rules/main/rules.json' */ /* 'https://raw.githubusercontent.com/DIGITALCRIMINALS/dynamic-rules/main/onlyfans.json' */ ) {
            // https://raw.githubusercontent.com/Growik/onlyfans-dynamic-rules/main/rules.json

			$this-> data = json_decode( file_get_contents( $url ), true );

		}

		function sign_calc( $user_id, $uri ) {

			$unixtime = floor( microtime( TRUE ) * 1000 );

			$message = implode( "\n", array( $this-> data [ 'static_param' ], $unixtime, $uri, $user_id ) );

			$sha_1_sign = sha1( $message );

			$checksum = 0;

			foreach( $this-> data [ 'checksum_indexes' ] as $i ) {

				$checksum+= ord( $sha_1_sign [ $i ] );

			}

			$checksum+= $this-> data [ 'checksum_constant' ];

			$checksum_hex = dechex( $checksum );

			// $f = $this-> data [ 'format' ]; // 18555:{}:{:x}:65bd1462
			$f = $this-> data [ 'prefix' ] . ':{}:{:x}:' . $this-> data [ 'suffix' ];
			$f = str_replace( '{}', $sha_1_sign, $f );
			$f = str_replace( "{:x}", $checksum_hex, $f );

			$r = array();

			$r [ 'time' ] = $unixtime;
			$r [ 'sign' ] = $f;

			return $r;

		}

	}

?>
