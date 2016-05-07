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
     * @return array
     */
    public static function parseResponse(ResponseInterface $response): array
    {
        return json_decode((string)$response->getBody(), $assoc = true);
    }

    /**
     * @param ResponseInterface[] $responses
     * @return array
     */
    public static function parseResponses(array $responses): array
    {
        $data = [];
        foreach ($responses as $response) {
            $d = Util::parseResponse($response);
            $data[] = $d;
        }
        return $data;
    }

    /**
     * @param ResponseInterface $response
     * @param string $dataKey
     * @return mixed
     */
    public static function modelFromResponse(ResponseInterface $response, string $dataKey): array
    {
        $parsed = self::parseResponse($response);
        return $parsed[$dataKey];
    }

    /**
     * @param ResponseInterface[] $responses
     * @param string $dataKey
     * @return array
     */
    public static function modelsFromPaginatedResponse(array $responses, string $dataKey): array
    {
        $data = [];
        foreach ($responses as $res) {
            $data = array_merge($data, static::modelFromResponse($res, $dataKey));
        }
        return $data;
    }

}
