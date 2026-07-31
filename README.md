# 📘 School Note Manager

<a name="English"></a>
🌐 **Language:** English | [বাংলা](#বাংলা)

A powerful and evolving **School Management & Activity Tracking System** designed to manage  
**schools, notes, invoices, accounts, balances, trash, and logs** — all in one centralized platform.

🔗 **Live Project:** https://sl.amarsite.top

---

## 🚀 Latest Release

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
> This update focuses on **invoice payment handling, logging accuracy, database optimization, and system flexibility**.

#### ✨ Highlights
- 💳 **Invoice Paid Status Enhancement**
  - Invoice payment now properly tracks payment time
  - New `paid_at` column added for accurate payment history
- 🧾 **Invoice Update Logging Fixed**
  - When an invoice is updated, **old data vs new data** now shows correctly in `all_logs`
  - Improves audit trail reliability
- 📊 **Logging System Optimization**
  - `all_logs` table optimized for cleaner, more readable log entries
  - Logs now display changes in a clearer and more structured format
- 💼 **Accounts Module Flexibility Improved**
  - Better handling of categories and future expansion
- ⚙️ **Invoice System Optimized**
  - Performance and structure improvements
  - More maintainable and scalable logic

---

### 🔖 Version `1.05.04` — **Invoice Auto Create Off**

---

### 🔖 Version `1.05.03` — **Accounts Dashboard Update**
> This update adds **category-wise expense tracking** and a dedicated details page for better monitoring.

#### ✨ Highlights
- 👥 **Category-wise Expense Cards (Raja / Yasin)**
  - Dashboard now shows **Raja** and **Yasin** expenses separately
  - Data is calculated from the `accounts` table (`category` + `amount`)
  - Range filter works correctly across:
    **Today / This Month / This Year / Last Year / Lifetime / Custom**
- 📄 **New Category Expense Details Page**
  - Added a new details page for category-based expense entries:
    - `/pages/category_details.php?category=Raja`
    - `/pages/category_details.php?category=Yasin`
  - Shows expense list + total sum for the selected category
  - Dashboard “View Details” buttons are now linked correctly
- 🎨 **Dashboard UI Fixes**
  - Raja/Yasin cards now show correct colors and labels
  - Entry count now displays **total expense rows** for each category

---

### 🔖 Version `1.05.02` — **Major Update**
> This release focuses on **logging accuracy, activity tracking, UI improvements, and data integrity**.

#### ✨ Highlights
- 🧾 **Invoice Delete Logging**
  - Deleted invoices are now fully logged
  - Visible in **Dashboard → Recent Activity**
- 📊 **Dashboard Improvements**
  - Log view links fixed and fully functional
- 🏫 **School Logs History**
  - History view updated
  - Redirect issues resolved
- 🖼️ **Favicon Added**
  - PNG logo added to browser title bar
  - Source: https://edurlab.com
- 📝 **Complete Logging System**
  - Note update logs
  - School invoice create & delete logs
  - School delete & restore logs
  - ✔️ Logging system is now fully reliable
- ⏱️ **Note Update Time Bug Fixed**
- 🗑️ **Trash System Improved**
  - Session handling fixed
  - Error issues resolved
- 🔍 **Notes Filtering Removed**
  - Simplified browsing experience
- 💰 **Balance Tracking Fixed**
  - Running balance corrected (bottom → top logic)
- 🏷️ **School Name Preserved in Logs**
  - School name remains visible in logs even after deletion  
  *(Effective from this version onward)*
- 🖼️ **Image Preview Enhancement**
  - Click on school photo to view it in large modal
- ♻️ **Smooth Restore Workflow**
  - Restore works seamlessly
  - Restore actions are logged correctly

---

### 🔖 Version `1.05.01`

#### ✨ Improvements
- 💼 **Accounts Module**
  - Category handling updated while adding entries
- 📂 **Sidebar UI**
  - Sidebar order rearranged for better navigation
- 📅 **Account Dashboard**
  - Default time range changed  
    **From:** This Month → **To:** Lifetime
- 🔐 **Category Validation**
  - Hard validation added to prevent invalid data

---

## 🧠 Core Features

- 🏫 School management (Create, Update, Delete, Restore)
- 🔔 Notification Bell & Unread Reminder Badges
- 🔍 Live Instant Search (Name, Phone, Location)
- 📝 Notes & Last Note preview with full activity logs
- 🧾 Invoice management with logging
- 💰 Accounts & balance tracking (Monthly, Yearly & Website Fee)
- 🗑️ Trash system with restore support
- 📊 Action-wise logs & history view
- 🖼️ Image preview modal
- 🔔 Dashboard recent activity feed

---

## 🛠️ Tech Stack

- **Backend:** PHP (PDO)
- **Frontend:** Bootstrap 5, JavaScript
- **Database:** MySQL
- **Logging:** Custom activity logging system
- **UI:** Modal previews, dynamic dashboards

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

Built & maintained with ❤️  
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
> এই আপডেটে **ইনভয়েস জেনারেশন আরও নমনীয়** করা হয়েছে এবং মাসভিত্তিক ইনভয়েস পরিচালনা সহজ করা হয়েছে।

#### ✨ নতুন কী আছে
- 📅 **ইনভয়েস মাস নির্বাচন**
  - শুধু চলতি মাস নয়, এখন নির্দিষ্ট মাস নির্বাচন করে ইনভয়েস তৈরি করা যায়
  - আগের বা পরের মাসের ইনভয়েসও সহজে জেনারেট করা সম্ভব
- 🧾 **অটো ইনভয়েস লজিক উন্নত**
  - ডুপ্লিকেট ইনভয়েস চেক এখন নির্বাচিত মাস অনুযায়ী হয়
  - Invoice Date এবং Subscription Month একই মাস অনুযায়ী সংরক্ষণ করা হয়
- 📋 **রিমেইনিং ইনভয়েস তালিকা**
  - Remaining সংখ্যায় ক্লিক করলে যেসব স্কুলের ইনভয়েস এখনো তৈরি হয়নি তাদের তালিকা পপআপে দেখা যায়
  - স্কুলের নাম ও মাসিক ফি প্রদর্শন করা হয়
- 🎨 **ব্যবহার সহজ হয়েছে**
  - ইনভয়েস তৈরির আগে সহজেই যাচাই করা যায় কোন কোন স্কুল বাকি আছে
  - মাসভিত্তিক বিলিং পরিচালনা আরও সুবিধাজনক হয়েছে

---

### 🔖 ভার্সন `1.05.05` — **ইনভয়েস ও লগিং অপ্টিমাইজেশন আপডেট**
> এই আপডেটে **ইনভয়েস পেমেন্ট, লগিং নির্ভুলতা, ডাটাবেজ অপ্টিমাইজেশন এবং সিস্টেমের নমনীয়তা** উন্নত করা হয়েছে।

#### ✨ নতুন কী আছে
- 💳 **ইনভয়েস পেমেন্ট স্ট্যাটাস উন্নত**
  - ইনভয়েস পরিশোধের সময় এখন সঠিকভাবে সংরক্ষণ করা হয়
  - নির্ভুল পেমেন্ট হিস্ট্রির জন্য নতুন `paid_at` কলাম যুক্ত করা হয়েছে
- 🧾 **ইনভয়েস আপডেট লগ ঠিক করা হয়েছে**
  - ইনভয়েস আপডেট করলে এখন `all_logs`-এ **পুরোনো ডাটা ও নতুন ডাটা** সঠিকভাবে দেখা যায়
  - অডিট ও পরিবর্তনের ইতিহাস আরও নির্ভরযোগ্য হয়েছে
- 📊 **লগিং সিস্টেম অপ্টিমাইজেশন**
  - `all_logs` টেবিল আরও পরিষ্কার ও সহজে বোঝার মতো করা হয়েছে
  - লগে পরিবর্তনগুলো আরও সুন্দর ও গুছানোভাবে প্রদর্শিত হয়
- 💼 **অ্যাকাউন্টস মডিউল আরও নমনীয়**
  - ক্যাটাগরি ব্যবস্থাপনা উন্নত করা হয়েছে
  - ভবিষ্যতে নতুন ফিচার যুক্ত করা আরও সহজ হবে
- ⚙️ **ইনভয়েস সিস্টেম অপ্টিমাইজেশন**
  - পারফরম্যান্স ও কোড স্ট্রাকচার উন্নত করা হয়েছে
  - রক্ষণাবেক্ষণ ও ভবিষ্যৎ উন্নয়ন আরও সহজ হয়েছে

---

### 🔖 ভার্সন `1.05.04` — **ইনভয়েস অটো ক্রিয়েট বন্ধ**

---

### 🔖 ভার্সন `1.05.03` — **অ্যাকাউন্ট ড্যাশবোর্ড আপডেট**
> এই আপডেটে **ক্যাটাগরি অনুযায়ী খরচ ট্র্যাকিং** এবং ডিটেইলস পেজ যোগ করা হয়েছে, যাতে হিসাব আরও সহজ হয়।

#### ✨ নতুন কী আছে
- 👥 **ক্যাটাগরি অনুযায়ী খরচ কার্ড (Raja / Yasin)**
  - ড্যাশবোর্ডে এখন **Raja** এবং **Yasin** এর খরচ আলাদা আলাদা দেখাবে
  - ডাটা নেওয়া হয় `accounts` টেবিল থেকে (`category` + `amount`)
  - টাইম রেঞ্জ ফিল্টার ঠিকভাবে কাজ করে:
    **Today / This Month / This Year / Last Year / Lifetime / Custom**
- 📄 **ক্যাটাগরি ডিটেইলস পেজ যুক্ত**
  - ক্যাটাগরি অনুযায়ী খরচের এন্ট্রি দেখার জন্য নতুন পেজ যোগ করা হয়েছে:
    - `/pages/category_details.php?category=Raja`
    - `/pages/category_details.php?category=Yasin`
  - নির্দিষ্ট ক্যাটাগরির সব এন্ট্রি + মোট খরচ দেখা যায়
  - ড্যাশবোর্ডের “View Details” বাটন এখন ঠিকভাবে লিংক করা
- 🎨 **ড্যাশবোর্ড UI ফিক্স**
  - Raja/Yasin কার্ডের রঙ ও লেবেল ঠিক করা হয়েছে
  - কাউন্ট এখন দেখাবে **কয়টা expense entry হয়েছে**

---

### 🔖 ভার্সন `1.05.02` — **মেজর আপডেট**
> এই আপডেটে মূলত **লগিং সিস্টেম, ড্যাশবোর্ড অ্যাক্টিভিটি, UI ও ডাটা নির্ভরযোগ্যতা** উন্নত করা হয়েছে।

#### ✨ নতুন কী আছে
- 🧾 **ইনভয়েস ডিলিট লগ**
  - ইনভয়েস ডিলিট হলে এখন লগ হয়
  - **Dashboard → Recent Activity** তে দেখা যায়
- 📊 **ড্যাশবোর্ড আপডেট**
  - লগ ভিউ লিংক ঠিক করা হয়েছে
- 🏫 **স্কুল লগ হিস্ট্রি**
  - হিস্ট্রি আপডেট
  - রিডাইরেক্ট সমস্যা সমাধান
- 🖼️ **ফ্যাভিকন যুক্ত**
  - ব্রাউজার টাইটেল বারে PNG আইকন
  - সোর্স: https://edurlab.com
- 📝 **সম্পূর্ণ লগিং সিস্টেম**
  - নোট আপডেট লগ
  - স্কুল ইনভয়েস তৈরি ও ডিলিট লগ
  - স্কুল ডিলিট ও রিস্টোর লগ
- ⏱️ **নোট আপডেট টাইম এরর ঠিক করা হয়েছে**
- 🗑️ **ট্র্যাশ সিস্টেম উন্নত**
  - সেশন সমস্যা ঠিক করা হয়েছে
- 🔍 **নোট ফিল্টার অপশন বাদ**
  - ব্যবহার সহজ করা হয়েছে
- 💰 **ব্যালেন্স ট্র্যাকিং ফিক্স**
  - নিচ থেকে উপরের দিকে ব্যালেন্স ক্যালকুলেশন ঠিক করা হয়েছে
- 🏷️ **স্কুল ডিলিট হলেও লগে নাম থাকবে**
  - এই আপডেটের পর থেকে কার্যকর
- 🖼️ **স্কুল ছবির প্রিভিউ**
  - ছবিতে ক্লিক করলে বড় করে দেখা যায়
- ♻️ **স্মুথ রিস্টোর সিস্টেম**
  - রিস্টোর ও লগ দুটোই ঠিকভাবে কাজ করে

---

### 🔖 ভার্সন `1.05.01`

#### ✨ উন্নয়নসমূহ
- 💼 **অ্যাকাউন্ট ক্যাটাগরি আপডেট**
- 📂 **সাইডবার অর্ডার পরিবর্তন**
- 📅 **ড্যাশবোর্ড টাইম রেঞ্জ**
  - ডিফল্ট: This Month → Lifetime
- 🔐 **ক্যাটাগরি ভ্যালিডেশন যুক্ত**

---

## 🧠 প্রধান ফিচারসমূহ

- 🏫 স্কুল ব্যবস্থাপনা
- 🔔 নোটিফিকেশন বেল ও অনরিড রিমাইন্ডার কাউন্টার
- 🔍 ইনস্ট্যান্ট লাইভ সার্চ (নাম, ফোন, স্থান)
- 📝 নোট এবং লাস্ট নোট প্রিভিউ সুবিধা
- 🧾 ইনভয়েস সিস্টেম
- 💰 হিসাব ও ব্যালেন্স ট্র্যাকিং (মাসিক, বার্ষিক ও ওয়েবসাইট ফি)
- 🗑️ ট্র্যাশ ও রিস্টোর
- 📊 অ্যাকশন ভিত্তিক লগ
- 🖼️ ছবি প্রিভিউ
- 🔔 রিসেন্ট অ্যাক্টিভিটি ড্যাশবোর্ড

---

## 🛠️ টেকনোলজি

- **Backend:** PHP (PDO)
- **Frontend:** Bootstrap 5, JavaScript
- **Database:** MySQL
- **Logging:** Custom logging system

---

## ✨ নির্মাতা

Developer Jasim  
01601610105