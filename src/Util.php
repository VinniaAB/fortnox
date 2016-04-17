<?php
/**
 * Created by PhpStorm.
 * User: Johan
 * Date: 2016-04-17
 * Time: 21:48
 */

namespace Vinnia\Fortnox;

use Psr\Http\Message\ResponseInterface;

class Util
{
    /**
     * @param ResponseInterface $response
     * @param string $key
     * @return array
     */
    static function parseResponse(ResponseInterface $response, string $key): array
    {
        $json = json_decode((string) $response->getBody(), $assoc = true);
        return $json[$key];
    }

    /**
     * @param ResponseInterface[] $responses
     * @param string $key
     * @return array
     */
    static function parseResponseArray(array $responses, string $key) {
        $data = [];
        foreach ($responses as $response) {
            $d = Util::parseResponse($response, $key);
            $data = array_merge($data, $d);
        }
        return $data;
    }

}
