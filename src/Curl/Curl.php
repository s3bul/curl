<?php

declare(strict_types=1);

namespace S3bul\Curl;

use CurlFile;
use CurlHandle;
use InvalidArgumentException;

/**
 * Class Curl
 *
 * @author Sebastian Korzeniecki <seba5zer@gmail.com>
 * @package S3bul\Curl
 */
class Curl
{
    /**
     * @var CurlHandle|null
     */
    private ?CurlHandle $client = null;

    /**
     * @var string|null
     */
    private ?string $url = null;

    /**
     * @var string[]
     */
    private array $cookies = [];

    /**
     * @var string[]
     */
    private array $headers = [];

    /**
     * @var array
     */
    private array $options = [];

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
     * @param string|null $url
     * @return $this
     */
    public function init(string $url = null, array $options = []): self {
        $this->client = curl_init($url ?? $this->url);
        curl_setopt_array($this->client, array_merge($this->getOptions(), $options));

        return $this;
    }

    /**
     * @return $this
     */
    public function reset(): self {
        $this->client = null;
        $this->url = null;
        $this->cookies = [];
        $this->headers = [];
        $this->options = [];
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkClient(): void
    {
        if(is_null($this->client)) {
            throw new InvalidArgumentException('Curl: First call "init" method');
        }
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param string[] $cookies
     * @return $this
     */
    public function setCookies(array $cookies): self
    {
        $this->cookies = [];
        return $this->addCookies($cookies);
    }

    /**
     * @param string[] $cookies
     * @return $this
     */
    public function addCookies(array $cookies): self
    {
        foreach($cookies as $cookie => $value) {
            $this->addCookie($cookie, $value);
        }
        return $this;
    }

    /**
     * @param string $cookie
     * @param string $value
     * @return $this
     */
    public function addCookie(string $cookie, string $value): self
    {
        $this->cookies[$cookie] = $value;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string[] $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = [];
        return $this->addHeaders($headers);
    }

    /**
     * @param string[] $headers
     * @return $this
     */
    public function addHeaders(array $headers): self
    {
        foreach($headers as $header => $value) {
            $this->addHeader($header, $value);
        }
        return $this;
    }

    /**
     * @param string $header
     * @param string $value
     * @return $this
     */
    public function addHeader(string $header, string $value): self
    {
        $this->headers[$header] = "$header: $value";
        return $this;
    }

    public function setCurlHeaders(array $headers): self {

    }

    public function setCurlHeader(string $header, string $value): self {
        $this->addHeader($header, $value);

    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        $custom = [];
        if(count($this->cookies) > 0) {
            $custom[CURLOPT_COOKIE] = http_build_query($this->cookies, '', '; ');
        }
        if(count($this->headers) > 0) {
            $custom[CURLOPT_HTTPHEADER] = array_values($this->headers);
        }
        return array_merge($this->options, $custom);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = [];
        return $this->addOptions($options);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function addOptions(array $options): self
    {
        foreach($options as $option => $value) {
            $this->addOption($option, $value);
        }
        return $this;
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function addOption(int $option, mixed $value): self
    {
        $this->options[$option] = $value;
        return $this;
    }

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
     * @param object|array $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @param int|null $encType
     * @return string
     */
    private function httpBuildQuery(object|array $data, string $numericPrefix = null, string $argSeparator = null, int $encType = null): string
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
        $this->headers = [];
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->headers));
        return $this;
    }

    /**
     * @param string[] $keys
     * @return $this
     */
    public function removeHeaders(array $keys): self
    {
        array_map(function(string $key) {
            unset($this->headers[$key]);
        }, $keys);
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->headers));
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeHeader(string $key): self
    {
        unset($this->headers[$key]);
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->headers));
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

        $this->headers[$key] = $key . ': ' . $value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->headers));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCookie($key, $value): self
    {
        $this->cookies[$key] = $value;
        $this->setOpt(CURLOPT_COOKIE, $this->httpBuildQuery($this->cookies, '', '; '));
        return $this;
    }

}
