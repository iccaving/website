<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Twig\Markup;

require_once('HelperFunctions.php');

class ArticleTagsExtension extends SimpleExtension
{
    protected function registerServices(Application $app)
    {
        if (isset($app['twig.sandbox.policy'])) {
            $app['twig.sandbox.policy'] = $app->share(
                $app->extend('twig.sandbox.policy', function ($policy) {
                    $policy->addAllowedFunction('allpeople');
                    $policy->addAllowedFunction('mainimg');
                    $policy->addAllowedFunction('people');
                    $policy->addAllowedFunction('photo');
                    $policy->addAllowedFunction('photolink');
                    $policy->addAllowedFunction('archiveloc');
                    $policy->addAllowedFunction('photoviewloc');
                    return $policy;
                })
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        $options = ['needs_context' => true, 'is_safe' => ['html']];
        $optionsNoContext = ['needs_context' => false];
        return [
            'allpeople' => ['allpeople', $options],
            'people' => ['people', $options],
            'mainimg' => ['mainimg', $options],
            'photo' => ['photo', $options],
            'photolink' => ['photolink', $options],
            'archiveloc' => ['archiveloc', $optionsNoContext],
            'photoviewloc' => ['photoviewloc', $optionsNoContext],
        ];
    }

    public function mainimg($context)
    {
        $archive_loc = archivelocFromContext($context);
        $photoview_loc = photoviewlocFromContext($context);
        $mainimg = $context['record']['main_image'];
        if (!empty($mainimg)) {
            $image = $archive_loc . "/" . $mainimg;
        }
        $html = "\n\n<span class='mainimg'><a href=" . $photoview_loc . "><img src='" . $image . "'></a></span>\n\n";
        //$html = "</p>\n\n<a href='" . $archive_loc . "'><img src='" . $image . "'></a>\n\n";
        return new Markup($html, 'UTF-8');
    }

    public function photolink($context)
    {
        $photoview_loc = photoviewlocFromContext($context);
        $html = '<span class="photo-button-wrapper"><a class="photo-button" href="' . $photoview_loc . '">Photos</a></span>';
        return new Markup($html, 'UTF-8');
    }

    public function photo($context, $imagelink, $position, $caption, $external, $a_link)
    {
        $photoview_loc = photoviewlocFromContext($context);
        $archive_loc = archivelocFromContext($context);
        if (empty($external)) {
            $image = $archive_loc . "/" . $imagelink;
            if (empty($link)) {
                $link = $photoview_loc . "/" . $imagelink;
            }
        } else {
            $image = $imagelink;
            if (empty($link)) {
                $link = $imagelink;
            }
        }
        $html = '<figure class="article-img-' . $position . '"><a href="' . $link . '"><img src="' . $image . '"></a><figcaption><a href="' . $link . '">' . $caption . '</a></figcaption></figure>';
        return new Markup($html, 'UTF-8');
    }

    public function allpeople($context)
    {
        $app = $this->getContainer();
        $allpeople = '';
        $queryselector = '';
        foreach ($context['record']['cavepeeps'] as $row) {
            $queryselector = $queryselector . " || " . join(' || ', $row['People']);
        }
        $queryselector = substr($queryselector, 4);
        $results = $app['query']->getContent((string) 'cavers', ['id' => $queryselector]);
        foreach ($results as $caver) {
            $allpeople = $allpeople . ", <a href='" . $caver->link() . "'>" . $caver->name . "</a>";
        }
        $html = substr($allpeople, 2);
        return new Markup($html, 'UTF-8');
    }

    public function people($context, $date, $caves_raw, $index)
    {
        $app = $this->getContainer();
        $caves = array_map(function ($x) {return trim($x);}, preg_split("/>/", $caves_raw));
        $caveobjects = $app['query']->getContent('caves', ['name' => join(" || ", $caves)]);
        $caveids = [];
        foreach ($caveobjects as $caveobject) {
            array_push($caveids, (string) $caveobject['id']);
        }
        sort($caveids);
        $people = '';
        foreach ($context['record']['cavepeeps'] as $row) {
            $metacaveids = $row['Cave'];
            sort($metacaveids);
            if ($metacaveids === $caveids) {
                $results = $app['query']->getContent((string) 'cavers', ['id' => join(' || ', $row['People'])]);
                foreach ($results as $caver) {
                    $people = $people . ", <a href='" . $caver->link() . "'>" . $caver->name . "</a>";
                }
            }
        }
        $html = substr($people, 2);
        return new Markup($html, 'UTF-8');
    }
}
