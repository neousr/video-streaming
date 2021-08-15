<?php

$m3u8 = 'https://rtvehlsvod2020a-fsly.vod-rtve.cross-media.es/resources/TE_NGVA/mp4/4/2/1376474778324.mp4/1376474778324-audio=95992-video=703976.m3u8?hls_minimum_fragment_length=6&hls_client_manifest_version=3&idasset=1991107';

$url = 'https://rtvehlsvod2020a-fsly.vod-rtve.cross-media.es/resources/TE_NGVA/mp4/4/2/1376474778324.mp4/';

$filename = 'canada-primavera-esquimal';

$response = file_get_contents($m3u8);

$m3u8_video_segments = get_m3u8_segments($response);

$total_segments = count( $m3u8_video_segments );

get_and_create_media_file($filename, $m3u8_video_segments, $url, $total_segments);

function fileGetContents($url) {
    $options = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_ENCODING => ""];
    $curl = curl_init($url);
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

// Get m3u8 audio and video segments
function get_m3u8_segments($response) {
    // https://stackoverflow.com/questions/41870442/get-and-return-media-url-m3u8-using-php
    preg_match_all(
        '/137.*107/',
        $response,
        $matches, PREG_SET_ORDER
    );
    $m3u8_segments = [];
    foreach ($matches as $match) {
        $m3u8_segments[] = $match[0];
    }
    return $m3u8_segments;
}

// Get and create a .ts media file
function get_and_create_media_file($filename, $segments, $url, $total_segments) {
    $options = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_ENCODING => ""];
    $fp = fopen('media/' . $filename . '.ts', 'wb');
    foreach($segments as $done => $segment) {
        // echo $url . $segment . "\n";
        $curl = curl_init($url . $segment);
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        fwrite($fp, $response);

        $done += 1;
        $percent = intval(($done / $total_segments) * 100);
        echo "\r$filename $percent% completado $done/$total_segments";

        flush();

        if($done == $total_segments) {
            echo "\n";
        }
    }
    fclose($fp);
}


