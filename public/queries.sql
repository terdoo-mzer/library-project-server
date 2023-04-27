
/*Create Borrower Register Table*/
CREATE TABLE borrower_register (
	id INT NOT NULL AUTO_INCREMENT,
    book_reference VARCHAR(255) UNIQUE NOT NULL,
    shelf_id INT NOT NULL,
    borrower_name VARCHAR(255) NOT NULL,
    libID INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    issuedDate DATE NOT NULL,
    returnDate DATE NOT NULL,
    isReturned BOOLEAN,
    PRIMARY KEY (id),
    FOREIGN KEY (libID) REFERENCES user(libID)
)

/*Use the query to alter the id column*/
ALTER TABLE `borrower_register` CHANGE `id` `register_id` INT(13) NOT NULL AUTO_INCREMENT; 
ALTER TABLE `borrower_register` DROP PRIMARY KEY, ADD PRIMARY KEY(`register_id`); 

/*CREATE meta borrower register table*/
CREATE TABLE meta_borrower_register (
    meta_reg_id INT NOT NULL AUTO_INCREMENT,
	register_id INT NOT NULL,
    num_days_borrowed INT,
    late_fees INT,
    days_overdue INT ,
    isDue BOOLEAN,
    PRIMARY KEY (meta_reg_id),
    FOREIGN KEY (register_id) REFERENCES borrower_register(register_id)
);

/*Insert Book into borrower's register*/
INSERT INTO `borrower_register` (`register_id`, `book_reference`, `book_shelf_id`, `borrower_name`, `libID`, `book_title`, `issuedDate`, `returnDate`, `isReturned`) VALUES (NULL, '$123#&abcdA3', '1', 'Samuel Mzer', '3', 'Things Fall Apart', DATE('2023-04-18'), DATE('2023-05-17'), '0');

/*Insert meta detaisl*/
INSERT INTO `meta_borrower_register` (`meta_reg_id`, `register_id`, `num_days_borrowed`, `late_fees`, `days_overdue`, `isDue`) VALUES (NULL, '1', '0', '0', '0', '0');

/*Create tokens table*/
CREATE TABLE tokens (
  admin_id INT NOT NULL,
  token_id INT NOT NULL AUTO_INCREMENT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (token_id),
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
