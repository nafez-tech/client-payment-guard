هذا المستند يشرح كيف تجهز الملفات الخمسة على موقع ووردبريس لتعمل معاً:


الملفات:

client-payment-check.php — (في wp-content/mu-plugins/)
مهمته: ملف MU Plugin يقرأ ملف status.txt من GitHub ويكتب الحالة في wp_options باسم wp_wooPaymentStatus.


class-option-payment-dbCheck.php — (في wp-admin/includes/)
مهمته: قراءة الحالة من wp_options وإظهار صفحة الإيقاف إذا كانت close.

status.txt — (ملف على GitHub branch لكل عميل)
المحتوى: كلمة واحدة فقط: active أو close

suspended-page.php — (في wp-admin/includes/)
مهمته: صفحة HTML التي تظهر للعميل عندما تكون الحالة close.

suspended-page-style.css — (في wp-admin/includes/)
مهمته: الـ CSS الخاص بالصفحة المعلقة
-------------
الخُطوات بالتفصيل
1) رفع الملف client-payment-check.php إلى mu-plugins

ضع الملف في المسار التالي على السيرفر:

/wp-content/mu-plugins/client-payment-check.php
في داخل هذا الملف يجب أن يكون:

رابط Raw لملف status.txt على GitHub (مثال):

https://raw.githubusercontent.com/<USER>/<REPO>/<BRANCH>/status.txt

2) رفع ملفات class-option-payment-dbCheck.php, suspended-page.php, suspended-page-style.css

ضع هذه الملفات داخل:

/wp-admin/includes/

3) تعديل wp-config.php — (اختياري لكن يوصى به)

إذا أردت أن تجعل ملف class-option-payment-dbCheck.php يُحمّل مبكراً (حتى لو حذف أحد المجلدات)، يمكنك إضافة سطر واحد في wp-config.php قبل السطر:

/* That's all, stop editing! Happy publishing. */


أضف:

if (file_exists(ABSPATH . 'wp-admin/includes/class-option-payment-dbCheck.php')) {
    require_once ABSPATH . 'wp-admin/includes/class-option-payment-dbCheck.php';
}


هذا يضمن أن الكود الذي يتحقق من الحالة سيتم تحميله مبكراً جداً. تحذير: تأكد أن الملف class-option-payment-dbCheck.php لا يحتوي على استدعاءات تعتمد على وظائف WP التي لم تتحمل بعد — لذا من الأفضل أن يضيف هذا الملف الـ hook (init) بدلاً من تنفيذ الفحص مباشرة عند require.



4) إعداد ملف status.txt على GitHub

في المستودع الخاص بكل عميل، أنشئ branch خاص بالعميل (مثلاً client1) وافتح ملف status.txt.

محتوى الملف يجب أن يكون سطر واحد فقط:

active


أو

close

-------------
🔹 اسم الصف (option_name) اللي هيتخزن في قاعدة البيانات هو:
wp_wooPaymentStatus
