<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;

require_once 'HelperFunctions.php';

class PhotoViewerExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        $options = ['needs_context' => true, 'is_safe' => ['html']];
        return [
            'photoreel' => ['photoreel', $options],
            'dophotos' => ['dophotos', $options],
        ];
    }

    public function photoreel($context)
    {
        $app = $this->getContainer();
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM rcc_caving.bolt_articles WHERE main_image <> \'\' ORDER BY `date` DESC LIMIT 6');
        $results = array();
        foreach ($raw_results as $r) {
            $results[] = mainimg_url(['record' => $r]);
        }
        $assetlocation = '/rcc/caving/theme/iccc/assets';
        $html = "<div class='photoreel-container'><div class='photoreel-left'><a><img src='" . $assetlocation . "/arrows-left.svg' style='height: 30px;'></a></div>";
        $dots = "<div class='photoreel-dots'>";
        $count = 0;
        foreach ($raw_results as $r) {
            $html = $html . "<div class='photoreel-photo photoreel-photo-" . strval($count) . "'><a href='" . $app['config']->get('general/siteurl') . '/article/' . $r['slug'] . "'><img src='" . mainimg_url(['record' => $r]) . "'><span class='photoreel-title'>" . $r['title'] . "</span></a></div>";
            $dots = $dots . "<a class='photoreel-dot photoreel-dot-" . strval($count) . "' data-count='" . strval($count) . "'></a>";
            $count += 1;
        }
        $html = $html . "<div class='photoreel-right'><a><img src='" . $assetlocation . "/arrows-right.svg' style='height: 30px;'></a></div>" . $dots . "</div>";
        $html = $html . "<script>var maxcount = " . strval($count) . ";var transtime = 1 ;var nextslidetime = 5;</script></div>";
        return $html;
    }

    public function dophotos($context)
    {
        $app = $this->getContainer();
        $siteurl = $app['config']->get('general/siteurl');
        $siteroot = $app['config']->get('general/siteroot');

        // Construct file path to directory
        $root = $siteroot . "/files/photo_archive";
        $dir = urldecode(preg_replace('/\/?photos\/?/', '', $app['request']->server->get('REQUEST_URI')));
        $path = $root . '/' . $dir;
        
        // Check if we should be displaying or generating
        parse_str($app['request']->server->get('QUERY_STRING'), $query);
        if (array_key_exists('generate', $query)) {
            chdir(str_replace('?generate','',$path));
            $output = shell_exec($siteroot . '/files/photo_archive/scripts/do_photos -o');
            return ["result" => $output];
        } else {
            if (file_exists($path)) {
                $urls = array();
                $files = glob($path . "/*--thumb.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);
                foreach ($files as $file) {
                    $urls[] = [
                        'thumb' => [
                            'info' => getimagesize($file),
                            'url' => str_replace($root.'/', "", $file),
                        ],
                        'image' => [
                            'info' => getimagesize(str_replace("--thumb", "", $file)),
                            'url' => str_replace($root.'/', "", str_replace("--thumb", "", $file)),
                        ],
                        'orig' => [
                            'info' => getimagesize(str_replace("--thumb", "--orig", $file)),
                            'url' => str_replace($root.'/', "", str_replace("--thumb", "--orig", $file)),
                        ],
                    ];
                }
                // Create breadcrumbs
                $breadcrumbs = array();
                $partial_path = '';
                $explode = explode('/',$dir);
                $first = true;
                foreach($explode as $p) 
                {   
                    if (!$first) {
                        $partial_path = $partial_path . '/' . $p;
                    } else {
                        $partial_path = $p;
                    }
                    $first = false;
                    $breadcrumbs[] = ["url" => $partial_path, "name" => $p];
                }
                // Create directory listing
                $dirs = array();
                $directories = glob('/' . trim($path, '/') . "/*", GLOB_ONLYDIR);
                foreach ($directories as $directory) 
                {   
                    $explode = explode('/',trim($directory,'/'));
                    $name = array_pop($explode);
                    $dirs[] = ["url" =>  str_replace(trim($root,'/').'/', "", trim($directory,'/')), "name" => $name];
                }
                return ["images" => $urls, "directories" => $dirs, "breadcrumbs" => $breadcrumbs];
            } else {
                return ["result" => "Folder does not exist '".$path."'"];
            }
        }
    }
}
