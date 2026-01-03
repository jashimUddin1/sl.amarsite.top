# running work
* 

# problem
* 

# korte hobe
* logs aro akta banate hobe jeno sob support day school, invoice, account everything , akhon ase just school wise.
* started cash ata pore korte hobe. And monthly balance ..
* accounts/index.php a start balance set korar system korte hobe
* invoice ar edit validation aktu low kora hoice simple invoice ar jonno , pore thik korte hobe
* update_core and delete_core ar redirect a problem ase thik korte hobe.
* time zone asia dite hobe
* Balance showing ta upore theke nicher dike ase aita niche theke uporer dike korte hobe

# invoices.php file ta thik korte hobe
* delete ar kaj korte hobe ==> running

# invoices/ invoice_delete.php # pending
* delete ar age invoice_trash  a data insert korte hobe.
* delete hole note_logs a data insert 

# sl.amarsite.top version-1.05.01
* single_invoice_header and layout_header_invoice kono file a use kora hoy nai tai remove kora hoice.  => local change
*       modified:   README.md
        modified:   accounts/core/add_core.php     
        modified:   accounts/core/update_core.php    
        modified:   accounts/index.php
        modified:   layout/layout_header.php
        modified:   layout/layout_header_index.php   
        deleted:    layout/layout_header_invoices.php
        deleted:    layout/single_invoice_header.php 
        modified:   pages/dashboard.php


requirement 02.01.2026
1. ['Buy','Marketing Cost','Office Supply','Repair','Transport','Rent','Utilities','Revenue','Other']; remove all

add ->
bike service cost
Marketing Cost
Office cost
bike oil cost 
nasta pani
paper cost
raja
yasin


2. Sidebar change order # done local &  server
a. dashboard por accounts hobe -> 2 sidebar file changes

# local changes
* accounts/index.php line:218 this month to lifetime  # done local & server
* pages/dashboard.php line:45 this month to lifetime  # done local & server
* accounts/index.php line:701767,1068 category change  # done local & server
* accounts/core/add_core.php line:91 add category validation change  # done local & server
* accounts/core/update_core.php line:110 add category validation change  # done local & server
* 