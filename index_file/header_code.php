<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dynamic Invoice Generator</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- PDF & Image Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
    xintegrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <style>
    body {
      font-family: 'Inter', sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .form-section {
      transition: all 0.3s ease-in-out;
    }

    .render-area {
      position: absolute;
      left: -9999px;
      top: auto;
      width: 210mm;
      /* Fixed A4 width for PDF generation */
      height: 297mm;
      /* Fixed A4 height for PDF generation */
    }

    #view-modal .invoice-container {
      transform-origin: center;
      transition: transform 0.3s ease;
    }

    .loading-animation {
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid #fff;
      border-radius: 50%;
      width: 1.5rem;
      height: 1.5rem;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    @media print {
      @page {
        size: A4;
        margin: 0;
      }

      body {
        margin: 0;
        background: white;
      }

      body>* {
        display: none !important;
      }

      #print-area,
      #print-area * {
        display: block !important;
      }

      #print-area img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }

      .invoice-container {
        box-shadow: none !important;
        margin: 0 !important;
        border-radius: 0 !important;
        border: none !important;
      }
    }
  </style>
</head>