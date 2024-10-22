<?php

	require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';

	$mysqli = new mysqli( $hostname, $username, $password, $database );

	$mysqli-> set_charset( $charset );

	$result = $mysqli-> query( "SELECT * FROM `" . $prefix . "profiles` ORDER BY `profile_id` DESC" );

	$row = $result-> fetch_assoc();

	$json = $row[ 'json' ];

	$profiles = json_decode( $json, true );

    $profiles_html = '<div class="row"><div class="col-12 mt-2 text-center"><a class="btn btn-primary" href="/admin/?current=1">Check</a></div></div><div class="row text-center"><div class="col-12 col-sm-6"><h3 class="text-center text-light m-2 mb-0">Current Scraper Session</h3><ul class="d-inline-block list-group p-2">';

	$id = 0;
	$i = 1;

    $rss_list = '';
    $tarea = '';

    $uris = array();

	foreach( $profiles as $profile ) {

        $uris[] = $profile[ 'uri' ];

	    $rss_list.= 'http://' . $_SERVER[ 'HTTP_HOST' ] . '/rss/?profile_username=' . $profile[ 'uri' ] . PHP_EOL;

		$class = $profile[ 'done' ] > 0 ? ' list-group-item-success' : ' list-group-item-light';

		if( $profile[ 'done' ] == 1 && $profiles[ ($id+1) ][ 'done' ] == 0 ) {
			$class = ' list-group-item-warning';
		}

        $profiles_html.= '<li class="text-start list-group-item' . $class . '">' . $i . '/' . count( $profiles ) . '. ' . $profile[ 'name' ] . '<a target="_blank" title="' . $profile['uri'] . '" class="btn btn-primary float-end" href="/admin/?download=' . $profile['id'] . '&username=' . $profile['uri'] . '&name=' . bin2hex($profile['name']) . '">Download</a></li></li>';

		$id++;
		$i++;

	}

    $list = '';

    $_profiles = array(
        'new' => array(),
        'old' => array()
    );

