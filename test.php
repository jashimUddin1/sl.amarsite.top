d-none d-md-inline ==> desktop/tab mobile = none
d-inline d-md-none ==> only mobile

akta table create korte chai accounts name

id user_id description,	method,	amount, Category, created_at, updated_at






table invoices => 
id
in_no
school_id
data => {"invoiceDate":"2025-12-25","billTo":{"school":"F.K. Technical","client_name":"","mobile":"01718731850"},"items":[{"description":"Monthly Fee","qty":1,"rate":460,"amount":460}],"totals":{"total":460,"pay":0,"due":460,"status":"UNPAID"},"note":""}
created_at
updated_at



akhane ami chassi status = paid hole sei paid invoice gular data dekhbo?

date = last updated_at ,
description = items.description
Method = '', default cash
amount = paid invoice total taka




CheckLIst
|
home ==> 
    notes view all btn => modal ok -> data fetch error -> style problem -> fixed all -> ok
    manage note => note_view.php -> back button -> ok
                => note_view.php -> ad note btn -> ok
                                 -> add note save -> error -> meeting column missing -> fixed => ok 
 
    add note => btn ok , submit ok , insert sucessful
dashboard ==> Latest Schools view all btn -> fixed -> ok
school ==> ok 
all page => simple check ok 


<!-- accounts/index.php add entry marged  -->
<div class="">
    <form action="core/add_core.php" method="post" class="row g-3 align-items-center top-row">
        <input type="hidden" name="action" value="insert_add">

        <!-- Date -->
        <div class="col-6 col-md-2">
            <input type="date" class="form-control" id="date" name="date" required>
        </div>

        <!-- Type -->
        <div class="col-6 col-md-1">
            <select class="form-select" name="type" required>
                <option value="expense">Expense</option>
                <option value="income">Income</option>
            </select>
        </div>

        <!-- Description -->
        <div class="col-12 col-md-4">
            <input type="text" class="form-control" id="desc" name="description" placeholder="Description"
                maxlength="255" required>
        </div>

        <!-- Amount -->
        <div class="col-4 col-md-2">
            <input type="number" class="form-control" id="amount" name="amount" placeholder="Amount" min="0"
                step="0.01" required>
        </div>

        <!-- Payment Method -->
        <div class="col-4 col-md-1">
            <select class="form-select" name="payment_method" required>
                <option value="Cash">Cash</option>
                <option value="bKash">bKash</option>
                <option value="Nagad">Nagad</option>
                <option value="Bank">Bank</option>
                <option value="Card">Card</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Category -->
        <div class="col-4 col-md-1">
            <select class="form-select" name="category" required>
                <option value="" selected disabled>Category</option>
                <option value="buy">buy</option>
                <option value="marketing_cost">Marketing Cost</option>
                <option value="office_supply">Office Supply</option>
                <option value="cost2">cost2</option>
                <option value="Transport">Transport</option>
                <option value="Rent">Rent</option>
                <option value="Utilities">Utilities</option>
                <option value="revenue">Revenue</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Add Button -->
        <div class="col-12 col-md-1 d-grid">
            <button type="submit" class="btn btn-success btn-add">Add</button>
        </div>

    </form>
</div>



ata amar final file.  ami akhane aro kichu add korte chai.

A) app-wrap ar right cornar a akta 3dot button thakbe 
3dot button => desktop/tab = px type and mobile py type.
3dot button a click korle akta setting modal open hobe

modal => 1. insert add toggle (on/off) = ata dara insert ar hidde show hobe
         2. view button toggle hobe mani show/hide
         3. sheet system toggle 

aigula korte chai peramiter chara and session chara . amar users table diye handling korte chai jate pore login korle o oi user ar same obosthay thake.

B) view te click korle oi entry full details ta modal a dekhabe.


ami direct file chai na . amake kothay ki kon line change , add korso bolo ami step by step change korbo