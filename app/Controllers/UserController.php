<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\UserMetaModel;
use App\Models\BorrowerRegisterModel;
use App\Models\MetaBorrowerRegisterModel;
use SebastianBergmann\Type\FalseType;
use CodeIgniter\API\ResponseTrait;

class UserController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        return 'I am a user controller';
    }

    public function register_user($id=null)
    {   
        $request = \Config\Services::request();

        $user_model = new UserModel();
        $user_meta_model = new UserMetaModel();
        

        if($request->is('post')) {
            // Process form input

            $rules = [
                'name' =>  'required|min_length[5]|max_length[50]',
                'date_of_birth' => 'required|valid_date',
                'phone' => 'required|min_length[11]|max_length[11]|is_unique[user_meta.phone]',
                'email' => 'required|min_length[5]|max_length[30]|valid_email|is_unique[user_meta.email]',
                
                'image' => [
                    'uploaded[image]', // This checks the input name
                    'mime_in[image,image/jpg,image/jpeg,image/png]',
                    'max_size[image,5000]',
                ],
                'address' => 'required|min_length[5]',
                'gender' => 'required'
            ];

            if(!$this->validate($rules)) {
                // Validate data here
                $response = [
                    'status' => 400,
                    'message' => $this->validator->getErrors(),
                    'error' => true,
                    'data' => []
                ];

            } else {
                // If data validated successfully, process

                // First process image
                $file = $request->getFile('image'); // Retrieve uploaded image by its name
                 
                // $file ->move('assets/images', $name); // Move the file to a new location

                if($file->isValid() && !$file->hasMoved()) {
                    $newName = $file->getRandomName(); // generates a secure random name
                    $file->move('assets/images', $newName);

                    $primaryData = [
                        'name' => $request->getVar('name'),
                        'photo' => 'assets/images' . $newName
                    ];

                    if($user_model->insert($primaryData)) {

                        // Get the id of the last insert. This will be foreign key in the meta table
                        $pk = $user_model->getInsertID();

                        if($pk) {
                            $secondaryData = [
                                'libID' => $pk,
                                'address' => $request->getVar('address'),
                                'gender' => $request->getVar('gender'),
                                'phone' => $request-> getVar('phone'),
                                'email' => $request->getVar('email'),
                                'date_of_birth' => $request->getVar('date_of_birth')
                            ];

                            $user_meta_model->insert($secondaryData);

                            $response = [
                                'status' => 200,
                                'message' => 'You have successfully create this member',
                                'error' => false,
                                'data' => [],
                            ];
                        }
                    } else {
                        // Failed to insert data in DB
                        // Return error and ask user to try again
                        $response = [
                            'status' => 400,
                            'message' => 'This operation was not successful. Please try again',
                            'error' => true,
                            'data' => []
                        ];
                    }
                } else {
                    // Return file upload fail error
                    return 'Photo not good';

                    throw new \RuntimeException($file->getErrorString() . '(' . $file->getError() . ')');

                }
            }

           return $this->respondCreated($response);
            // return 'This is a post request';
        } 


        /*******
         * 
         * This function below will serve as a enpoint function for member validation
         * Also fetching all of member details from the user table and user_meta table 
         * for the member area
         * 
         ******/
        if($request->is('get')) {
            // Get user data from db using id

            // $request = \Config\Services::request();

            $user_model = new UserModel();
            $borrower_register = new BorrowerRegisterModel();
            $meta_borrower_register = new MetaBorrowerRegisterModel();
            $db = \Config\Database::connect();
            // $user_meta_model = new UserMetaModel();

            if(!$user_model->find($id)) {
                $response = [
                    'status' => 404,
                    'message' => 'This id does not exist',
                    'error' => true,
                    'data' => []
                ];
            } else {
                
                // Read: https://onlinewebtutorblog.com/codeigniter-4-how-to-work-with-mysql-joins-tutorial/
                $builder = $db -> table('user');
                $builder->select('*');
                $builder->where('user.libID', $id);
                $builder->join('user_meta', 'user_meta.libID = user.libID', 'left');
                
                $query = $builder->get()->getResult();

                $response = [
                    'status' => 200,
                    'message' => 'User found',
                    'error' => false,
                    'data' => $query
                ];
            }

            // print_r($query);

            return $this->respondCreated($response);
            // return 'THis is a get request';
        }

    }

    public function retrieveUserBorrowedBooks($libID) {
        // echo "I am the retriever guy!!";

        $db = \Config\Database::connect();

        $builder = $db->table('borrower_register');
        $builder->select('*');
        $builder->join('meta_borrower_register', 'borrower_register.borrower_register_id = meta_borrower_register.borrower_register_id');
        $builder->where('borrower_register.libID', $libID);
        $builder->where('borrower_register.isReturned', false);
        $result = $builder->get()->getResult();

        if(!count($result)) {
            // echo "Nothing was found";
            $response = [
                'status' => 200,
                'message' => 'This user does not have any borrowed books',
                'error' => false,
                'data' => []
            ];

        } else {
            // print_r($result);
            $response = [
                'status' => 200,
                'message' => 'Found',
                'error' => false,
                'data' => $result
            ];
            // return $this->respond(json_encode($result), 200);
        }

        return $this->respondCreated($response);
    }
}
