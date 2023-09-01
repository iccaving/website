<?php
function archivefileloc($record)
{
    $archive_loc = '';
    $photoarchive = $record['photoarchive'];
    $type = $record['type'];
    $location = $record['location'];
    $date = $record['date'];
    if ($photoarchive == '') {
        $archive_loc = '';
        if (!empty($type)) {
            $archive_loc = $archive_loc . '/' . strtolower($type) . 's';
        }
        if (!empty($location) && !empty($date)) {
            $archive_loc = $archive_loc . '/' . $date . ' - ' . strtolower($location);
        }
    } else {
        $archive_loc = $photoarchive;
    }
    return $archive_loc;
}

function archiveloc($record)
{
    $archive_loc = archivefileloc($record);
    $photoarchive = $record['photoarchive'];
    if ($photoarchive == '') {
        return '/photo_archive'.$archive_loc;
    } else {
        return $archive_loc;
    }
}

function photoviewloc($record) {
    return rtrim(str_replace("photo_archive", "photos", archiveloc($record)),'/');
}

function archivelocFromContext($context)
{
    return archiveloc($context['record']);
}

function photoviewlocFromContext($context)
{
    return photoviewloc($context['record']);
}

function mainimg_url($context)
{
    $archive_loc = archivelocFromContext($context);
    $mainimg = $context['record']['main_image'];
    $image = '';
    if (!empty($mainimg)) {
        $image = $archive_loc . "/" . $mainimg;
    }
    return $image;
}

function file_exists_cs($fileName, $caseSensitive = false) {
    if(file_exists($fileName)) {
        return $fileName;
    }
    if($caseSensitive) return false;
    // Handle case insensitive requests            
    $directoryName = dirname($fileName);
    $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
    $fileNameLowerCase = preg_replace('~\.[jJ][pP][eE][gG]~', '.jpg', strtolower($fileName));
    foreach($fileArray as $file) {
        $test = preg_replace('~\.[jJ][pP][eE][gG]~', '.jpg', strtolower($file));
        if($test == $fileNameLowerCase) {
            return $file;
        }
    }
    return false;
}
