<?php

declare(strict_types=1);

namespace S3bul\Curl;

use Curl\Curl as PhpCurl;
use CurlFile;

/**
 * Class Curl
 *
 * @author Sebastian Korzeniecki <seba5zer@gmail.com>
 * @package S3bul\Curl
 */
class Curl extends PhpCurl
{
    /**
     * @var string[]
     */
    private array $_cookies = [];

    /**
     * @var string[]
     */
    private array $_headers = [];

    /**
     * @var int
     * @link https://php.net/manual/en/function.http-build-query.php
     */
    private int $urlEncType = PHP_QUERY_RFC1738;

    /**
     * @var int
     * @link https://php.net/manual/en/function.http-build-query.php
     */
    private int $postFieldsEncType = PHP_QUERY_RFC1738;

    /**
     * @var bool
     */
    private bool $disableArrayBracketInQuery = false;

    /**
     * @return int
     */
    private function getUrlEncType(): int
    {
        return $this->urlEncType;
    }

    /**
     * @param int $urlEncType
     * @return $this
     */
    public function setUrlEncType(int $urlEncType): self
    {
        $this->urlEncType = $urlEncType;
        return $this;
    }

    /**
     * @return int
     */
    private function getPostFieldsEncType(): int
    {
        return $this->postFieldsEncType;
    }

    /**
     * @param int $postFieldsEncType
     * @return $this
     */
    public function setPostFieldsEncType(int $postFieldsEncType): self
    {
        $this->postFieldsEncType = $postFieldsEncType;
        return $this;
    }

    /**
     * @return bool
     * @deprecated
     */
    public function isDisableQueryArrayBracket(): bool
    {
        return $this->isDisableArrayBracketInQuery();
    }

    /**
     * @param bool $disableQueryArrayBracket
     * @return $this
     * @deprecated
     */
    public function setDisableQueryArrayBracket(bool $disableQueryArrayBracket): self
    {
        return $this->setDisableArrayBracketInQuery($disableQueryArrayBracket);
    }

    /**
     * @return bool
     */
    public function isDisableArrayBracketInQuery(): bool
    {
        return $this->disableArrayBracketInQuery;
    }

    /**
     * @param bool $disableArrayBracketInQuery
     * @return $this
     */
    public function setDisableArrayBracketInQuery(bool $disableArrayBracketInQuery): self
    {
        $this->disableArrayBracketInQuery = $disableArrayBracketInQuery;
        return $this;
    }

    /**
     * @param mixed $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @param int|null $encType
     * @return string
     */
    private function httpBuildQuery($data, string $numericPrefix = null, string $argSeparator = null, int $encType = null): string
    {
        $prefix = is_null($numericPrefix) ? '' : $numericPrefix;
        $separator = is_null($argSeparator) ? '&' : $argSeparator;
        $result = http_build_query($data, $prefix, $separator, is_null($encType) ? PHP_QUERY_RFC1738 : $encType);
        return $this->isDisableArrayBracketInQuery() ? preg_replace('/%5B\d*%5D/', '', $result) : $result;
    }

    /**
     * @param $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @return string
     */
    private function buildUrlQuery($data, string $numericPrefix = null, string $argSeparator = null): string
    {
        return $this->httpBuildQuery($data, $numericPrefix, $argSeparator, $this->getUrlEncType());
    }

    /**
     * @param mixed $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @return string
     */
    private function buildPostFieldsQuery($data, string $numericPrefix = null, string $argSeparator = null): string
    {
        return $this->httpBuildQuery($data, $numericPrefix, $argSeparator, $this->getPostFieldsEncType());
    }

    /**
     * @inheritdoc
     */
    protected function preparePayload($data): void
    {
        $this->setOpt(CURLOPT_POST, true);

        if(is_array($data) || is_object($data)) {
            $skip = false;
            foreach($data as $key => $value) {
                // If a value is an instance of CurlFile skip the http_build_query
                // see issue https://github.com/php-mod/curl/issues/46
                // suggestion from: https://stackoverflow.com/a/36603038/4611030
                if($value instanceof CurlFile) {
                    $skip = true;
                }
            }

            if(!$skip) {
                $data = $this->buildPostFieldsQuery($data);
            }
        }

        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @inheritdoc
     */
    public function get($url, $data = []): self
    {
        if(count($data) > 0) {
            $this->setOpt(CURLOPT_URL, $url . '?' . $this->buildUrlQuery($data));
        }
        else {
            $this->setOpt(CURLOPT_URL, $url);
        }
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->exec();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function post($url, $data = array(), $asJson = false): self
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        return parent::post($url, $data, $asJson);
    }

    /**
     * @inheritdoc
     */
    public function put($url, $data = array(), $payload = true): self
    {
        if(!empty($data)) {
            if($payload === false) {
                $url .= '?' . $this->buildUrlQuery($data);
            }
            else {
                $this->preparePayload($data);
            }
        }

        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->exec();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function patch($url, $data = array(), $payload = true): self
    {
        if(!empty($data)) {
            if($payload === false) {
                $url .= '?' . $this->buildUrlQuery($data);
            }
            else {
                $this->preparePayload($data);
            }
        }

        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->exec();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function delete($url, $data = array(), $payload = true): self
    {
        if(!empty($data)) {
            if($payload === false) {
                $url .= '?' . $this->buildUrlQuery($data);
            }
            else {
                $this->preparePayload($data);
            }
        }

        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->exec();
        return $this;
    }

    /**
     * @return $this
     */
    public function clearHeaders(): self
    {
        $this->_headers = [];
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

    /**
     * @param string[] $keys
     * @return $this
     */
    public function removeHeaders(array $keys): self
    {
        array_map(function(string $key) {
            unset($this->_headers[$key]);
        }, $keys);
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeHeader(string $key): self
    {
        unset($this->_headers[$key]);
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHeader($key, $value): self
    {
        if(is_null($value)) {
            return $this->removeHeader($key);
        }

        $this->_headers[$key] = $key . ': ' . $value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCookie($key, $value): self
    {
        $this->_cookies[$key] = $value;
        $this->setOpt(CURLOPT_COOKIE, $this->httpBuildQuery($this->_cookies, '', '; '));
        return $this;
    }

}
