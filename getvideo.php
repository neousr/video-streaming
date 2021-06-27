<?php

echo "### Iniciando captura.\n";
$time = microtime(true);

$audio_filename = 'Episodio8_audio';
$video_filename = 'Episodio8_video';
$video_resolution = '1080p';
$episode_url_fragment = '3343/20171123/';

$scheme = 'https://';
$host = 'arsat.cont.ar';
$filename = '/vod-contar-001/' . $episode_url_fragment;
$master_m3u8 = 'stream.m3u8';

// URL
$url = $scheme . $host . $filename;

$response = file_get_contents($url . $master_m3u8);

// Get audio and video segments
$m3u8_segments = get_m3u8_segments($response);

// Get m3u8 audio segment, It is at index[0]
$audio_segment_m3u8 = $m3u8_segments[0];

$response = file_get_contents($url . $audio_segment_m3u8);

$audio_segments = get_audio_segments($response);

// Total audio and video segments
$total_segments = count($audio_segments);

// Create audio file
get_and_create_media_file($audio_filename, $audio_segments, $url, $total_segments);

// Get video resolution segment
$video_segment_m3u8 = get_video_resolution_segment($m3u8_segments, $video_resolution);

$response = file_get_contents($url . $video_segment_m3u8);

$video_segments = get_video_segments($response);

// Create video file
get_and_create_media_file($video_filename, $video_segments, $url, $total_segments);

$time = microtime(true) - $time;
echo "Tiempo total de ejecución: " . round($time, 3) . " segundos.\n";

// Util functions
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
    preg_match_all(
        '/audio_.*m3u8|video_.*m3u8/',
        $response,
        $m3u8_segments, PREG_SET_ORDER
    );
    $temp = [];
    foreach ($m3u8_segments as $m3u8_segment) {
        $temp[] = $m3u8_segment[0];
    }
    return $temp;
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

        fwrite($fp, $response);

        $done += 1;
        $percent = intval(($done / $total_segments) * 100);
        echo "\r$filename $percent% completado $done/$total_segments";
    
        flush();

        if($done == $total_segments) {
            echo "\n";
        }
    }
    curl_close($curl);
    fclose($fp);
}