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
    static function parseResponse(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), $assoc = true);
    }

    /**
     * @param ResponseInterface[] $responses
     * @return array
     */
    static function parseResponses(array $responses) {
        $data = [];
        foreach ($responses as $response) {
            $d = Util::parseResponse($response);
            $data[] = $d;
        }
        return $data;
    }

}
