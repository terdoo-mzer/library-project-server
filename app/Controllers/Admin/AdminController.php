<?php

namespace App\Controllers\Admin;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AdminModel;
use App\Models\TokensModel;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTime;
use DateTimeZone;

class AdminController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        //
        echo "I am the admin controller";
    }

    public function createAdmin()
    {
        echo "Create Admin";

        helper(['form']);

        $admins = new AdminModel();
      

        $rules = [
            'name'          => 'required|min_length[2]|max_length[50]',
            'email'         => 'required|min_length[4]|max_length[100]|valid_email|is_unique[admins.email]',
            'password'      => 'required|min_length[4]|max_length[50]',
            'confirmpassword'  => 'matches[password]'
        ];

        if (!$this->validate($rules)) {
            $response = [
                'status' => '400',
                'message' => $this->validator->getErrors(),
                'data' => [],
                'error' => true
            ];
        } else {

            $data = [
                'name' => $this->request->getVar('name'),
                'email' => $this->request->getVar('email'),
                'password' =>   password_hash($this->request->getVar('password'), PASSWORD_DEFAULT)
            ];

            $admins->insert($data);
            $response = [
                'status' => '200',
                'message' => 'This Admin User has been created successfuly',
                'data' => [],
                'error' => false
            ];
        }

        return $this->respondCreated($response);
    }

    public function login()
    {
        helper(['form']);
        // echo "Login";
        $admins = new AdminModel();
        $token_model = new TokensModel();
        $db = \Config\Database::connect();

        $token_table = $db->table('tokens');
        $key = Services::getSecretKey();

        $rules = [
            'email' => 'required',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            $response = [
                'status' => '400',
                'message' => $this->validator->getErrors(),
                'data' => [],
                'error' => true
            ];
        } else {
            $email =  $this->request->getVar('email');
            $password = $this->request->getVar('password');

            $data = $admins->where('email', $email)->first();

            if ($data) {
                $pass = $data['password'];
                $authenticatePassword = password_verify($password, $pass);

                if ($authenticatePassword) {
                    $issuedAtTime = time();
                    // $tokenTimeToLive = getenv('JWT_TIME_TO_LIVE');
                    $tokenExpiration = $issuedAtTime + (2 * 60);
                    $payload = [
                        'iat' => $issuedAtTime,
                        'exp' => $tokenExpiration,
                        'data' => [
                            'user_email' => $data['email'],
                            'user_id' => $data['id']
                        ]
                    ];


                    $jwt = JWT::encode($payload, $key, 'HS256');

                    $dateTime = new DateTime('@' . $tokenExpiration);
                    $dateTime->setTimezone(new DateTimeZone('Africa/Lagos'));
                    $formattedDateTime = $dateTime->format('Y-m-d H:i:s');

                    // Check to see if a particular id already exists in the tokens db
                    // if it does, then you would uptae the token and expiry on that id
                    // Else do a new insert
                    // This is to prevent multiple id entries

                    
                    if($token_table->where('admin_id', $data['id'])->get()->getRow()) {
                        echo "Update here";
                        $token_model->updateToken($data['id'], $jwt, $formattedDateTime);
                    } else {
                        // Insert Token into the database
                        echo "Create new";
                    $token_model->createToken($data['id'], $jwt, $formattedDateTime);
                    }
                    $response = [
                        'status' => 200,
                        'jwt' => $jwt,
                        'message' => 'User Login Successfully',
                        'error' => false
                    ];
                } else {
                    $response = [
                        'status' => 400,
                        'message' => 'Invalid login details',
                        'data' => [],
                        'error' => true
                    ];
                }
            } else {

                $response = [
                    'status' => 400,
                    'message' => 'This email does not exist',
                    'data' => [],
                    'error' => true
                ];
            }
        }

        return $this->respondCreated($response);
    }

    // public function verifyUser()
    // {
    //     $request = service('request');
    //     $key = Services::getSecretKey();
    //     $headers = $request->getHeader('authorization');
    //     $jwt = $headers->getValue();
    //     $userData = JWT::decode($jwt, new KEY($key, 'HS256'));
    //     $users = $userData->data;
    //     return $this->respond([
    //         'status' => 1,
    //         'users' => $users
    //     ]);
    // }

    public function logout()
    {
        echo "Logout";
    }
}
