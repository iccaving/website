<?php

namespace Bolt\Extension\Rhys\Cavepeeps;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Extension\SimpleExtension;
use Bolt\Controller\Zone;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Markup;

/**
 * ExtensionName extension class.
 *
 * @author Your Name <you@example.com>
 */
class CavepeepsExtension extends SimpleExtension
{
    /*
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        return [
            // Web assets that will be loaded in the frontend
            // new Stylesheet('extension.css'),
            // new JavaScript('extension.js'),
            // Web assets that will be loaded in the backend
            // Note that ::create() requires Bolt 3.3+
            // Stylesheet::create('clippy.js/clippy.css')->setZone(Zone::BACKEND),
            // JavaScript::create('extensions.js')->setZone(Zone::BACKEND),
        ];
    }

    /**
     * We can share our configuration as a service so our other classes can use it.
     *
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $app['myextension.config'] = $app->share(function ($app) {
            return $this->getConfig();
        });
        if (isset($app['twig.sandbox.policy'])) {
            $app['twig.sandbox.policy'] = $app->share(
                $app->extend('twig.sandbox.policy', function ($policy) {
                    $policy->addAllowedFunction('allpeople');
                    $policy->addAllowedFunction('mainimg');
                    $policy->addAllowedFunction('people');
                    $policy->addAllowedFunction('photo');
                    $policy->addAllowedFunction('photolink');
                    $policy->addAllowedFunction('archiveloc');
                    $policy->addAllowedFunction('raw');
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
        $options = [ 'needs_context' => true, 'is_safe' => ['html'] ];
        $optionsNoContext = [ 'needs_context' => false, 'is_safe' => ['html'] ];
        return [
            'allpeople' => [ 'allpeople', $options ],
            'people' => [ 'people', $options ],
            'caversearch' => [ 'caversearch', $options ],
            'cavesearch' => [ 'cavesearch', $options ],
            'allcaves' => [ 'allcaves', $options ],
            'allcavers' => [ 'allcavers', $options ],
            'mainimg' => [ 'mainimg', $options ],   
            'photo' => [ 'photo', $options ],     
            'photolink' => [ 'photolink', $options ],
            'tripsByAcademicYear' => [ 'tripsByAcademicYear', $options ],
            'tours' => [ 'tours', $options ],
            'archiveloc' => [ 'archiveloc', $optionsNoContext ],
            'photoreel' => [ 'photoreel', $options ],
        ];
    }

    public function archiveloc($record) {
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

    public function archivelocFromContext($context) {
        return $this->archiveloc($context['record']);;
    }

    function mainimg_url($context) {
        $archive_loc = $this->archivelocFromContext($context);
        $mainimg = $context['record']['main_image'];
        $image = '';
        if (!empty($mainimg)) {
            $image = $archive_loc . "/" . $mainimg;
        }
        return $image;
    }

    public function mainimg($context)
    {
        $archive_loc = $this->archivelocFromContext($context);
        $mainimg = $context['record']['main_image'];
        if (!empty($mainimg)) {
            $image = $archive_loc . "/" . $mainimg;
        }
        $html = "\n\n<span class='mainimg'><a href=" . $archive_loc . "><img src='" . $image . "'></a></span>\n\n";
        //$html = "</p>\n\n<a href='" . $archive_loc . "'><img src='" . $image . "'></a>\n\n";
        return new Markup($html, 'UTF-8');
    }

    public function photolink($context)
    {
        $archive_loc = $this->archivelocFromContext($context);
        $html = '<span class="photo-button-wrapper"><a class="photo-button" href="' . $archive_loc . '">Photos</a></span>';
        return new Markup($html, 'UTF-8');
    }

    public function photo($context, $imagelink, $position, $caption, $external, $a_link)
    {
        $archive_loc = $this->archivelocFromContext($context);
        if (empty($external)) {
            $image = $archive_loc . "/" . $imagelink;
            if (empty($link)) {
                $link = $archive_loc . "/" . str_replace(['.jpg','.JPG','.jpeg','.JPEG','.png','.PNG','.gif','.GIF','.svg','.SVG'],'.html',$imagelink);
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
	    $queryselector = $queryselector." || ".join(' || ', $row['People']);
        }
        $queryselector = substr($queryselector, 4);
        $results =  $app['query']->getContent((string) 'cavers', ['id' => $queryselector]);
        foreach ($results as $caver) {
            $allpeople = $allpeople.", <a href='".$caver->link()."'>".$caver->name."</a>";
        }
        $html = substr($allpeople, 2);
        return new Markup($html, 'UTF-8');
    }

    public function people($context, $date, $caves_raw, $index)
    {
        $app = $this->getContainer();
        $caves = array_map(function($x) {return trim($x);}, preg_split("/>/",$caves_raw));
        $caveobjects = $app['query']->getContent('caves', ['name' => join(" || ", $caves)]);
        $caveids = [];
        foreach($caveobjects as $caveobject) {
            array_push($caveids, (string) $caveobject['id']);
        }
        sort($caveids);
        $people = '';
        foreach ($context['record']['cavepeeps'] as $row) {
            $metacaveids = $row['Cave'];
            sort($metacaveids);
            if ($metacaveids === $caveids) {
                $results =  $app['query']->getContent((string) 'cavers', ['id' => join(' || ', $row['People'])]);
                foreach ($results as $caver) {
                    $people = $people.", <a href='".$caver->link()."'>".$caver->name."</a>";
                }
            }
        }
        $html = substr($people, 2);
        return new Markup($html, 'UTF-8');
    }

    function authorsearch($context, $caverId)
    {
        $app = $this->getContainer();
        $articles = [];
        $results = $app['db']->fetchAll('SELECT id FROM rcc_caving.bolt_articles WHERE authors LIKE "%\"' . $caverId . '\"%"');
        $articleIDs = array();
        array_walk($results, function($a) use (&$articleIDs) { $articleIDs[] = $a['id']; });
        $results = $app['query']->getContent((string) 'articles', ['id' => join(" || ", $articleIDs)]);
        $articles = iterator_to_array($results);
        usort($articles, function ($item1, $item2) {
            return strcmp($item2['date'], $item1['date']);
        });
        return $articles;
    }

    public function caversearch($context, $caverId)
    {
        // Get trip data for person
        $data = array();
        $app = $this->getContainer();
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM (SELECT content_id, `grouping`, max(CASE WHEN fieldname = "Cave" THEN value_json_array END) AS Cave,
            MAX(CASE WHEN fieldname = "People" THEN value_json_array END) AS People,
            MAX(CASE WHEN fieldname = "Date" THEN value_date END) AS `Date`
            FROM rcc_caving.bolt_field_value
            GROUP BY content_id, `grouping`) AS T
            WHERE People LIKE "%\"' . $caverId . '\"%"');
        $results = array();
        foreach($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
        }

        // Hydrate the articles, caves and people
        $articleIDs = array();
        array_walk($results, function($a) use (&$articleIDs) { $articleIDs[] = $a['content_id']; });
        $articles = $app['query']->getContent((string) 'articles', ['id' => join(" || ", $articleIDs)]);
        $articlesById = array();
        foreach($articles as $article) {
            $articlesById[$article['id']] = $article;
        }
        $caveIDs = array();
        array_walk($results, function($a) use (&$caveIDs) { $caveIDs = array_merge($caveIDs, $a['Cave']); });
        $caves = $app['query']->getContent((string) 'caves', ['id' => join(" || ", $caveIDs)]);
        $cavesById = array();
        foreach($caves as $cave) {
            $cavesById[$cave['id']] = $cave;
        }
        $caverIDs = array();
        array_walk($results, function($a) use (&$caverIDs) { $caverIDs = array_merge($caverIDs, $a['People']); });
        $cavers = $app['query']->getContent((string) 'cavers', ['id' => join(" || ", $caverIDs)]);
        $caversById = array();
        foreach($cavers as $caver) {
            $caversById[$caver['id']] = $caver;
        }

        // Convert to trips
        $trips = array();
        foreach($results as $result) {
            $caves = array();
            foreach($result['Cave'] as $cave) {
                array_push($caves, $cavesById[$cave]);
            };
            array_push($trips, ['article'=>$articlesById[$result['content_id']], 'caves'=>$caves, 'date'=>$result['Date']]);
        }
        usort($trips, function ($item1, $item2) {
            return strcmp($item2['date'], $item1['date']);
        });
        $data['trips'] = $trips;

        // Get authored
        $data['authored'] = $this->authorsearch($context, $caverId);

        // Get top caves
        $caveIDsCount = array_count_values($caveIDs);
        arsort($caveIDsCount);
        if (count($caveIDsCount) <= 1) {
            $data['caves'] = [];
        } else {
            $top = array_slice($caveIDsCount, 1, 10,true);
            $topIds = array_keys($top);
            $topCombined = array();
            foreach($topIds as $id) {
                array_push($topCombined, ['cave'=>$cavesById[$id], 'count'=>$top[$id]]);
            }
            usort($topCombined, function ($item1, $item2) {
                return strcmp($item2['count'], $item1['count']);
            });
            $data['caves'] = ['top'=>$topCombined,'count'=>count($caveIDsCount)];
        }
        // Get top cavers
        $caverIDsCount = array_count_values($caverIDs);
        arsort($caverIDsCount);
        if (count($caverIDsCount) <= 1) {
            $data['cavers'] = [];
        } else {
            $top = array_slice($caverIDsCount, 1, 10,true);
            $topIds = array_keys($top);
            $topCombined = array();
            foreach($topIds as $id) {
                array_push($topCombined, ['caver'=>$caversById[$id], 'count'=>$top[$id]]);
            }
            usort($topCombined, function ($item1, $item2) {
                return strcmp($item2['count'], $item1['count']);
            });
            $data['cavers'] = ['top'=>$topCombined,'count'=>count($caverIDsCount)];
        }
        return $data;
    }

    public function cavesearch($context, $caveId)
    {
        $app = $this->getContainer();
        $data = array();
        $app = $this->getContainer();
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM (SELECT content_id, `grouping`, max(CASE WHEN fieldname = "Cave" THEN value_json_array END) AS Cave,
            MAX(CASE WHEN fieldname = "People" THEN value_json_array END) AS People,
            MAX(CASE WHEN fieldname = "Date" THEN value_date END) AS `Date`
            FROM rcc_caving.bolt_field_value
            GROUP BY content_id, `grouping`) AS T
            WHERE Cave LIKE "%\"' . $caveId . '\"%"');
        $results = array();
        foreach($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
        }
        // Hydrate the articles
        $articleIDs = array();
        array_walk($results, function($a) use (&$articleIDs) { $articleIDs[] = $a['content_id']; });
        $articles = $app['query']->getContent((string) 'articles', ['id' => join(" || ", $articleIDs)]);
        $articlesById = array();
        foreach($articles as $article) {
            $articlesById[$article['id']] = $article;
        }
        // Convert to trips
        $trips = array();
        foreach($results as $result) {
            array_push($trips, ['article'=>$articlesById[$result['content_id']], 'date'=>$result['Date']]);
        }
        usort($trips, function ($item1, $item2) {
            return strcmp($item2['date'], $item1['date']);
        });
        $data['trips'] = $trips;
        return $data;
    }
    public function allcaves($context)
    {
        $app = $this->getContainer();
        $data = array();
        $app = $this->getContainer();
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM (SELECT content_id, `grouping`, max(CASE WHEN fieldname = "Cave" THEN value_json_array END) AS Cave,
            MAX(CASE WHEN fieldname = "People" THEN value_json_array END) AS People,
            MAX(CASE WHEN fieldname = "Date" THEN value_date END) AS `Date`
            FROM rcc_caving.bolt_field_value
            GROUP BY content_id, `grouping`) AS T');
        $data = array();
        $results = array();
        foreach($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
            foreach($result['Cave'] as $caveId) {
                if (array_key_exists($caveId, $data)) {
                    $data[$caveId]['count'] = $data[$caveId]['count']  + 1;
                    if ($data[$caveId]['date'] < $result['Date']) {
                        $data[$caveId]['date'] = $result['Date'];
                    }
                } else {
                    $data[$caveId] = ['count'=>1, 'date'=>$result['Date']];
                }
            }
        }
        $caves = $app['query']->getContent((string) 'caves', ['id' => join(" || ", array_keys($data))]);
        foreach($caves as $cave) {
            $data[$cave['id']]['cave'] = $cave;
        }
        return $data;
    }

    public function allcavers($context)
    {
        $app = $this->getContainer();
        $data = array();
        $app = $this->getContainer();
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM (SELECT content_id, `grouping`, max(CASE WHEN fieldname = "Cave" THEN value_json_array END) AS Cave,
            MAX(CASE WHEN fieldname = "People" THEN value_json_array END) AS People,
            MAX(CASE WHEN fieldname = "Date" THEN value_date END) AS `Date`
            FROM rcc_caving.bolt_field_value
            GROUP BY content_id, `grouping`) AS T');
        $data = array();
        $results = array();
        foreach($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
            foreach($result['People'] as $caverId) {
                if (array_key_exists($caverId, $data)) {
                    $data[$caverId]['count'] = $data[$caverId]['count']  + 1;
                    if ($data[$caverId]['date'] < $result['Date']) {
                        $data[$caverId]['date'] = $result['Date'];
                    }
                } else {
                    $data[$caverId] = ['count'=>1, 'date'=>$result['Date']];
                }
            }
        }
        $cavers = $app['query']->getContent((string) 'cavers', ['id' => join(" || ", array_keys($data))]);
        foreach($cavers as $caver) {
            $data[$caver['id']]['caver'] = $caver;
        }
        usort($data, function ($item1, $item2) {
            if ($item2['date'] != $item1['date']) {
                return strcmp($item2['date'], $item1['date']);
            } else {
                return -1 * (strcmp($item2['caver']['name'], $item1['caver']['name']));
            }
        });
        return $data;
    }

    public function tripsByAcademicYear($context)
    {
        $app = $this->getContainer();
        $articles = $app['query']->getContent((string) 'articles', ['type' => 'trip', 'order' => '-date']);
        $data = array();
        foreach($articles as $article) {
            $year = date("Y",strtotime($article['date']));
            $month = date("m",strtotime($article['date']));
            if ($month >= 9) {
                $key = strval($year) . '-' . strval($year + 1);
            } else {
                $key = strval($year - 1) . '-' . strval($year);
            }
            if (array_key_exists($key, $data)) {
                array_push($data[$key], $article);
            } else {
                $data[$key] = [$article];
            }
        }
        return $data;
    }

    public function photoreel($context)
    {
        $app = $this->getContainer();
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM rcc_caving.bolt_articles WHERE main_image <> \'\' ORDER BY `date` DESC LIMIT 6' );
        $results = array();
        foreach($raw_results as $r) {
            $results[] = $this->mainimg_url(['record'=>$r]);
        }
        $assetlocation = '/theme/iccc/assets';
        $siteurl = '';
        $html = "<div class='photoreel-container'><div class='photoreel-left'><a><img src='" . $assetlocation . "/arrows-left.svg' style='height: 30px;'></a></div>";
        $dots = "<div class='photoreel-dots'>";
        $count = 0;
        foreach($raw_results as $r) {
            $html = $html . "<div class='photoreel-photo photoreel-photo-" . strval($count) . "'><a href='" . $siteurl . "/article.url'><img src='" . $this->mainimg_url(['record'=>$r]) . "'><span class='photoreel-title'>" . $r['title'] . "</span></a></div>";
            $dots = $dots. "<a class='photoreel-dot photoreel-dot-" . strval($count). "' data-count='" . strval($count) . "'></a>";
            $count += 1;
        }
        $html = $html . "<div class='photoreel-right'><a><img src='" . $assetlocation .  "/arrows-right.svg' style='height: 30px;'></a></div>" . $dots . "</div>";
        $html = $html . "<script>var maxcount = " .  strval($count) . ";var transtime = 1 ;var nextslidetime = 5;</script></div>";
        return $html;
    }

}
