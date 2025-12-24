amar database a dui type ar json save ase ==> 

{"clientInstitution": "test8", "clientName": "Abm Abdul Aziz", "clientPhone": "2342", "invoiceNumber": "11", "invoiceDate": "2025-12-07", "paymentStatus": "PAID", "invoiceNote": "Three Hundred Forty Five Taka Only. Taka Only.", "invoiceName": "test8", "invoiceStyle": "classic", "items": "[{\"description\":\"es\",\"quantity\":\"15\",\"amount\":23,\"total\":345,\"calculated\":\"true\"}]", "updatedAt": "2025-12-08T17:59:29.302Z"} ==>

{"invoiceNumber":11,"invoiceDate":"2025-12-20","invoiceStyle":"classic","billTo":{"school":"আল-আবরার নুরানি ক্যাডেট মাদরাসা এন্ড স্কুল","name":"","phone":"01712798772"},"items":[{"desc":"","qty_raw":"1","qty":1,"rate":0,"amount":0}],"totals":{"total":0,"pay":0,"due":0,"status":"UNPAID"},"note":"Zero Taka Only."}

ai onujaye sob thik kore jate sob kaj kore 

akhon amar ai file ta dynamic kore daw
invoices table => 
id
school_id 
data ==> akhone json data ase ...--> {"invoiceNumber":2,"invoiceDate":"2025-12-24","invoiceStyle":"classic","billTo":{"school":"Mulghor High School","name":"","phone":""},"items":[{"desc":"new in","qty_raw":"32","qty":32,"rate":34,"amount":1088},{"desc":"now","qty_raw":"2","qty":2,"rate":25,"amount":50}],"totals":{"total":1138,"pay":1138,"due":0,"status":"PAID"},"note":"One Thousand Eighty Eight Taka Only."}
created_at
updated_at



amar schools table ==> 
id
district
upazila
school_name
client_name
mobile
m_fee
y_fee
status
photo_path
created_by
updated_by
created_at
updated_at

invoices table ==>
id
in_no = mane invoice number
school_id
data
created_at
updated_at

ami aber o amar table duita dilam . 

invoices.php te age aktu kaj korte hobe mani check korte hobe ai monht a invoice create hoy nai amon kono school ase ki na . jodi sob school ar ai month a invoice thakbe (tumi invoices table theke check korba) tobe button ta secondary hobe ar jodi create school ba sob school ar invoice invoices table khuje na pawya jay (ai month ar) tobe btn ta success hobe. --

jey koyta school baki ase sei koyta school ar data niye invoices table a invoice create korbe
amar table onujaye invoice_auto_generate.php ta full  update kore daw