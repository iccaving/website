<?php
function archiveloc($record)
{
    $archive_loc = '';
    $photoarchive = $record['photoarchive'];
    $type = $record['type'];
    $location = $record['location'];
    $date = $record['date'];
    if ($photoarchive == '') {
        $archive_loc = 'https://union.ic.ac.uk/rcc/caving/photo_archive';
        if (!empty($type)) {
            $archive_loc = $archive_loc . '/' . strtolower($type) . 's';
        }
        if (!empty($location) && !empty($date)) {
            $archive_loc = $archive_loc . '/' . $date . '%20-%20' . strtolower($location);
        }
    } else {
        $archive_loc = $photoarchive;
    }
    return $archive_loc;
}

function archivelocFromContext($context)
{
    return archiveloc($context['record']);
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
