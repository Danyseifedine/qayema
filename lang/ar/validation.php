<?php

/*
| Arabic validation messages, for the rules the API actually uses. A rule not
| listed falls back to Laravel's English message. Field names come from
| `attributes` at the bottom; a translatable field's per-language entries
| (name.en, name.ar, …) share one name through the `*` keys.
*/

return [
    'array' => 'يجب أن يكون حقل :attribute قائمة.',
    'alpha' => 'يجب أن يحتوي حقل :attribute على أحرف فقط.',
    'boolean' => 'يجب أن تكون قيمة حقل :attribute نعم أو لا.',
    'confirmed' => 'تأكيد حقل :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date_format' => 'يجب أن يطابق حقل :attribute الصيغة :format.',
    'dimensions' => 'أبعاد صورة :attribute غير مناسبة.',
    'email' => 'يجب أن يكون حقل :attribute بريداً إلكترونياً صالحاً.',
    'exists' => 'القيمة المختارة في :attribute غير صالحة.',
    'file' => 'يجب أن يكون حقل :attribute ملفاً.',
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'in' => 'القيمة المختارة في :attribute غير صالحة.',
    'integer' => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'max' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يتجاوز حجم :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا يكون حقل :attribute أكبر من :max.',
        'string' => 'يجب ألا يتجاوز حقل :attribute :max حرفاً.',
    ],
    'mimes' => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :min عناصر على الأقل.',
        'numeric' => 'يجب أن يكون حقل :attribute :min على الأقل.',
        'string' => 'يجب أن يكون حقل :attribute :min أحرف على الأقل.',
    ],
    'numeric' => 'يجب أن يكون حقل :attribute رقماً.',
    'regex' => 'صيغة حقل :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'size' => [
        'string' => 'يجب أن يكون حقل :attribute :size أحرف.',
    ],
    'string' => 'يجب أن يكون حقل :attribute نصاً.',
    'timezone' => 'يجب أن يكون حقل :attribute منطقة زمنية صالحة.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'url' => 'يجب أن يكون حقل :attribute رابطاً صالحاً.',

    'attributes' => [
        'name' => 'الاسم',
        'name.*' => 'الاسم',
        'description' => 'الوصف',
        'description.*' => 'الوصف',
        'ingredients' => 'المكونات',
        'ingredients.*' => 'المكونات',
        'price' => 'السعر',
        'category_id' => 'القسم',
        'is_available' => 'التوفر',
        'phone' => 'رقم الهاتف',
        'country_code' => 'الدولة',
        'currency' => 'العملة',
        'timezone' => 'المنطقة الزمنية',
        'google_maps_url' => 'رابط الموقع',
        'opening_hours' => 'ساعات العمل',
        'opening_hours.*.open' => 'وقت الفتح',
        'opening_hours.*.close' => 'وقت الإغلاق',
        'second_locale' => 'اللغة الثانية',
        'default_locale' => 'لغة الافتتاح',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'platform' => 'المنصة',
        'url' => 'الرابط',
        'file' => 'الملف',
        'logo_key' => 'الشعار',
        'cover_image_key' => 'صورة الغلاف',
        'image_key' => 'الصورة',
        'range' => 'الفترة',
        'status' => 'الحالة',
        'message' => 'الرسالة',
        'note' => 'الملاحظة',
    ],
];
