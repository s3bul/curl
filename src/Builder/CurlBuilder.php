<?php
declare(strict_types=1);

namespace S3bul\Builder;

use CurlHandle;
use InvalidArgumentException;

/**
 * Class CurlBuilder
 *
 * @author Sebastian Korzeniecki <seba5zer@gmail.com>
 * @package S3bul\Builder
 */
class CurlBuilder
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
    private array $cookies = [];

    /**
     * @var string[]
     */
    private array $headers = [];

    /**
     * @var string[]
     */
    private array $options = [];

    private string|bool|null $response = null;

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
     * @var bool
     */
    private bool $returnTransfer = true;

    /**
     * @param string|null $url
     * @param string[] $options
     * @return $this
     */
    public function produceHandle(string $url = null, array $options = []): self
    {
        $this->handle = curl_init($url ?? $this->url);
        curl_setopt_array($this->handle, array_merge($this->getOptions(), $options));

        return $this;
    }

    /**
     * @return $this
     */
    public function reset(): self
    {
        $this->handle = null;
        $this->url = null;
        $this->cookies = [];
        $this->headers = [];
        $this->options = [];
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
    private function getUrl(): ?string
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
     * @return array
     */
    public function getOptions(): array
    {
        $custom = [];
        if (count($this->cookies) > 0) {
            $custom[CURLOPT_COOKIE] = $this->httpBuildQuery($this->cookies, '', '; ');
        }
        if (count($this->headers) > 0) {
            $custom[CURLOPT_HTTPHEADER] = array_values($this->headers);
        }
        $custom[CURLOPT_RETURNTRANSFER] = $this->returnTransfer;
        return array_merge($this->options, $custom);
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
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return $this
     */
    public function clearHeaders(): self
    {
        $this->headers = [];
        return $this;
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
        $this->headers[$header] = "$header: $value";
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
        $this->clearHeaders();
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
     * @param string $key
     * @return $this
     */
    public function removeCookie(string $key): self
    {
        unset($this->cookies[$key]);
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
     * @return bool
     */
    public function isReturnTransfer(): bool
    {
        return $this->returnTransfer;
    }

    /**
     * @param bool $returnTransfer
     * @return $this
     */
    public function setReturnTransfer(bool $returnTransfer): self
    {
        $this->returnTransfer = $returnTransfer;
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
        return $this->isDisableArrayBracketInQuery() ? preg_replace('/%5B\d*%5D/', '', $result) : $result;
    }

    /**
     * @param object|array $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @return string
     */
    private function buildUrlQuery(object|array $data, string $numericPrefix = null, string $argSeparator = null): string
    {
        return $this->httpBuildQuery($data, $numericPrefix, $argSeparator, $this->getUrlEncType());
    }

    /**
     * @param object|array $data
     * @param string|null $numericPrefix
     * @param string|null $argSeparator
     * @return string
     */
    private function buildPostFieldsQuery(object|array $data, string $numericPrefix = null, string $argSeparator = null): string
    {
        return $this->httpBuildQuery($data, $numericPrefix, $argSeparator, $this->getPostFieldsEncType());
    }

    /**
     * @return void
     */
    private function checkClient(): void
    {
        if (is_null($this->handle)) {
            throw new InvalidArgumentException('Curl: First call "produceHandle" method');
        }
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
     * @param string $request
     * @param bool $payload
     * @param bool $json
     * @return $this
     */
    private function callCurl(string $request, bool $payload = false, bool $json = true): self
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
     * @param array $data
     * @return $this
     */
    public function get(array $data = []): self
    {
        return $this->callCurl(self::GET);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function post(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::POST, true, $json);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function put(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::PUT, true, $json);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function patch(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::PATCH, true, $json);
    }

    /**
     * @param array|object|string $data
     * @param bool $json
     * @return $this
     */
    public function delete(array|object|string $data = [], bool $json = true): self
    {
        return $this->callCurl(self::DELETE, true, $json);
    }

}
