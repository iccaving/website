<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;

class CavepeepsExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        $options = ['needs_context' => true, 'is_safe' => ['html']];
        $optionsNoContext = ['needs_context' => false];
        return [
            'caversearch' => ['caversearch', $options],
            'cavesearch' => ['cavesearch', $options],
            'allcaves' => ['allcaves', $options],
            'allcavers' => ['allcavers', $options],
            'tripsByAcademicYear' => ['tripsByAcademicYear', $options]
        ];
    }

    function authorsearch($context, $caverId)
    {
        $app = $this->getContainer();
        $articles = [];
        $results = $app['db']->fetchAll('SELECT id FROM u666684881_rcc_caving.bolt_articles WHERE authors LIKE "%\"' . $caverId . '\"%"');
        if (count($results) < 1) {
            return [];
        }
        $articleIDs = array();
        array_walk($results, function ($a) use (&$articleIDs) {
            $articleIDs[] = $a['id'];
        });
        $results = $app['query']->getContent((string)'articles', ['id' => join(" || ", $articleIDs)]);
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
            FROM u666684881_rcc_caving.bolt_field_value
            GROUP BY content_id, `grouping`) AS T
            WHERE People LIKE "%\"' . $caverId . '\"%"'
        );
        $results = array();
        foreach ($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
        }

        // Hydrate the articles, caves and people
        $articleIDs = array();
        array_walk($results, function ($a) use (&$articleIDs) {
            $articleIDs[] = $a['content_id'];
        });
        $articles = $app['query']->getContent((string)'articles', ['id' => join(" || ", $articleIDs), 'status' => 'published']);
        $articlesById = array();
        foreach ($articles as $article) {
            $articlesById[$article['id']] = $article;
        }
        $caveIDs = array();
        array_walk($results, function ($a) use (&$caveIDs) {
            $caveIDs = array_merge($caveIDs, $a['Cave']);
        });
        $caves = $app['query']->getContent((string)'caves', ['id' => join(" || ", $caveIDs)]);
        $cavesById = array();
        foreach ($caves as $cave) {
            $cavesById[$cave['id']] = $cave;
        }
        $caverIDs = array();
        array_walk($results, function ($a) use (&$caverIDs) {
            $caverIDs = array_merge($caverIDs, $a['People']);
        });
        $cavers = $app['query']->getContent((string)'cavers', ['id' => join(" || ", $caverIDs)]);
        $caversById = array();
        foreach ($cavers as $caver) {
            $caversById[$caver['id']] = $caver;
        }

        // Convert to trips
        $trips = array();
        foreach ($results as $result) {
            if (array_key_exists($result['content_id'], $articlesById)) {
                $caves = array();
                foreach ($result['Cave'] as $cave) {
                    array_push($caves, $cavesById[$cave]);
                };
                $attendees = array();
                foreach ($result['People'] as $othercaverID) {
                    // Don't include self as an attendee.
                    if ($othercaverID != $caverId){
                        array_push($attendees, $caversById[$othercaverID]);    
                    }
                };
                array_push($trips, ['article' => $articlesById[$result['content_id']], 'caves' => $caves,
                                    'date' => $result['Date'], 'attendees' => $attendees]);
            }
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
        if (count($caveIDsCount) < 1) {
            $data['caves'] = ['top' => [], 'count' => 0];
        } else {
            $top = array_slice($caveIDsCount, 0, 10, true);
            $topIds = array_keys($top);
            $topCombined = array();
            foreach ($topIds as $id) {
                array_push($topCombined, ['cave' => $cavesById[$id], 'count' => $top[$id]]);
            }
            usort($topCombined, function ($item1, $item2) {
                return $item2['count'] > $item1['count'] ? 1 : -1;
            });
            $data['caves'] = ['top' => $topCombined, 'count' => count($caveIDsCount)];
        }
        // Get top cavers
        $caverIDsCount = array_count_values($caverIDs);
        arsort($caverIDsCount);
        unset($caverIDsCount[$caverId]);
        if (count($caverIDsCount) < 1) {
            $data['cavers'] = ['top' => [], 'count' => 0];
        } else {
            $top = array_slice($caverIDsCount, 0, 10, true);
            $topIds = array_keys($top);
            $topCombined = array();
            foreach ($topIds as $id) {
                array_push($topCombined, ['caver' => $caversById[$id], 'count' => $top[$id]]);
            }
            usort($topCombined, function ($item1, $item2) {
                return $item2['count'] > $item1['count'] ? 1 : -1;
            });
            $data['cavers'] = ['top' => $topCombined, 'count' => count($caverIDsCount)];
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
            FROM u666684881_rcc_caving.bolt_field_value
            GROUP BY content_id, `grouping`) AS T
            WHERE Cave LIKE "%\"' . $caveId . '\"%"'
        );
        $results = array();
        foreach ($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
        }

        // Hydrate the articles
        $articleIDs = array();
        array_walk($results, function ($a) use (&$articleIDs) {
            $articleIDs[] = $a['content_id'];
        });
        $articles = $app['query']->getContent((string)'articles', ['id' => join(" || ", $articleIDs), 'status' => 'published']);
        $articlesById = array();
        foreach ($articles as $article) {
            $articlesById[$article['id']] = $article;
        }
        // Convert to trips
        $trips = array();
        foreach ($results as $result) {
            if (array_key_exists($result['content_id'], $articlesById)) {
                array_push($trips, ['article' => $articlesById[$result['content_id']], 'date' => $result['Date']]);
            }
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
        $unpub_raw = $app['db']->fetchAll('SELECT id FROM u666684881_rcc_caving.bolt_articles WHERE status != "published"');
        $unpub = join(",", array_column($unpub_raw, 'id'));
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM (SELECT content_id, `grouping`, max(CASE WHEN fieldname = "Cave" THEN value_json_array END) AS Cave,
            MAX(CASE WHEN fieldname = "People" THEN value_json_array END) AS People,
            MAX(CASE WHEN fieldname = "Date" THEN value_date END) AS `Date`
            FROM u666684881_rcc_caving.bolt_field_value
            WHERE content_id NOT IN (' . $unpub . ')
            GROUP BY content_id, `grouping`) AS T'
        );
        $data = array();
        $results = array();
        foreach ($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
            foreach ((array) $result['Cave'] as $caveId) {
                if (array_key_exists($caveId, $data)) {
                    $data[$caveId]['count'] = $data[$caveId]['count']  + 1;
                    if ($data[$caveId]['date'] < $result['Date']) {
                        $data[$caveId]['date'] = $result['Date'];
                    }
                } else {
                    $data[$caveId] = ['count' => 1, 'date' => $result['Date']];
                }
            }
        }
        $caves = $app['query']->getContent((string)'caves', ['id' => join(" || ", array_keys($data))]);
        foreach ($caves as $cave) {
            $data[$cave['id']]['cave'] = $cave;
        }
        usort($data, function ($item1, $item2) {
            return -1 * (strcmp($item2['cave']['name'], $item1['cave']['name']));
        });
        return $data;
    }

    public function allcavers($context)
    {
        $app = $this->getContainer();
        $unpub_raw = $app['db']->fetchAll('SELECT id FROM u666684881_rcc_caving.bolt_articles WHERE status != "published"');
        $unpub = join(",", array_column($unpub_raw, 'id'));
        $raw_results = $app['db']->fetchAll(
            'SELECT * FROM (SELECT content_id, `grouping`, max(CASE WHEN fieldname = "Cave" THEN value_json_array END) AS Cave,
            MAX(CASE WHEN fieldname = "People" THEN value_json_array END) AS People,
            MAX(CASE WHEN fieldname = "Date" THEN value_date END) AS `Date`
            FROM u666684881_rcc_caving.bolt_field_value
            WHERE content_id NOT IN (' . $unpub . ')
            GROUP BY content_id, `grouping`) AS T'
        );
        $data = array();
        $results = array();
        foreach ($raw_results as $result) {
            $result['Cave'] = json_decode($result['Cave']);
            $result['People'] = json_decode($result['People']);
            array_push($results, $result);
            foreach ((array) $result['People'] as $caverId) {
                if (array_key_exists($caverId, $data)) {
                    $data[$caverId]['count'] = $data[$caverId]['count']  + 1;
                    if ($data[$caverId]['date'] < $result['Date']) {
                        $data[$caverId]['date'] = $result['Date'];
                    }
                } else {
                    $data[$caverId] = ['count' => 1, 'date' => $result['Date']];
                }
            }
        }
        $cavers = $app['query']->getContent((string)'cavers', ['id' => join(" || ", array_keys($data))]);
        foreach ($cavers as $caver) {
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
        $raw_results = $app['db']->fetchAll('SELECT *, date, CASE WHEN MONTH(date) < 9 THEN CONCAT(YEAR(date) - 1, " - ", YEAR(date)) ELSE CONCAT(YEAR(date), " - ", YEAR(date) + 1) END AS ACYEAR FROM u666684881_rcc_caving.bolt_articles WHERE type = "trip" AND status = "published" ORDER BY date DESC');
        $data = array();
        foreach ($raw_results as $result) {
            $key = $result['ACYEAR'];
            $result['link'] = $app['config']->get('general/siteurl') . '/article/' . $result['slug'];
            if (array_key_exists($key, $data)) {
                array_push($data[$key], $result);
            } else {
                $data[$key] = [$result];
            }
        }
        return $data;

        $articles = $app['query']->getContent((string)'articles', ['type' => 'trip', 'order' => '-date', 'status' => 'published']);
        $data = array();
        foreach ($articles as $article) {
            $year = date("Y", strtotime($article['date']));
            $month = date("m", strtotime($article['date']));
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
}
