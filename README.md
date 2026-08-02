# 📘 School Note Manager

<a name="English"></a>
🌐 **Language:** English | [বাংলা](#বাংলা)

A powerful and evolving **School Management & Activity Tracking System** designed to manage  
**schools, notes, invoices, accounts, balances, trash, and logs** — all in one centralized platform.

🔗 **Live Project:** https://sl.amarsite.top

---

## 🚀 Latest Release

### 🔖 Version `1.06.03` — 
1. add run.php optimized kora hoice 
2. auto invoice generates thik kora hoice


### 🔖 Version `1.06.02` — 
1. school ar student ar jonno notun cell add kora hoice


### 🔖 Version `1.06.01` — **Color Status Tracking, Color Search & Sticky Table Header**
> This update introduces **color-coded school status tracking**, **color-based table filtering**, **dynamic tooltip status notes**, and **sticky top bar navigation**.

#### ✨ Highlights
- 🎨 **Color-Coded School Status Tracking**
  - Assigned visual color status indicators (`Green`, `Yellow`, `Pink`, `Blue`, `Red`) for each school record
  - Added dynamic hover tooltips over color badges displaying exact status meaning (e.g., Green = Confirmed, Blue = December Follow-up, Red = Declined)
  - Integrated color status selector inside school edit/add forms with automated `selected` binding
- 🎨 **Color Search & Multi-Filter Engine**
  - Enhanced the search bar header with a dedicated **Color Filter Dropdown**
  - Seamlessly filters school records simultaneously by **Search Text (Name, Phone, Location)** AND **Color Status** in real-time
- 📌 **Sticky Header Toolbar & Fixed Table Header**
  - Converted top actions wrapper (Add School button, Search Bar, Color Filter, Notification Bell) into a **sticky top toolbar**
  - Enabled fixed positioning for table headers (`thead`) so columns remain visible while scrolling through long data sets

---

### 🔖 Version `1.06.00` — **Notification Bell, Instant Search & UI Enhancements**
> This update brings a **smart notification center**, **real-time school search**, and **enhanced note visibility**.

#### ✨ Highlights
- 🔔 **Unread Notification Bell & Badge Counter**
  - Displays a real-time badge counter over the bell icon showing pending/due notifications
  - Clicking the bell opens a sleek modal pop-up displaying all unread reminders with meeting schedules
  - Integrated **Mark as Read** functionality to easily clear completed notifications
- 🔍 **Live Real-time Search Bar**
  - Search bar integrated right into the header between "Add School" and the "Notification Bell"
  - Filter schools instantly by **Name, Phone, or Location** without page reloads
- 📝 **Last Note & Website Fee Tracking**
  - Added a dedicated **Last Note** column in the main table with hover tooltip support
  - Added `website_fee` tracking alongside monthly and yearly fees in school records

---

### 🔖 Version `1.05.06` — **Invoice Month & Remaining List Update**
> This update improves **invoice generation flexibility** and enhances the invoice management experience.

#### ✨ Highlights
- 📅 **Invoice Month Selection**
  - Invoice generation now supports a selected month instead of only the current month
  - Makes it possible to generate invoices for previous or upcoming months
- 🧾 **Improved Auto Invoice Logic**
  - Duplicate invoice checking now follows the selected invoice month
  - Invoice date and subscription month are generated consistently
- 📋 **Remaining Invoice List**
  - Clicking the remaining invoice count now opens a popup showing all schools whose invoices are still pending
  - Displays school name and monthly fee for quick verification
- 🎨 **Better User Experience**
  - Easier invoice planning and verification before auto generation
  - Improved workflow for monthly billing management

---

### 🔖 Version `1.05.05` — **Invoice & Logging Optimization Update**

---

### 🔖 Version `1.05.04` — **Invoice Auto Create Off**

---

### 🔖 Version `1.05.03` — **Accounts Dashboard Update**

---

### 🔖 Version `1.05.02` — **Major Update**

---

### 🔖 Version `1.05.01`

---

## 🎨 Color Status Mapping Guide

| Color Code | Meaning / Status Note |
| :--- | :--- |
| 🟩 **Green** | কনফার্ম করেছে / এক্সেপ্টেড (Confirmed / Accepted) |
| 🟨 **Yellow** | কথা চলছে / ফলোআপে আছে (In Progress / Follow-up) |
| 🟦 **Blue** | December-এ নিবে বলছে (Scheduled for December) |
| 🟪 **Pink** | পরবর্তীতে কল দিতে বলছে (Call Later) |
| 🟥 **Red** | নিয়ে দ্বিধাদ্বন্দ্ব / নিবে না (Declined / Uncertain) |

---

## 🧠 Core Features

- 🏫 School management (Create, Update, Delete, Restore)
- 🔔 Notification Bell & Unread Reminder Badges
- 🔍 Live Instant Search (Name, Phone, Location) & Color Filtering
- 🎨 Color-coded status tracking with hover tooltips
- 📌 Sticky Toolbar and Fixed Header Table View
- 📝 Notes & Last Note preview with full activity logs
- 🧾 Invoice management with logging
- 💰 Accounts & balance tracking (Monthly, Yearly & Website Fee)
- 🗑️ Trash system with restore support
- 📊 Action-wise logs & history view
- 🖼️ Image preview modal

---

## 🛠️ Tech Stack

- **Backend:** PHP (PDO)
- **Frontend:** Bootstrap 5, JavaScript (ES6), Bootstrap Icons
- **Database:** MySQL
- **Logging:** Custom activity logging system

---

## 📌 Versioning Strategy

- **Major:** Feature & logic changes  
- **Minor:** UI improvements, validations  
- **Patch:** Bug fixes & optimizations  

---

## 📄 License

This project is currently **private / internal use only**.  
Licensing terms may be updated later.

---

## ✨ Author

Built & maintained with ❤️ by **Developer Jasim (01601610105)**  
for real-world school data management, tracking, and accountability.

---

<a name="বাংলা"></a>

# 📘 স্কুল নোট ম্যানেজার

🌐 **ভাষা:** [English](#English) | **বাংলা**

একটি শক্তিশালী ও আধুনিক **স্কুল ব্যবস্থাপনা ও অ্যাক্টিভিটি ট্র্যাকিং সিস্টেম**,  
যার মাধ্যমে এক জায়গা থেকেই **স্কুল, নোট, ইনভয়েস, হিসাব, ব্যালেন্স, ট্র্যাশ ও লগ** পরিচালনা করা যায়।

🔗 **লাইভ প্রজেক্ট:** https://sl.amarsite.top

---

## 🚀 সর্বশেষ আপডেট

### 🔖 ভার্সন `1.06.01` — **কালার স্ট্যাটাস ট্র্যাকিং, কালার ফিল্টার এবং স্টিকি হেডার ইউআই**
> এই আপডেটে **স্কুলের সাথে আলাপের কালার স্ট্যাটাস ট্র্যাকিং**, **কালার ড্রপডাউন সার্চ ফিল্টার**, **মাউস হভার টুলটিপ স্ট্যাটাস** এবং **স্টিকি টেবিল হেডার** যুক্ত করা হয়েছে।

#### ✨ নতুন কী আছে
- 🎨 **কালার-কোডেড স্কুল স্ট্যাটাস ট্র্যাকিং**
  - প্রতিটি স্কুলের সাথে যোগাযোগের বর্তমান অবস্থা সহজে বোঝাতে কালার কোডিং সিস্টেম (`Green`, `Yellow`, `Pink`, `Blue`, `Red`) যুক্ত করা হয়েছে।
  - কালার বক্সের ওপর মাউস নিলে নির্দিষ্ট স্ট্যাটাস টেক্সট টুলটিপ হিসেবে দেখা যাবে (যেমন: Green = কনফার্ম করেছে, Blue = ডিসেম্বর মাসে নিবে, Red = নিবে না)।
  - স্কুল এডিট এবং অ্যাড ফর্মে কালার ড্রপডাউন যুক্ত করা হয়েছে যা ডাটাবেজ থেকে ডাটা স্বয়ংক্রিয়ভাবে সিলেক্ট করে রাখে।
- 🎨 **কালার সার্চ ও মাল্টি-ফিল্টারিং**
  - হেডারের সার্চ বারের পাশে নতুন **Color Filter Dropdown** যুক্ত করা হয়েছে।
  - একই সাথে টেক্সট (নাম/ফোন/ঠিকানা) এবং কালার কোড মিলিয়ে নিমেষেই ডাটা ফিল্টার করা যায়।
- 📌 **স্টিকি অ্যাকশন বার ও টেবিল হেডার**
  - স্ক্রোল করার সময় ওপরে **Add School, Search Bar, Color Select এবং Bell Icon** সংবলিত বারটি ফিক্সড আটকে থাকবে।
  - টেবিলের ভেতরের ডাটা স্ক্রোল হলেও টেবিল হেডার (`# School Name Phone...`) ওপরে দৃশ্যমান থাকবে।

---

### 🔖 ভার্সন `1.06.00` — **নোটিফিকেশন বেল, ইনস্ট্যান্ট সার্চ এবং ইউআই আপডেট**
> এই আপডেটে **স্মার্ট নোটিফিকেশন সেন্টার**, **লাইভ সার্চ বার** এবং **লাস্ট নোট দেখার সুবিধা** যুক্ত করা হয়েছে।

#### ✨ নতুন কী আছে
- 🔔 **অনরিড নোটিফিকেশন বেল ও ব্যাজ কাউন্টার**
  - বেলের ওপর লাল ব্যাজে কয়টি রিমাইন্ডার বাকি আছে তা লাইভ দেখা যায়
  - বেলে ক্লিক করলে পপআপ মডালে পরবর্তী মিটিংয়ের সময়সহ আনরিড নোটগুলোর তালিকা দেখা যায়
  - কাজ শেষ হলে নোটিফিকেশন সহজেই বাদ দেওয়ার জন্য **Mark as Read** সুবিধা যুক্ত করা হয়েছে
- 🔍 **লাইভ রিয়েল-টাইম সার্চ বার**
  - Add School বাটন এবং Notification Bell-এর মাঝখানে নতুন সার্চ বার যুক্ত করা হয়েছে
  - কোনো পেজ রিলোড ছাড়াই স্কুলের **নাম, ফোন নম্বর বা ঠিকানা** দিয়ে নিমেষেই তথ্য ফিল্টার করা যায়
- 📝 **লাস্ট নোট ও ওয়েবসাইটের ফি ট্র্যাকিং**
  - মূল তালিকায় প্রতিটি স্কুলের সবচেয়ে সাম্প্রতিক নোটটি দেখানোর জন্য **Last Note** কলাম যুক্ত করা হয়েছে
  - মান্থলি এবং ইয়ারলি ফির পাশাপাশি নতুন **Website Fee** কলাম যুক্ত করা হয়েছে

---

### 🔖 ভার্সন `1.05.06` — **ইনভয়েস মাস নির্বাচন ও রিমেইনিং তালিকা আপডেট**

---

### 🔖 ভার্সন `1.05.05` — **ইনভয়েস ও লগিং অপ্টিমাইজেশন আপডেট**

---

### 🔖 ভার্সন `1.05.04` — **ইনভয়েস অটো ক্রিয়েট বন্ধ**

---

### 🔖 ভার্সn `1.05.03` — **অ্যাকাউন্ট ড্যাশবোর্ড আপডেট**

---

### 🔖 ভার্সন `1.05.02` — **মেজর আপডেট**

---

### 🔖 ভার্সন `1.05.01`

---

## 🎨 কালার স্ট্যাটাস গাইড

| কালার কোড | স্ট্যাটাস ও অর্থ |
| :--- | :--- |
| 🟩 **Green** | কনফার্ম করেছে / এক্সেপ্টেড |
| 🟨 **Yellow** | কথা চলছে / ফলোআপে আছে |
| 🟦 **Blue** | December-এ নিবে বলছে |
| 🟪 **Pink** | পরবর্তীতে কল দিতে বলছে |
| 🟥 **Red** | নিয়ে দ্বিধাদ্বন্দ্ব / নিবে না |

---

## 🧠 প্রধান ফিচারসমূহ

- 🏫 স্কুল ব্যবস্থাপনা
- 🔔 নোটিফিকেশন বেল ও অনরিড রিমাইন্ডার কাউন্টার
- 🔍 ইনস্ট্যান্ট লাইভ সার্চ এবং কালার ফিল্টারিং
- 🎨 কালার কোডেড স্ট্যাটাস ও হভার টুলটিপ নোট
- 📌 স্টিকি ড্যাশবোর্ড টুলবার ও টেবিল হেডার
- 📝 নোট এবং লাস্ট নোট প্রিভিউ সুবিধা
- 🧾 ইনভয়েস সিস্টেম
- 💰 হিসাব ও ব্যালেন্স ট্র্যাকিং (মাসিক, বার্ষিক ও ওয়েবসাইট ফি)
- 🗑️ ট্র্যাশ ও রিস্টোর
- 📊 অ্যাকশন ভিত্তিক লগ
- 🖼️ ছবি প্রিভিউ

---

## 🛠️ টেকনোলজি

- **Backend:** PHP (PDO)
- **Frontend:** Bootstrap 5, JavaScript, Bootstrap Icons
- **Database:** MySQL
- **Logging:** Custom logging system

---

## ✨ নির্মাতা

Built & maintained with ❤️ by **Developer Jasim (01601610105)**