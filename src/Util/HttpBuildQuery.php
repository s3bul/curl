<?php
declare(strict_types=1);

namespace S3bul\Util;

class HttpBuildQuery
{
    public static function build(
        object|array $data,
        string $numericPrefix = null,
        string $argSeparator = null,
        int $encType = null,
        bool $bracketInQuery = null
    ): string
    {
        $result = http_build_query(
            $data,
            is_null($numericPrefix) ? '' : $numericPrefix,
            is_null($argSeparator) ? '&' : $argSeparator,
            is_null($encType) ? PHP_QUERY_RFC1738 : $encType,
        );
        $bracket = is_null($bracketInQuery) ? true : $bracketInQuery;
        return $bracket ? $result : preg_replace('/%5B\d*%5D/', '', $result);
    }

}