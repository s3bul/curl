<?php
declare(strict_types=1);

namespace S3bul\Client;

use CurlHandle;
use InvalidArgumentException;

/**
 * Class CurlClient
 *
 * @author Sebastian Korzeniecki <seba5zer@gmail.com>
 * @package S3bul\Client
 */
class CurlClient
{
    /**
     * HTTP request methods
     */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const HEAD = 'HEAD';
    const DELETE = 'DELETE';
    const TRACE = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE = 'MERGE';

    private const DEFAULT_OPTIONS = [
        CURLOPT_RETURNTRANSFER => true,
    ];

    /**
     * @var CurlHandle|null
     */
    private ?CurlHandle $handle = null;

    /**
     * @var string|null
     */
    private ?string $url = null;

    /**
     * @var string[]
     */
    private array $headers = [];

    /**
     * @var string[]
     */
    private array $cookies = [];

    /**
     * @var array
     */
    private array $options = self::DEFAULT_OPTIONS;

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
     * @var string|bool|null
     */
    private string|bool|null $response = null;

    /**
     * @param string|null $url
     * @param array $options
     * @return $this
     */
    public function init(string $url = null, array $options = []): self
    {
        $this->handle = curl_init();
        $this->url = $url ?? $this->url;
        $_options = [CURLOPT_URL => $this->url] + $options + self::DEFAULT_OPTIONS;
        $this->setOptions($_options);
        curl_setopt_array($this->handle, $this->getOptions());

        return $this;
    }

    /**
     * @return $this
     */
    public function reset(): self
    {
        $this->handle = null;
        $this->url = null;
        $this->headers = [];
        $this->cookies = [];
        $this->options = self::DEFAULT_OPTIONS;
        $this->urlEncType = PHP_QUERY_RFC1738;
        $this->postFieldsEncType = PHP_QUERY_RFC1738;
        $this->disableArrayBracketInQuery = false;
        $this->response = null;
        return $this;
    }

    /**
     * @return CurlHandle|null
     */
    public function getHandle(): ?CurlHandle
    {
        return $this->handle;
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
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $header
     * @return string|null
     */
    public function getHeader(string $header): ?string
    {
        return $this->headers[$header] ?? null;
    }

    /**
     * @param string $header
     * @return $this
     */
    public function removeHeader(string $header): self
    {
        unset($this->headers[$header]);
        return $this;
    }

    /**
     * @param string $header
     * @param string|null $value
     * @return $this
     */
    public function addHeader(string $header, ?string $value): self
    {
        if (is_null($value)) {
            return $this->removeHeader($header);
        }
        $this->headers[$header] = $value;
        return $this;
    }

    /**
     * @param string[] $headers
     * @return $this
     */
    public function addHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->addHeader($header, $value);
        }
        return $this;
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
     * @return string[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param string $cookie
     * @return string|null
     */
    public function getCookie(string $cookie): ?string
    {
        return $this->cookies[$cookie] ?? null;
    }

    /**
     * @param string $cookie
     * @return $this
     */
    public function removeCookie(string $cookie): self
    {
        unset($this->cookies[$cookie]);
        return $this;
    }

    /**
     * @param string $cookie
     * @param string|null $value
     * @return $this
     */
    public function addCookie(string $cookie, ?string $value): self
    {
        if (is_null($value)) {
            return $this->removeCookie($cookie);
        }
        $this->cookies[$cookie] = $value;
        return $this;
    }

    /**
     * @param string[] $cookies
     * @return $this
     */
    public function addCookies(array $cookies): self
    {
        foreach ($cookies as $cookie => $value) {
            $this->addCookie($cookie, $value);
        }
        return $this;
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
     * @return array
     */
    public function getOptions(): array
    {
        $custom = [];
        if (count($this->headers) > 0) {
            $custom[CURLOPT_HTTPHEADER] = array_map(
                fn(string $header, string $value): string => "$header: $value",
                array_keys($this->headers),
                array_values($this->headers),
            );
        }
        if (count($this->cookies) > 0) {
            $custom[CURLOPT_COOKIE] = $this->httpBuildQuery($this->cookies, '', '; ');
        }
        return $custom + $this->options;
    }

    /**
     * @param int $option
     * @return mixed
     */
    public function getOption(int $option): mixed
    {
        return $this->options[$option] ?? null;
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
     * @param array $options
     * @return $this
     */
    public function addOptions(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->addOption($option, $value);
        }
        return $this;
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
     * @return int
     */
    public function getUrlEncType(): int
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
    public function getPostFieldsEncType(): int
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
     * @return bool|string|null
     */
    public function getResponse(): bool|string|null
    {
        return $this->response;
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
        return $this->disableArrayBracketInQuery ? preg_replace('/%5B\d*%5D/', '', $result) : $result;
    }

    /**
     * @param object|array $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @return string
     */
    private function buildUrlQuery(object|array $data, string $numericPrefix = null, string $argSeparator = null): string
    {
        return $this->httpBuildQuery($data, $numericPrefix, $argSeparator, $this->urlEncType);
    }

    /**
     * @param object|array $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @return string
     */
    private function buildPostFieldsQuery(object|array $data, string $numericPrefix = null, string $argSeparator = null): string
    {
        return $this->httpBuildQuery($data, $numericPrefix, $argSeparator, $this->postFieldsEncType);
    }

    /**
     * @param int|null $option
     * @return mixed
     */
    public function getCurlInfo(int $option = null): mixed
    {
        return curl_getinfo($this->handle, $option);
    }

    /**
     * @return int
     */
    public function getCurlInfoHttpCode(): int
    {
        return $this->getCurlInfo(CURLINFO_HTTP_CODE);
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    private function setCurlOption(int $option, mixed $value): bool
    {
        $this->addOption($option, $value);
        return curl_setopt($this->handle, $option, $value);
    }

    /**
     * @return void
     */
    private function checkClient(): void
    {
        if (is_null($this->handle)) {
            throw new InvalidArgumentException('Curl: First call "init" method');
        }
    }

    /**
     * @param string $request
     * @param array|object|string $data
     * @param bool $payload
     * @param bool $json
     * @return $this
     */
    private function callCurl(string $request, array|object|string $data = [], bool $payload = false, bool $json = true): self
    {
        $this->checkClient();
        $this->setCurlOption(CURLOPT_CUSTOMREQUEST, $request);
        if (!empty($data)) {
            if ($payload) {
                $this->setCurlOption(CURLOPT_POST, true);
                $this->setCurlOption(CURLOPT_POSTFIELDS, $json ? json_encode($data) : $data);
            } else {
                $this->setCurlOption(CURLOPT_URL, $this->url . '?' . $this->buildUrlQuery($data));
            }
        }

        $this->response = curl_exec($this->handle);

        return $this;
    }

    /**
     * @param array|object|string $data
     * @return $this
     */
    public function get(array|object|string $data = []): self
    {
        return $this->callCurl(self::GET, $data);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function post(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::POST, $data, true, $json);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function put(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::PUT, $data, true, $json);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function patch(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::PATCH, $data, true, $json);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function delete(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::DELETE, $data, true, $json);
    }

}
