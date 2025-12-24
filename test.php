amar database a dui type ar json save ase ==> 

{"clientInstitution": "test8", "clientName": "Abm Abdul Aziz", "clientPhone": "2342", "invoiceNumber": "11", "invoiceDate": "2025-12-07", "paymentStatus": "PAID", "invoiceNote": "Three Hundred Forty Five Taka Only. Taka Only.", "invoiceName": "test8", "invoiceStyle": "classic", "items": "[{\"description\":\"es\",\"quantity\":\"15\",\"amount\":23,\"total\":345,\"calculated\":\"true\"}]", "updatedAt": "2025-12-08T17:59:29.302Z"} ==>

{"invoiceNumber":11,"invoiceDate":"2025-12-20","invoiceStyle":"classic","billTo":{"school":"আল-আবরার নুরানি ক্যাডেট মাদরাসা এন্ড স্কুল","name":"","phone":"01712798772"},"items":[{"desc":"","qty_raw":"1","qty":1,"rate":0,"amount":0}],"totals":{"total":0,"pay":0,"due":0,"status":"UNPAID"},"note":"Zero Taka Only."}

ai onujaye sob thik kore jate sob kaj kore 

akhon amar ai file ta dynamic kore daw
invoices table => 
id
school_id 
data ==> akhone json data ase ...--> {"invoiceNumber":12,"invoiceDate":"2025-12-20","invoiceStyle":"classic","billTo":{"school":"আল-আবরার নুরানি ক্যাডেট মাদরাসা এন্ড স্কুল","name":"","phone":"01712798772"},"items":[{"desc":"","qty_raw":"1","qty":1,"rate":0,"amount":0}],"totals":{"total":0,"pay":0,"due":0,"status":"UNPAID"},"note":"Zero Taka Only."}
created_at
updated_at



amar ai file ta aktu poriborton chassi ... 
amar bortoman system hosse school_id diye oi school ar data chole ase then notun invoice create hoy. controllers/invoice_save_school.php diye 

akhon chassi peramiter hisabe invoice_id jabe . je id jabe sei id jodi database a thake tobe fetch kore aina data dekhabe then chnage korle save change button ar maddhome controllers/invoice_update.php diye update hobe . ar jodi oi peramiter ar  invoice_id database a na thake tobe echo korbe "ai invoice id database a nai . id thik kore din othoba new invoice create korun." kaj ta kore daw please sathe controllers/invoice_update.php file baniye dio