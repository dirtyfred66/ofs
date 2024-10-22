<?php

	header( 'Content-Type: application/xml; charset=utf-8' );

	function duration( $secs, $delimiter = ':' ) {

		$seconds = $secs % 60;
		$minutes = floor( $secs / 60 );
		$hours   = floor( $secs / 3600 );

		$seconds = str_pad( $seconds, 2, "0", STR_PAD_LEFT );
		$minutes = str_pad( $minutes, 2, "0", STR_PAD_LEFT ) . $delimiter;
		$hours = ( $hours > 0 ) ? str_pad( $hours, 2, "0", STR_PAD_LEFT ) . $delimiter : '';

		return "$hours$minutes$seconds";

	}

	require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';

	$mysqli = new mysqli( $hostname, $username, $password, $database );

	$mysqli-> set_charset( $charset );

	$blocks = array();

	$result = $mysqli-> query( "SELECT * FROM `" . $prefix . "blocks`" );

	while( $row = $result-> fetch_assoc() ) {

		$blocks[] = $row[ 'url' ];

	}

	$items = '';

	// if( SITE == 'of' ) {

		//$query = "SELECT * FROM `posts` GROUP BY `post_id` ORDER BY `posted` DESC";
		//$query = "SELECT * FROM `" . $prefix . "posts` WHERE `id` IN ( SELECT MAX(id) FROM `" . $prefix . "posts` GROUP BY `post_id` ) ORDER BY `posted` DESC";

		$result = $mysqli-> query( "SELECT * FROM `" . $prefix . "profiles` ORDER BY `profile_id` DESC LIMIT 1" );
		
		$row = $result-> fetch_assoc();
		
		$profiles = @json_decode( $row[ 'json' ], true );
		$profiles = is_array($profiles) ? $profiles : array();

		$sql = '';

		foreach( $profiles as $profile ) {

			$sql.= $profile[ 'id' ] . ',';

		}
		
		$sql = substr( $sql, 0, -1 );

        $limit = isset( $_GET[ 'limit' ] ) ? $_GET[ 'limit' ] : '10000';
        $limit = isset( $_GET[ 'limit' ] ) && $_GET[ 'limit' ] == 'auto' ? '' : ' LIMIT ' . $limit;

        $year = '';

        if( isset( $_GET[ 'year' ] ) ) {

            $year = " AND YEAR(posted) = " . $_GET[ 'year' ];

        }

        $query = "SELECT * FROM `" . $prefix . "posts` WHERE `id` IN ( SELECT MAX(id) FROM `" . $prefix . "posts` GROUP BY `post_id` )" . $year . " AND `profile_id` IN (" . $sql . ") ORDER BY `posted` DESC" . $limit;

        if( isset( $_GET[ 'profile_username' ] ) ) {

            $query = "SELECT * FROM `" . $prefix . "posts` WHERE `id` IN ( SELECT MAX(id) FROM `" . $prefix . "posts` GROUP BY `post_id` )" . $year . " AND `profile_username` = '" . $_GET[ 'profile_username' ] . "' ORDER BY `posted` DESC" . $limit;

        }

        $vids = array();

        $longest_video_ids = array();

		$result = $mysqli-> query( $query );

		while( $item = $result-> fetch_assoc() ) {

			if( ! in_array( $item[ 'link' ], $blocks ) ) {

			$vd = '';
			$vs = '';

			$videos = @json_decode( $item [ 'videos' ], true );
			$videos = is_array($videos) ? $videos : array();

			$longest_video_duration = 0;
			$longest_video_id = null;
			$longest_video_id_brackets = '';
// var_dump($videos);
			foreach( $videos as $id=> $video ) {

				if( $video[ 'duration' ] > $longest_video_duration ) {
					$longest_video_duration = $video[ 'duration' ];
					$longest_video_id = $id;
					$longest_video_id_brackets = '[' . $longest_video_id . '] ';
					$vd = '|' . duration( $video [ 'duration' ] ) . '| ';
				}
				
				//if ( ! DateTime::createFromFormat( 'Y-m-d\TH:i:sP', $video [ 'created' ] ) ) {
				//    var_dump($video); exit;
				//}
				
				
				
				$vs.= DateTime::createFromFormat( 'Y-m-d\TH:i:sP', $video [ 'created' ] )-> format( 'Y.m.d' ) . ' - ID: ' . $id . ' (duration: ' . duration( $video [ 'duration' ] ) . ')<br />';

			}

			$vs = substr( $vs, 0, -6 );

			$p = 'Photo';

			if( $item [ 'photos' ] == 0 || $item [ 'photos' ] > 1 ) {

				$p.= 's';

			} else {}

			$v = 'Video';

			if( count( $videos ) == 0 || count( $videos ) > 1 ) {

				$v.= 's';

			} else {}

			$usernames = @json_decode( $item[ 'usernames' ], true );
			$usernames = is_array( $usernames ) ? $usernames : array();

			$real_usernames = array();
			$real_usernames[] = $item[ 'profile_username' ];

			if( count( $usernames ) > 0 ) {
				foreach( $usernames as $username ) {
					if( ! in_array( $username, $real_usernames ) ) {
						$real_usernames[] = $username;
					}
				}
			}

			if( count( $real_usernames ) > 0 ) {
				$real_usernames = implode( ' @', $real_usernames );
			} else {
				$real_usernames = '';
			}

			// $title = $vd . $item [ 'profile_name' ] . ' - ' . date( "Y.m.d", strtotime( $item [ 'posted' ] ) ) . ' - ' . mb_substr( $item [ 'title' ], 0, 32, 'utf-8' ) . ' |$' . $item [ 'price' ] . '|';
			$title = 'ID ' . $longest_video_id . ' ' . $vd . $item [ 'profile_name' ] . ' - ' . date( "Y.m.d", strtotime( $item [ 'posted' ] ) ) . ' - @' . $real_usernames;
			$_title = mb_strlen( $item [ 'title' ] ) > 150 ? mb_substr( $item [ 'title' ], 0, 150, 'utf-8' ) . '...' : $item [ 'title' ];
			$title = $vd . $longest_video_id_brackets . $item [ 'profile_name' ] . ' - ' . date( "Y.m.d", strtotime( $item [ 'posted' ] ) ) . ' - ' . $_title;
                
            if( in_array( $longest_video_id, $longest_video_ids ) && isset( $_GET[ 'duplicates' ] ) && $_GET[ 'duplicates' ] == 'none' ) {

                continue;

            } else {
                
                $longest_video_ids[] = $longest_video_id;

            }

			$description = '<p>' . $item [ 'link' ] . '</p><p>' . $p . ': ' . $item [ 'photos' ] . '</p><p>' . $v . ' (' . count( $videos ) . '):</p><p>' . $vs . '</p><p>' . $item [ 'title' ] . '</p>';
			$link = $item [ 'link' ];

			$title = preg_replace( '/[^\x00-\x7f]+/', '', $title );

			$items.= "\t\t<item>\n\t\t\t<title><![CDATA[" . $title . "]]></title>\n\t\t\t<description><![CDATA[" . $description . "]]></description>\n\t\t\t<link>" . $link . "</link>\n\t\t</item>\n";

            $vids[ $longest_video_id ][] = "\t\t<item>\n\t\t\t<title><![CDATA[" . $longest_video_id . ' - ' . $title . "]]></title>\n\t\t\t<description><![CDATA[" . $description . "]]></description>\n\t\t\t<link>" . $link . "</link>\n\t\t</item>\n";

			}

		}

	// }

    if( isset( $_GET[ 'duplicates' ] ) && $_GET[ 'duplicates' ] == 'order' ) {

        $_items = '';

        foreach( $vids as $id=> $_vids ) {

            foreach( $_vids as $_vid ) {

                $_items.= $_vid;

            }

        }
        
        $items = $_items;

    }

	echo '<?xml version="1.0" encoding="UTF-8" ?>';

?>
<rss version="2.0">
	<channel>
		<title>RSS</title>
		<link>https://<?php print $_SERVER [ 'HTTP_HOST' ]; ?>/rss</link>
		<language>en-us</language><?php print "\n" . $items; ?>
   </channel>
</rss>
