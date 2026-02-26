<?php
namespace COAL\Helpers;

abstract class Utils
{
    public static function filter(&$data, $filter)
    {
        $data = array_values(array_filter($data, $filter));
    }

    public static function die($args = null)
    {
        if (is_null($args)) {
            throw new \BgaVisibleSystemException(
                implode('<br>', self::$logmsg)
            );
        }
        throw new \BgaVisibleSystemException(json_encode($args));
    }

    /**
     * Return a string corresponding to an assoc array of resources
     */
    public static function resourcesToStr($resources)
    {
        $descs = [];
        foreach ($resources as $resource => $amount) {
            if (in_array($resource, ['sources', 'sourcesDesc', 'cId'])) {
                continue;
            }

            if ($amount == 0) {
                continue;
            }

            // if (in_array($resource, [ENERGY])) {
            //   $descs[] = '<' . strtoupper($resource) . ':' . $amount . '>';
            // } else {
            $descs[] = $amount . '<' . strtoupper($resource) . '>';
            // }
        }
        return implode(',', $descs);
    }

    public static function tagTree($t, $tags)
    {
        foreach ($tags as $tag => $v) {
            $t[$tag] = $v;
        }

        if (isset($t['childs'])) {
            $t['childs'] = array_map(function ($child) use ($tags) {
                return self::tagTree($child, $tags);
            }, $t['childs']);
        }
        return $t;
    }

    public static function formatFee($cost)
    {
        return [
            'fees' => [$cost],
        ];
    }

    public static function uniqueZones($arr1)
    {
        return array_values(
            array_uunique($arr1, function ($a, $b) {
                return $a['x'] == $b['x']
                    ? $a['y'] - $b['y']
                    : $a['x'] - $b['x'];
            })
        );
    }
}

function array_uunique($array, $comparator)
{
    $unique_array = [];
    do {
        $element = array_shift($array);
        $unique_array[] = $element;

        $array = array_udiff($array, [$element], $comparator);
    } while (count($array) > 0);

    return $unique_array;
}
