<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\TokensModel;
use Config\Services;
use DateTime;
use DateTimeZone;


class AuthFilter implements FilterInterface
{

    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');
        $response = service('response');
        $db = \Config\Database::connect();
        $tokens_table = $db->table('tokens');

        $token = null;

        // return $token;
        $key = Services::getSecretKey();

        if (!empty($authorizationHeader)) {
            $headerParts = explode(' ', $authorizationHeader);
            if (count($headerParts) === 2 && $headerParts[0] === 'Bearer') {
                $token = $headerParts[1];
            }
        }

        if (!$token) {
            return $this->fail('<span style="color:red">Authorization header is missing or invalid</span>');
        }

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // print_r($decoded);
            // return;
        } catch (\Exception $e) {
            return $this->fail('<span style="color:red">Invalid token</span>');
        }

        $tokensModel = new TokensModel();

        // $tokenEntity = $tokensModel->find($decoded->data->user_id); // Check for the user Id
        // $tokenEntity = $tokensModel->where('admin_in', $decoded->data->user_id)->find(); // Check for the user Id
        // $tokenEntity = $tokensModel->find(); // Check for the user Id

        $tokenEntity = $tokens_table->where('admin_id', $decoded->data->user_id)->get()->getRow();

        if (!$tokenEntity) {
            return $this->fail('<span style="color:red">Token not found</span>');
        }

        if ($decoded->exp < time()) {
            return $this->fail('<span style="color:red">Token has expired</span>');
        }

        $issuedAtTime = time();
        $tokenExpiration = $issuedAtTime + (2 * 60);

        $payload = [
            'iat' => $issuedAtTime,
            'exp' => $tokenExpiration,
            'data' => [
                'user_email' => $decoded->data->user_email,
                'user_id' => $decoded->data->user_id
            ]
        ];

        $dateTime = new DateTime('@' . $tokenExpiration);
        $dateTime->setTimezone(new DateTimeZone('Africa/Lagos'));
        $formattedDateTime = $dateTime->format('Y-m-d H:i:s');


        $newJwt = JWT::encode($payload, $key,'HS256');

        // print_r($newJwt);
        // return;

        // $tokensModel->updateToken($tokenEntity->token_id, $newJwt, $formattedDateTime);

        $tokens_table->set('token', $newJwt);
        $tokens_table->set('expires_at', $formattedDateTime);
        $tokens_table->where('admin_id', $decoded->data->user_id);
        $tokens_table->update();

        // Send the updated jwt through the heder to client in the response stream
        $response->setHeader('Authorization Bearer', $newJwt);

        return true;
        // return $response;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }

    protected function fail($message)
    {
        $response = service('response');
        $response->setStatusCode(401);
        $response->setJSON(['error' => $message]);
        return $response;
    }
}
