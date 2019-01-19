<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Twig\Markup;

class TableOfContentsExtension extends SimpleExtension
{
    protected function registerServices(Application $app)
    {
        if (isset($app['twig.sandbox.policy'])) {
            $app['twig.sandbox.policy'] = $app->share(
                $app->extend('twig.sandbox.policy', function ($policy) {
                    $policy->addAllowedFunction('toc');
                    return $policy;
                })
            );
        }
    }

    protected function registerTwigFunctions()
    {
        $options = ['needs_context' => true, 'is_safe' => ['html']];
        return [
            'toc' => ['toc', $options],
        ];
    }

    protected function registerTwigFilters()
    {
        $options = ['needs_context' => true];
        return [
            'headerids' => ['headerids', $options],
        ];
    }
    public function toc($context, $max = 10)
    {
        $pattern = "/^\s*(#+)(.*)/im";
        $body = $context['record']->values['body'];
        preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);
        $base = min(array_map(function ($val) {
            return strlen($val);
        }, array_column($matches, 1)));
        $html = '<ul>';
        foreach ($matches as $match) {
            $count = strlen($match[1]);
            if ($count <= $max) {
                $level = $count - $base;
                $html = $html . str_repeat("<ul>", $level) . '<li><a href="#' . urlencode(trim($match[2])) . '">' . $match[2] . '</a></li>' . str_repeat("</ul>", $level);
            }
        }
        $html = $html . '</ul>';
        return new Markup($html, 'UTF-8');
    }

    public function headerids($context, $text)
    {
        $pattern = "/<h(\d)>(.*)<\/h\d>/i";
        return new Markup(preg_replace_callback($pattern,
            function ($matches) {
                return '<h' . $matches[1] . ' id="' . urlencode(trim($matches[2])) . '">' . $matches[2] . '</h' . $matches[1] . '>';
            }, $text), 'UTF-8');
    }

}
