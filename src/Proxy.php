<?php
/**
 * Proxy
 */
namespace Graviton\PhpProxy;

use GuzzleHttp\Client;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Uri;

/**
 * place to override some stuff from jenssegers proxy.. until we can create an own library
 *
 * @package Graviton\ProxyBundle\Service
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link    http://swisscom.ch
 */
class Proxy
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * Proxy constructor.
     *
     * @param Client $client client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Prepare the proxy to forward a request instance.
     *
     * @param  ServerRequestInterface $request request
     *
     * @return $this
     */
    public function forward(ServerRequestInterface $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Forward the request to the target url and return the response.
     *
     * @param string $target target
     *
     * @throws LogicException
     * @return ResponseInterface
     */
    public function to($target)
    {
        if (is_null($this->request)) {
            throw new \LogicException('Missing request instance.');
        }

        $target = new Uri($target);

        // Overwrite target scheme and host.
        $uri = $this->request->getUri()
                             ->withScheme($target->getScheme())
                             ->withHost($target->getHost());

        // Check for custom port.
        if ($port = $target->getPort()) {
            $uri = $uri->withPort($port);
        }

        // Check for subdirectory.
        if ($path = $target->getPath()) {
            $uri = $uri->withPath(rtrim($path, '/') . '/' . ltrim($uri->getPath(), '/'));
        }

        if (!empty($this->request->getQueryParams())) {
            // special case for rql
            $queryParams = $this->request->getQueryParams();

            if (count($queryParams) == 1 && empty(array_shift($queryParams))) {
                $queryKeys = array_keys($this->request->getQueryParams());
                $uri = $uri->withQuery($queryKeys[0]);
            } else {
                $uri = $uri->withQuery(http_build_query($this->request->getQueryParams()));
            }
        }

        $request = $this->request->withUri($uri);

        return $this->client->send($request);
    }
}
