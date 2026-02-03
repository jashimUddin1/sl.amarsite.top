# 📘 School Note Manager

<a name="English"></a>
🌐 **Language:** English | [বাংলা](#বাংলা)

A powerful and evolving **School Management & Activity Tracking System** designed to manage  
**schools, notes, invoices, accounts, balances, trash, and logs** — all in one centralized platform.

🔗 **Live Project:** https://sl.amarsite.top

---

## 🚀 Latest Release

### Version `1.05.04` — ** invoice auto create off **

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
- 📝 Notes with full activity logs
- 🧾 Invoice management with logging
- 💰 Accounts & balance tracking
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
যার মাধ্যমে এক জায়গা থেকেই **স্কুল, নোট, ইনভয়েস, হিসাব, ব্যালেন্স, ট্র্যাশ ও লগ** পরিচালনা করা যায়।

🔗 **লাইভ প্রজেক্ট:** https://sl.amarsite.top

---

## 🚀 সর্বশেষ আপডেট

### 🔖 ভার্সন `1.05.03` — **অ্যাকাউন্ট ড্যাশবোর্ড আপডেট**
> এই আপডেটে **ক্যাটাগরি অনুযায়ী খরচ ট্র্যাকিং** এবং ডিটেইলস পেজ যোগ করা হয়েছে, যাতে হিসাব আরও সহজ হয়।

#### ✨ নতুন কী আছে
- 👥 **ক্যাটাগরি অনুযায়ী খরচ কার্ড (Raja / Yasin)**
  - ড্যাশবোর্ডে এখন **Raja** এবং **Yasin** এর খরচ আলাদা আলাদা দেখাবে
  - ডাটা নেওয়া হয় `accounts` টেবিল থেকে (`category` + `amount`)
  - টাইম রেঞ্জ ফিল্টার ঠিকভাবে কাজ করে:
    **Today / This Month / This Year / Last Year / Lifetime / Custom**
- 📄 **ক্যাটাগরি ডিটেইলস পেজ যুক্ত**
  - ক্যাটাগরি অনুযায়ী খরচের এন্ট্রি দেখার জন্য নতুন পেজ যোগ করা হয়েছে:
    - `/pages/category_details.php?category=Raja`
    - `/pages/category_details.php?category=Yasin`
  - নির্দিষ্ট ক্যাটাগরির সব এন্ট্রি + মোট খরচ দেখা যায়
  - ড্যাশবোর্ডের “View Details” বাটন এখন ঠিকভাবে লিংক করা
- 🎨 **ড্যাশবোর্ড UI ফিক্স**
  - Raja/Yasin কার্ডের রঙ ও লেবেল ঠিক করা হয়েছে
  - কাউন্ট এখন দেখাবে **কয়টা expense entry হয়েছে**


### 🔖 ভার্সন `1.05.02` — **মেজর আপডেট**
> এই আপডেটে মূলত **লগিং সিস্টেম, ড্যাশবোর্ড অ্যাক্টিভিটি, UI ও ডাটা নির্ভরযোগ্যতা** উন্নত করা হয়েছে।

#### ✨ নতুন কী আছে
- 🧾 **ইনভয়েস ডিলিট লগ**
  - ইনভয়েস ডিলিট হলে এখন লগ হয়
  - **Dashboard → Recent Activity** তে দেখা যায়
- 📊 **ড্যাশবোর্ড আপডেট**
  - লগ ভিউ লিংক ঠিক করা হয়েছে
- 🏫 **স্কুল লগ হিস্ট্রি**
  - হিস্ট্রি আপডেট
  - রিডাইরেক্ট সমস্যা সমাধান
- 🖼️ **ফ্যাভিকন যুক্ত**
  - ব্রাউজার টাইটেল বারে PNG আইকন
  - সোর্স: https://edurlab.com
- 📝 **সম্পূর্ণ লগিং সিস্টেম**
  - নোট আপডেট লগ
  - স্কুল ইনভয়েস তৈরি ও ডিলিট লগ
  - স্কুল ডিলিট ও রিস্টোর লগ
- ⏱️ **নোট আপডেট টাইম এরর ঠিক করা হয়েছে**
- 🗑️ **ট্র্যাশ সিস্টেম উন্নত**
  - সেশন সমস্যা ঠিক করা হয়েছে
- 🔍 **নোট ফিল্টার অপশন বাদ**
  - ব্যবহার সহজ করা হয়েছে
- 💰 **ব্যালেন্স ট্র্যাকিং ফিক্স**
  - নিচ থেকে উপরের দিকে ব্যালেন্স ক্যালকুলেশন ঠিক করা হয়েছে
- 🏷️ **স্কুল ডিলিট হলেও লগে নাম থাকবে**
  - এই আপডেটের পর থেকে কার্যকর
- 🖼️ **স্কুল ছবির প্রিভিউ**
  - ছবিতে ক্লিক করলে বড় করে দেখা যায়
- ♻️ **স্মুথ রিস্টোর সিস্টেম**
  - রিস্টোর ও লগ দুটোই ঠিকভাবে কাজ করে

---

### 🔖 ভার্সন `1.05.01`

#### ✨ উন্নয়নসমূহ
- 💼 **অ্যাকাউন্ট ক্যাটাগরি আপডেট**
- 📂 **সাইডবার অর্ডার পরিবর্তন**
- 📅 **ড্যাশবোর্ড টাইম রেঞ্জ**
  - ডিফল্ট: This Month → Lifetime
- 🔐 **ক্যাটাগরি ভ্যালিডেশন যুক্ত**

---

## 🧠 প্রধান ফিচারসমূহ

- 🏫 স্কুল ব্যবস্থাপনা
- 📝 নোট ও সম্পূর্ণ লগিং
- 🧾 ইনভয়েস সিস্টেম
- 💰 হিসাব ও ব্যালেন্স ট্র্যাকিং
- 🗑️ ট্র্যাশ ও রিস্টোর
- 📊 অ্যাকশন ভিত্তিক লগ
- 🖼️ ছবি প্রিভিউ
- 🔔 রিসেন্ট অ্যাক্টিভিটি ড্যাশবোর্ড

---

## 🛠️ টেকনোলজি

- **Backend:** PHP (PDO)
- **Frontend:** Bootstrap 5, JavaScript
- **Database:** MySQL
- **Logging:** কাস্টম লগ সিস্টেম

---

## ✨ নির্মাতা

বাস্তব জীবনের স্কুল ডাটা ম্যানেজমেন্টের জন্য  
❤️ দিয়ে তৈরি ও রক্ষণাবেক্ষণ করা হয়েছে।
