<?php

echo "### Iniciando captura.\n";
$time = microtime(true);

$audio_filename = 'Episodio10_audio';
$video_filename = 'Episodio10_video';
$video_resolution = '480p';
$episode_url_fragment = '3345/20171123/';

$scheme = 'https://';
$host = 'arsat.cont.ar';
$filename = '/vod-contar-001/' . $episode_url_fragment;
$m3u8_master = 'stream.m3u8';

// URL
$url = $scheme . $host . $filename;

$response = file_get_contents($url . $m3u8_master);

// Get video and audio segments
$m3u8_segments = get_m3u8_segments($response);

// ********** Get Video **********

// Get video resolution segment
$m3u8_video_segment = get_video_resolution_segment($m3u8_segments, $video_resolution);

$response = file_get_contents($url . $m3u8_video_segment);

$video_segments = get_video_segments($response);

// Total audio and video segments
$total_segments = count($video_segments);

// Create video file
get_and_create_media_file($video_filename, $video_segments, $url, $total_segments);

// ********** Get Audio **********

// Get m3u8 audio segment, It is at index[0]
$m3u8_audio_segment = $m3u8_segments[0];

$response = file_get_contents($url . $m3u8_audio_segment);

$audio_segments = get_audio_segments($response);

// Create audio file
get_and_create_media_file($audio_filename, $audio_segments, $url, $total_segments);

$time = microtime(true) - $time;
echo "Tiempo total de ejecución: " . round($time, 3) . " segundos.\n";

/**
 * Util functions
 */

// Get m3u8 video resolution segment
function get_video_resolution_segment($m3u8_segments, $resolution = '240p') {
    $resolutions = ['240p', '360p', '480p', '720p', '1080p'];
    if (!in_array($resolution, $resolutions)) {
        return $m3u8_segments[5]; // default resolution 240p
    }
    $n = count($m3u8_segments);
    for($i = 1; $i < $n; $i++) {
        if (str_contains( $m3u8_segments[$i], $resolution )) {
            return $m3u8_segments[$i];
        }
    }
}
// Get m3u8 audio and video segments
function get_m3u8_segments($response) {
    // https://stackoverflow.com/questions/41870442/get-and-return-media-url-m3u8-using-php
    preg_match_all(
        '/audio_.*m3u8|video_.*m3u8/',
        $response,
        $matches, PREG_SET_ORDER
    );
    $m3u8_segments = [];
    foreach ($matches as $match) {
        $m3u8_segments[] = $match[0];
    }
    return $m3u8_segments;
}
// Get only .ts audio segments
function get_audio_segments($response) {
    preg_match_all(
        '/audio.*ts/',
        $response,
        $audio_segments, PREG_SET_ORDER
    );
    return $audio_segments;
}
// Get only .ts video segments
function get_video_segments($response) {
    preg_match_all(
        '/video.*ts/',
        $response,
        $video_segments, PREG_SET_ORDER
    );
    return $video_segments;
}
// Get and create a .ts media file
function get_and_create_media_file($filename, $segments, $url, $total_segments) {
    $options = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_ENCODING => ""];
    $fp = fopen('files/' . $filename . '.ts', 'wb');
    foreach($segments as $done => $segment) {
        $url_segment = $segment[0];
        // echo $url . $url_segment . "\n";
        $curl = curl_init($url . $url_segment);
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