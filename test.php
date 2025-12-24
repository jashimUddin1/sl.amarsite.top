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




