<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class ReturnbooksController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        //
        echo 'I am the turn cooks guy!';
    }

    public function returnBooks() {
        if($this->request->is('post')) {
            echo "I am the post guy";
        }

     
    }

    public function insert_data() {
        $library_id = $this->input->post('library_id');
        $books = $this->input->post('books');
      
        foreach($books as $book) {
          $data = array(
            'library_id' => $library_id,
            'book_name' => $book
          );
      
          $this->db->insert('table_name', $data);
        }
      }

}
