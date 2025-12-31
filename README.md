# sl.amarsite.top version-1.05

# income and cost statement add
# create table =>
* 	CREATE TABLE `accounts` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `description` varchar(255) NOT NULL,
 `method` varchar(91) NOT NULL,
 `amount` decimal(10,0) NOT NULL,
 `category` varchar(255) NOT NULL,
 `type` varchar(91) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE accounts_trash (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    del_acc_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,

    date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    method VARCHAR(20) NOT NULL,
    amount DECIMAL(12,0) NOT NULL,
    category VARCHAR(91) NOT NULL,
    type ENUM('income','expense') NOT NULL,

    deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



* accounts/index.php a start balance set korar system korte hobe

# Remove duplicate file # done
* layout a invoice header onk gula relative akta rekhe bakigula delete korte hobe. => done

# invoices/ create_new_invoice.php # pending
* notun akta invoice create korte hobe without fill data 

# invoices/ invoice_delete.php # pending
* delete ar age invoice_trash  a data insert korte hobe.
* delete hole note_logs a data insert 



# problem
* view all logs a button a error ase fixed


# korte hobe
* logs aro akta banate hobe jeno sob support day school, invoice, account everything , akhon ase just school wise.


























# sl.amarsite.top version-1.04

# database change
* ALTER TABLE schools
ADD COLUMN m_fee DECIMAL(10,2) NULL AFTER mobile,
ADD COLUMN y_fee DECIMAL(10,2) NULL AFTER m_fee;


# invoice_school_final.php
* print button ta bad dite hobe

# invoices.php file ta thik korte hobe
* delete ar kaj korte hobe ==> running


# dashboard.php 
* invoice theke income summary aina show korate hobe dashboard a .

# roadmap
* invoice_update for invoice_number column --> done
* school add ,edit ar monthly fee yearly, fee column add kore kaj korte hobe --> done
* automatic invoice generates --> oi school ar under koyta invoice ase tai... --> done
* schools a oi school sob invoice dekhano --> done
* old invoice thik kore connection  kora --> running --> remove
* server a upload and test