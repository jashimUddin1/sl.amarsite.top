<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <title>School Invoice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Tailwind -->

    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- html2canvas (invoice preview image download) -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

    <style>
        body {
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .base-color{
            color: #22c55e;
        }
        .base-bg{
            background-color: #22c55e;
        }
        .base-p{
            padding: 9px 14px;
        }
        /* ===== Invoice page CSS merged (safe, only classes used in invoice page) ===== */
        .invoice-wrapper {
            max-width: 900px;
            margin: 5px auto;
        }

        .btn-reset {
            background-color: #f97373;
            color: #fff;
        }

        .btn-reset:hover {
            background-color: #f05252;
            color: #fff;
        }

        .invoice_left_heading div{
            font-size: x-small;
        }

        .invoice_right_heading {
            font-size: x-small;
        }

        .btn-add-item {
            background-color: #4f46e5;
            color: #fff;
        }

        .btn-add-item:hover {
            background-color: #4338ca;
            color: #fff;
        }

        .btn-footer-add {
            background-color: #16a34a;
            color: #fff;
        }

        .btn-footer-add:hover {
            background-color: #15803d;
            color: #fff;
        }

        .btn-footer-print {
            background-color: #2563eb;
            color: #fff;
        }

        .btn-footer-print:hover {
            background-color: #1d4ed8;
            color: #fff;
        }

        .btn-footer-pdf {
            background-color: #ea580c;
            color: #fff;
        }

        .btn-footer-pdf:hover {
            background-color: #c2410c;
            color: #fff;
        }

        .form-section-title {
            font-weight: 600;
            font-size: 1.05rem;
        }

        .invoice-preview-card {
            max-width: 430px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.18);
            padding: 20px 24px;
        }

        .invoice-preview-header-line {
            border-top: 2px solid darkolivegreen;
            margin: 8px 0 12px;
        }

        .invoice-preview-table thead {
            background-color: #22c55e;
            color: #fff;
        }

        .invoice-preview-table,
        .invoice-preview-table th,
        .invoice-preview-table td {
            border: 1px solid #cbd5e1;
        }

        .invoice-preview-table th,
        .invoice-preview-table td {
            padding: 5px 5px;
            font-size: 0.5rem;
        }

        .rem4 {
            font-size: 0.4rem;
        }

        .rem5 {
            font-size: 0.5rem;
        }

        .rem6 {
            font-size: 0.6rem;
        }

        .rem7 {
            font-size: 0.7rem;
        }

        .subtotal_cal{
            font-size: 0.6rem;
        }

        .badge-status-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.75rem;
        }

        .btn-download-preview {
            background-color: #16a34a;
            color: #fff;
            border-radius: 999px;
            padding-left: 1.7rem;
            padding-right: 1.7rem;
        }

        .btn-download-preview:hover {
            background-color: #15803d;
            color: #fff;
        }

        .calculator-style {
            background-color: #4F46E5;
            padding: 6px;
            color: white;
            border-radius: 5px;
        }

        .calculator-style.active {
            background-color: #16a34a;
        }

#bg-img-logo{
  position: relative;
  overflow: hidden;
  background: #fff; /* টেবিল অংশ সাদা থাকুক */
}

/* watermark image only for table area */
#bg-img-logo::before{
  content: "";
  position: absolute;
  inset: 0;

  background-image: url("../assets/logo3.png");
  background-repeat: no-repeat;
  background-position: center;

  background-size: 35% auto;   /* 🔥 width = 10%, height auto */
  opacity: 0.2;

  pointer-events: none;
  z-index: 0;
}


/* table content always above watermark */
#bg-img-logo table{
  position: relative;
  z-index: 1;
}


    </style>
</head>

<body>