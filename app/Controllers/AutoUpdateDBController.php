<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BorrowerRegisterModel;
use App\Models\MetaBorrowerRegisterModel;

// https://www.glennstovall.com/writing-cron-job-in-codeigniter/
// https://crontab-generator.org/
// https://www.easycron.com/

class AutoUpdateDBController extends BaseController
{
    public function index()
    {
        //
        echo "I am the auto update controller";
    }

    function auto_update_days_fee() {

        $borrower_reg = new BorrowerRegisterModel();
        $meta_borrower_reg = new MetaBorrowerRegisterModel();

        $db = \Config\Database::connect();
        $builder = $db->table('borrower_register'); // Thisis the primary table
        $meta_builder = $db->table('meta_borrower_register'); //This is the secondary table
        $builder->select('*');
        $builder->join('meta_borrower_register', 'borrower_register.borrower_register_id = meta_borrower_register.borrower_register_id');
        $builder->where('isReturned', false);
        $result = $builder->get()->getResult();

        // print_r($result);
        $max_borrowed_days = 1;
        $late_fee_charge = 10;

        foreach($result as $book) {
            // print_r($book->issuedDate);
            // $issued_date = $book->issuedDate;
            // $return_date = $book->returnDate;

            //Calculate number of days borrowed and over due days
            $num_of_days_borrowed = floor((time() - strtotime($book->issuedDate)) / (60 * 60 * 24));
            echo $num_of_days_borrowed;
            $num_days_overdue = max($num_of_days_borrowed-$max_borrowed_days, 0);
            // echo $num_days_overdue;

            //Update the num of days borrowed and overdue days columns;
            $meta_builder->set('num_days_borrowed', $num_of_days_borrowed);
            $meta_builder->set('days_overdue', $num_days_overdue);
            $meta_builder->set('late_fees', $num_days_overdue*$late_fee_charge);
            $meta_builder->set('isDue', $num_of_days_borrowed >= $max_borrowed_days);
            $meta_builder->where('borrower_register_id', $book->borrower_register_id);
            $meta_builder->update();

        }
        echo "Update successfully!";
    }


}
