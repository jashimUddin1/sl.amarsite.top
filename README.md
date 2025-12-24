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
* automatic invoice generates --> oi school ar under koyta invoice ase tai... --> running 
* schools a oi school sob invoice dekhano -->
* old invoice thik kore connection  kora --> 
* server a upload and test