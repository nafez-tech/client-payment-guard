status.txt = active
status.txt = close

فتح status.txt في الفرع بتاع العميل.

اكتب فيه active → الموقع يفتح عادي.

اكتب فيه close → الموقع يتقفل على طول (مش محتاج تدخل داشبورد).
------------
Client Payment Guard

هذا السكربت يضيف حماية للموقع داخل مجلد:

wp-content/mu-plugins/


ويتيح لك التحكم في تشغيل أو إيقاف موقع العميل عن بُعد من خلال GitHub.

🚀 خطوات الاستخدام
1. إضافة البلجن

انسخ الملف client-payment-guard.php إلى المسار:

wp-content/mu-plugins/


أي إضافة في هذا المجلد تعمل تلقائيًا ولن تظهر في لوحة التحكم.

2. إنشاء برنش لكل عميل

في مستودع GitHub اعمل Branch جديد باسم العميل.

داخل البرنش، أنشئ ملف نصي باسم:

status.txt


محتوى الملف يكون:

active → الموقع يعمل بشكل طبيعي.

close → الموقع يتوقف ويظهر تنبيه الدفع.

3. الحصول على رابط الملف الصحيح

بعد إنشاء ملف status.txt داخل البرنش الخاص بالعميل:

افتح الملف من واجهة GitHub.

اضغط على زر Raw.

انسخ الرابط من المتصفح (سيكون شكله مثل):

https://raw.githubusercontent.com/USERNAME/REPOSITORY/BRANCH/status.txt


⚠️ تأكد أن اسم الـ Branch يخص العميل، لأن كل عميل له ملف حالة خاص به.

ضع الرابط في الكود مكان:

define('CPG_STATUS_URL', 'https://raw.githubusercontent.com/USERNAME/REPOSITORY/BRANCH/status.txt');

4. تعديل الثيم في الكود

غيّر السطر التالي لاسم مجلد الثيم المستخدم:

define('CPG_THEME_SLUG', 'hello-elementor');

5. التحكم في الموقع

لتشغيل الموقع: ضع في status.txt كلمة:

active


لإيقاف الموقع: ضع في status.txt كلمة:

close

✅ النتيجة

لا يمكن للعميل تعطيل البلجن لأنه في mu-plugins.

يمكنك التحكم في موقع العميل عن بُعد فقط من خلال تعديل ملف status.txt على GitHub.
