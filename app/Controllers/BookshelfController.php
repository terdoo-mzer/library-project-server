<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\BorrowerRegisterModel;
use App\Models\MetaBorrowerRegisterModel;
use App\Models\UserModel;


class BookshelfController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */

    // This endpoint is used for Autocomplete
    public function getBook()
    {
        $db = \Config\Database::connect();

        // check if resource exists
        $builder = $db->table('book_shelf');
        $builder->select('book_shelf_id, book_title, IBSN');
        $builder->orderBy('book_shelf_id', 'ASC');
        $builder->where('isBorrowed', false);
        $query = $builder->get()->getResult();

        if (!count($query)) {
            $response = [
                'status' => 404,
                'message' => 'Unsuccessful',
                'error' => true,
                'data' => []
            ];
        } else {
            $response = [
                'status' => 200,
                'message' => 'Successful',
                'error' => false,
                'data' => $query
            ];
        }

        return $this->respondCreated($response);
    }

    function borrowBook()
    {
        // THis function serves will serve on the enpoint for borrowing
        $db = \Config\Database::connect();
        $bookShelfTable = $db->table('book_shelf'); // QUery Book shelf table

        $user = new UserModel();
        $register = new BorrowerRegisterModel();
        $meta_register = new MetaBorrowerRegisterModel();

        // Check if this is a post request
        if ($this->request->is('post')) {
            $rules = [
                'libID' =>  'required|min_length[1]|max_length[100]',
                // 'book_name' =>  'required|min_length[10]|max_length[100]',
                'books.*.shelf-index' => 'required|integer',
            ];

            // Validate input
            if (!$this->validate($rules)) {
                $response = [
                    'status' => 400,
                    'message' => $this->validator->getErrors(),
                    'error' => true,
                    'data' => []
                ];
            } else {
                $payload =  file_get_contents('php://input'); // Retrieve payload
                $decoded_payload = json_decode($payload); // Decode payload

                // Get user id from input
                $libraryID = $decoded_payload->libID;
                $books_shelf_indexes = $decoded_payload->books; // Not that this will be the dataattribute name in the html form

                // Check if user has due books
                // THis check will ensure a user who has due books is not allowed to borrow
                $builder = $db->table('borrower_register');
                $builder->select('*');
                $builder->join('meta_borrower_register', 'borrower_register.borrower_register_id = meta_borrower_register.borrower_register_id');
                $builder->where('borrower_register.libID', $libraryID);
                $builder->where('meta_borrower_register.isDue', true);
                $result = $builder->get()->getResult();

                if ($result) {
                    // This will run when a user has due books
                    $response = [
                        'status' => 404,
                        'message' => 'This user has outstanding borrowed books and cannot borrow more books',
                        'data' => $result,
                        'error' => true
                    ];
                } else {
                    // User is not defaulting, therefore proceed
                    // TODO
                    // 1. Check if the book exists in the shelf (use the shelf id)
                    // 2. If yes, then grab the details of the book to be sent to the borrowers table
                    // 3. Set the isBorrowed flag to true on the book shelf

                    /*
                     To get the input attribute data instead of the input value,
                     $shelf_id = $this->request->getVar('book_shelf_no');
                     In the code above, pass the data attribute name as the argument to retrive
                     the needed data
                    */

                    // $shelf_id = '';
                    foreach ($books_shelf_indexes as $book_shelf_index) {
                        // print_r($book_shelf_index);
                        $shelf_id =  $book_shelf_index->{"shelf-index"};
                        // echo $shelf_id;

                        // return;
                        $bookShelfTable->where('book_shelf_id', $shelf_id); // This will check if the book with the specific id exists, if it doesn't it will return null
                        // $bookShelfTable->where('isBorrowed', false); // This will check if the book with the specific id exists, and is not borrowed
                        $shelfResult = $bookShelfTable->get()->getResult();
                        if ($shelfResult) {
                            // echo 'hello';
                            /** Get the following details from the book shelf and user table
                             * 1. book_shelf_id
                             * 2. borrower_name
                             * 3. libID
                             * 4. book_title
                             */

                            //   print_r($shelfResult);
                            $book_shelf_id = $shelfResult[0]->book_shelf_id;
                            $book_title = $shelfResult[0]->book_title;
                            //   echo $book_shelf_id;
                            //   echo $book_title;
                            //   return;

                            $userQuery = $user->where('libID', $libraryID)->first();
                            $borrower_name = $userQuery['name'];
                            //   echo $borrower_name;
                            //   print_r($userQuery);

                            // Generate random book reference for each book
                            $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ$#@"), 0, 12);
                            // Generate the issued and return dates
                            // $max_borrow_days = getenv('max_borrow_days');
                            $max_borrow_days = 1;
                            $issued_date = date('Y-m-d'); // Current date
                            $return_date = date('Y-m-d', strtotime('+' . $max_borrow_days . ' days')); // Date max_borrow_days days from now

                            echo "Random String " . $max_borrow_days . "\n";
                            echo "Issued date: " . $issued_date . "\n";
                            echo "Return date: " . $return_date . "\n";

                            $data = [
                                'book_reference' => $randomString,
                                'book_shelf_id' => $book_shelf_id,
                                'borrower_name' => $borrower_name,
                                'libID' => $libraryID,
                                'book_title' => $book_title,
                                'issuedDate' => $issued_date,
                                'returnDate' => $return_date,
                                'isReturned' => false
                            ];

                            $register->insert($data);
                            $insertID = $register->getInsertID();

                            if ($insertID) {
                                $data2 = [
                                    'borrower_register_id' => $insertID,
                                    'num_days_borrowed' => 0,
                                    'late_fees' => 0,
                                    'days_overdue' => 0,
                                    'isDue' => 0

                                ];
                                $meta_register->insert($data2);
                                $bookShelfTable->where('book_shelf_id', $shelf_id);
                                $bookShelfTable->update(['isBorrowed' => true]);
                                $response = [
                                    'status' => 200,
                                    'message' => "Book successfully borrowed",
                                    'data' => [],
                                    'error' => false
                                ];
                            } else {
                                $response = [
                                    'status' => 500,
                                    'message' => "Book borrowing failed. Try again later",
                                    'data' => [],
                                    'error' => true
                                ];
                            }
                        } else {
                            $response = [
                                'status' => 404,
                                'message' => 'The requested book does not exist',
                                'data' => '',
                                'error' => true
                            ];
                        }
                    }
                }
            }
        }

        return $this->respondCreated($response);
    }

    public function returnBooks()
    {
        // echo "Return books here. ";
        $db = \Config\Database::connect();


        $meta_borrower_table = $db->table('meta_borrower_register');

        // $payload = file_get_contents('php://input');
        // $decode_payload = json_decode($payload);

        // echo $decode_payload->booksToReturn[0]->bookReference;
        // echo $decode_payload->booksToReturn[0]->{'shelf-index'};

        // return;

        if ($this->request->is('post')) {
            $rules = [
                'booksToReturn.*.bookReference' =>  'required', // 
                'booksToReturn.*.shelf-index' =>  'required|integer',
            ];

            if (!$this->validate($rules)) {
                //    echo "Error";
                $response = [
                    'status' => 400,
                    'message' => $this->validator->getErrors(),
                    'error' => true,
                    'data' => []
                ];
                // return "Not valid";
            } else {
                $borrower_table = $db->table('borrower_register');

                // echo "Yayyy";
                $payload = file_get_contents('php://input');
                $books = json_decode($payload);

                foreach ($books->booksToReturn as $book) {
                    $book_ref = $book->bookReference;
                    $book_index = $book->{'shelf-index'};
                    $borrower_table->where('book_reference', $book_ref);
                    $result = $borrower_table->get()->getResult();

                    // print_r($result);
                    // return;

                    if ($result) {

                        // Update Borrower and meta Borrower Table
                        $borrower_table->set('isReturned', true);
                        $meta_borrower_table->set('isDue', false);

                        $borrower_table->join('meta_borrower_register', 'borrower_register.borrower_register_id = meta_borrower_register.borrower_register_id');
                        $borrower_table->where('borrower_register.book_reference', $book_ref);

                        $borrower_table->update();
                        // Update the meta_borrower_table
                        $meta_borrower_table->where('borrower_register_id IN (SELECT borrower_register_id FROM borrower_register WHERE book_reference = "' . $book_ref . '")');
                        $meta_borrower_table->update();


                        // Update Book Shelf
                        $book_shelf = $db->table('book_shelf');
                        $book_shelf->where('book_shelf_id', $book_index);
                        $book_shelf->set('isBorrowed', false);
                        $book_shelf->update();

                        $response = [
                            'status' => 200,
                            'message' => 'Submitted successfully!',
                            'error' => false,
                            'data' => []
                        ];
                    } else {
                        $response = [
                            'status' => 400,
                            'message' => 'The reference does not exist or submitting failed. Please check and try again',
                            'error' => true,
                            'data' => []
                        ];
                    }
                }
            }
        }
        return $this->respondCreated($response);
    }
}