if ( isset( $_GET['current'] ) || isset( $_GET['download'] ) ) {

$_result = $mysqli-> query( "SELECT * FROM `" . $prefix . "options`" );

$opts = array();

while( $_row = $_result-> fetch_assoc() ) {

    $opts[ $_row[ 'option_name' ] ] = $_row[ 'option_value' ];

}

include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'of.php';

$OF = new OF( array(

    'cookie_data'=> $opts[ 'cookie_data' ],
    'user_agent'=> $opts[ 'user_agent' ],
    'user_id'=> $opts[ 'user_id' ],
    'x_bc'=> $opts[ 'x_bc' ]

) );
    
}

    if ( isset( $_GET['current'] ) ) {

        $subscriptions = $OF -> subscriptions_all(0);
        if ( is_array($subscriptions) && count($subscriptions) > 0 ) {
            foreach ( $subscriptions as $subscription ) {
                if ( ! in_array( $subscription['username'], $uris ) ) {
                    $_profiles['new'][] = $subscription;
                } else {
                    $_profiles['old'][] = $subscription;
                }
            }
        }
        
        if (count($_profiles['new']) > 0) {
            $list.= '<h3 class="text-center text-light m-2 mb-0">News</h3><ul class="d-inline-block list-group p-2">';
            $tarea .= '<textarea class="m-0 mt-2 w-100 form-control" rows="6">';
            foreach ( $_profiles['new'] as $_subscription ) {
                $list.= '<li class="text-start list-group-item">' . $_subscription['name'] . '<a target="_blank" class="btn btn-primary float-end" href="/admin/?download=' . $_subscription['id'] . '&username=' . $_subscription['username'] . '&name=' . bin2hex($_subscription['name']) . '">Download</a></li>';
                $tarea.= 'http://' . $_SERVER[ 'HTTP_HOST' ] . '/rss/?profile_username=' . $_subscription[ 'username' ] . PHP_EOL;
            }
            $tarea .= '</textarea>';
            $list.='</ul>';
        } else {
            $list.= '<span class="text-light">Not found new subscribers.</span>';
        }

        if (count($_profiles['old']) > 0) {
/*
            $list.= '<h3 class="text-center text-light m-2 mb-0">Current</h3><ul class="d-inline-block list-group p-2">';
            foreach ( $_profiles['old'] as $_subscription ) {
                // $_subscription['name']
                // $_subscription['username']
                // $_subscription['id']
                $list.= '<li class="text-start list-group-item">' . $_subscription['name'] . '<a class="btn btn-primary float-end" href="/admin/?download=' . $_subscription['id'] . '&username=' . $_subscription['username'] . '&name=' . bin2hex($_subscription['name']) . '">Download</a></li>';
            }
            $list.='</ul>';
*/
        }

    }

    if ( isset( $_GET['download'] ) ) {

        $profile_id = $_GET['download'];
        $profile_name = hex2bin($_GET['name']);
        $profile_name = $mysqli-> real_escape_string( $profile_name );
        $profile_uri = $_GET['username'];
        
        $posts = $OF-> posts_all( $profile_id, 0 );

        if( count( $posts ) > 0 ) {

            // <old>

            $query = "";

            foreach( $posts as $id=> $post ) {

                // preg_match_all( "/@(.*?) /", $post[ 'rawText' ], $usernames );
                preg_match_all( "/@([0-9a-z-_]+)/", $post[ 'rawText' ], $usernames );
                $usernames = json_encode( $usernames[ 1 ] );

                if( array_key_exists( 'media', $post ) && count( $post[ 'media' ] ) > 0 ) {

                    $photos = 0;
                    $videos = array();

                    foreach( $post[ 'media' ] as $media ) {

                        if( $media[ 'type' ] == 'photo' ) {

                            $photos++;

                        } elseif( $media[ 'type' ] == 'video' ) {

                            $videos[ $media[ 'id' ] ] = array( 'duration'=> $media[ 'source' ][ 'duration' ], 'created'=> $media[ 'createdAt' ] );

                        } else {}

                    }

                    $price = $post[ 'price' ];

                    if( count( $videos ) > 0 && $price > 0 ) {

                        // id
                        $posted = date( 'Y-m-d H:i:s', $post[ 'postedAtPrecise' ] );
                        $post_id = $post[ 'id' ];
                        $title = $mysqli-> real_escape_string( $post[ 'rawText' ] ); // $post[ 'text' ];
                        // duration
                        // separated
                        // $profile_id
                        // $profile_name;
                        $price = $post[ 'price' ];
                        $link = 'https://onlyfans.com/' . $post[ 'id' ] . '/' . $profile_uri;
                        // thumb
                        // $photos
                        $videos = json_encode( $videos ); // , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ?

                        $query.= "INSERT INTO `" . $prefix . "posts` ( `posted`, `post_id`, `title`, `profile_id`, `profile_name`, `profile_username`, `usernames`, `price`, `link`, `photos`, `videos` ) VALUES ( '" . $posted . "', " . $post_id . ", '" . $title . "', " . $profile_id . ", '" . $profile_name . "', '" . $profile_uri . "', '" . $usernames . "', " . $price . ", '" . $link . "', " . $photos . ", '" . $videos . "' ); ";

                    } else {}

                } else {}

            }

            if( strlen( $query ) > 0 ) {

                $mysqli-> multi_query( substr( $query, 0, -2 ) ) or die( $mysqli-> error );
                while( $mysqli-> next_result() ) { ; } // flush multi_queries
                
                echo "<script>window.close();</script>";

            } else {}

            // </old>

        }

    }

    $profiles_html.= '</ul></div><div class="col-12 col-sm-6">' . $list . $tarea . '</div></div>';

?>
<!DOCTYPE html>
<html lang="en">

	<head>

		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Admin</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous" />
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css" />

		<style>

			body { background-color: black }

		</style>

	</head>

	<body>
