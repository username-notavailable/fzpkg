<?php

/*
|--------------------------------------------------------------------------
| Validation Language Lines
|--------------------------------------------------------------------------
|
| Le seguenti righe contengono i messaggi di errore predefiniti utilizzati
| dalla classe di validazione. Alcune di queste regole hanno più versioni,
| come le regole di dimensione. Sentiti libero di modificare ciascuno di
| questi messaggi qui.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Следующие строки содержат сообщения об ошибках по умолчанию, используемые
    | классом валидации. Некоторые из этих правил имеют несколько версий,
    | например, правила размера. Не стесняйтесь корректировать
    | любое из этих сообщений здесь.
    |
    */

    'accepted' => 'Вы должны принять :attribute.',
    'accepted_if' => 'Вы должны принять :attribute, если :other равно :value.',
    'active_url' => 'Поле :attribute содержит недействительный URL.',
    'after' => 'Поле :attribute должно содержать дату после :date.',
    'after_or_equal' => 'Поле :attribute должно содержать дату не ранее :date.',
    'alpha' => 'Поле :attribute должно содержать только буквы.',
    'alpha_dash' => 'Поле :attribute должно содержать только буквы, цифры, дефисы и подчеркивания.',
    'alpha_num' => 'Поле :attribute должно содержать только буквы и цифры.',
    'array' => 'Поле :attribute должно быть массивом.',
    'ascii' => 'Поле :attribute должно содержать только буквенно-цифровые символы и символы с одним байтом.',
    'before' => 'Поле :attribute должно содержать дату до :date.',
    'before_or_equal' => 'Поле :attribute должно содержать дату не позднее :date.',
    'between' => [
        'array' => 'Поле :attribute должно содержать от :min до :max элементов.',
        'file' => 'Размер файла в поле :attribute должен быть от :min до :max килобайт.',
        'numeric' => 'Значение поля :attribute должно быть между :min и :max.',
        'string' => 'Длина текста в поле :attribute должна быть от :min до :max символов.',
    ],
    'boolean' => 'Поле :attribute должно иметь значение логического типа.',
    'can' => 'Поле :attribute содержит недопустимое значение.',
    'confirmed' => 'Поле :attribute не совпадает с подтверждением.',
    'contains' => 'Поле :attribute не содержит обязательного значения.',
    'current_password' => 'Пароль неверен.',
    'date' => 'Поле :attribute не является датой.',
    'date_equals' => 'Поле :attribute должно быть датой, равной :date.',
    'date_format' => 'Поле :attribute не соответствует формату :format.',
    'decimal' => 'Поле :attribute должно содержать :decimal десятичных знаков.',
    'declined' => 'Поле :attribute должно быть отклонено.',
    'declined_if' => 'Поле :attribute должно быть отклонено, если :other равно :value.',
    'different' => 'Значения полей :attribute и :other должны различаться.',
    'digits' => 'Длина поля :attribute должна быть :digits цифр.',
    'digits_between' => 'Длина поля :attribute должна быть между :min и :max цифрами.',
    'dimensions' => 'Поле :attribute имеет недопустимые размеры изображения.',
    'distinct' => 'Поле :attribute содержит повторяющееся значение.',
    'doesnt_end_with' => 'Поле :attribute не должно заканчиваться одним из следующих значений: :values.',
    'doesnt_start_with' => 'Поле :attribute не должно начинаться с одного из следующих значений: :values.',
    'email' => 'Поле :attribute должно содержать действительный адрес электронной почты.',
    'ends_with' => 'Поле :attribute должно заканчиваться одним из следующих значений: :values.',
    'enum' => 'Выбранное значение для :attribute некорректно.',
    'exists' => 'Выбранное значение для :attribute некорректно.',
    'extensions' => 'Поле :attribute должно быть файлом одного из следующих типов: :values.',
    'file' => 'Поле :attribute должно содержать файл.',
    'filled' => 'Поле :attribute обязательно для заполнения.',
    'gt' => [
        'array' => 'Количество элементов в поле :attribute должно быть больше :value.',
        'file' => 'Размер файла в поле :attribute должен быть больше :value килобайт.',
        'numeric' => 'Значение поля :attribute должно быть больше :value.',
        'string' => 'Длина текста в поле :attribute должна быть больше :value символов.',
    ],
    'gte' => [
        'array' => 'Количество элементов в поле :attribute должно быть :value или больше.',
        'file' => 'Размер файла в поле :attribute должен быть :value килобайт или больше.',
        'numeric' => 'Значение поля :attribute должно быть :value или больше.',
        'string' => 'Длина текста в поле :attribute должна быть :value символов или больше.',
    ],
    'hex_color' => 'Поле :attribute должно быть действительным шестнадцатеричным цветом.',
    'image' => 'Поле :attribute должно содержать изображение.',
    'in' => 'Значение поля :attribute некорректно.',
    'in_array' => 'Поле :attribute должно присутствовать в :other.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'ip' => 'Поле :attribute должно содержать действительный IP-адрес.',
    'ipv4' => 'Поле :attribute должно содержать действительный IPv4-адрес.',
    'ipv6' => 'Поле :attribute должно содержать действительный IPv6-адрес.',
    'json' => 'Поле :attribute должно содержать корректную строку JSON.',
    'list' => 'Поле :attribute должно быть списком.',
    'lowercase' => 'Поле :attribute должно быть в нижнем регистре.',
    'lt' => [
        'array' => 'Количество элементов в поле :attribute должно быть меньше :value.',
        'file' => 'Размер файла в поле :attribute должен быть меньше :value килобайт.',
        'numeric' => 'Значение поля :attribute должно быть меньше :value.',
        'string' => 'Длина текста в поле :attribute должна быть меньше :value символов.',
    ],
    'lte' => [
        'array' => 'Количество элементов в поле :attribute не должно превышать :value.',
        'file' => 'Размер файла в поле :attribute должен быть :value килобайт или меньше.',
        'numeric' => 'Значение поля :attribute должно быть :value или меньше.',
        'string' => 'Длина текста в поле :attribute должна быть :value символов или меньше.',
    ],
    'mac_address' => 'Поле :attribute должно содержать корректный MAC-адрес.',
    'max' => [
        'array' => 'Количество элементов в поле :attribute не должно превышать :max.',
        'file' => 'Размер файла в поле :attribute не должен превышать :max килобайт.',
        'numeric' => 'Значение поля :attribute не должно быть больше :max.',
        'string' => 'Длина текста в поле :attribute не должна превышать :max символов.',
    ],
    'max_digits' => 'Поле :attribute не должно содержать больше :max цифр.',
    'mimes' => 'Поле :attribute должно быть файлом одного из следующих типов: :values.',
    'mimetypes' => 'Поле :attribute должно быть файлом одного из следующих типов: :values.',
    'min' => [
        'array' => 'Количество элементов в поле :attribute должно быть не меньше :min.',
        'file' => 'Размер файла в поле :attribute должен быть не меньше :min килобайт.',
        'numeric' => 'Значение поля :attribute должно быть не меньше :min.',
        'string' => 'Длина текста в поле :attribute должна быть не меньше :min символов.',
    ],
    'min_digits' => 'Поле :attribute должно содержать не меньше :min цифр.',
    'missing' => 'Поле :attribute должно отсутствовать.',
    'missing_if' => 'Поле :attribute должно отсутствовать, если :other равно :value.',
    'missing_unless' => 'Поле :attribute должно отсутствовать, если :other не равно :value.',
    'missing_with' => 'Поле :attribute должно отсутствовать, если присутствует :values.',
    'missing_with_all' => 'Поле :attribute должно отсутствовать, если присутствуют :values.',
    'multiple_of' => 'Значение поля :attribute должно быть кратно :value.',
    'not_in' => 'Значение поля :attribute некорректно.',
    'not_regex' => 'Поле :attribute имеет некорректный формат.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один символ.',
        'uncompromised' => 'Значение поля :attribute было обнаружено в утечке данных. Пожалуйста, выберите другое значение.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'prohibited' => 'Поле :attribute запрещено к заполнению.',
    'prohibited_if' => 'Поле :attribute запрещено, если :other равно :value.',
    'prohibited_unless' => 'Поле :attribute запрещено, если :other не входит в :values.',
    'prohibits' => 'Поле :attribute запрещает наличие поля :other.',
    'regex' => 'Поле :attribute имеет некорректный формат.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать записи для: :values.',
    'required_if' => 'Поле :attribute обязательно для заполнения, когда :other равно :value.',
    'required_if_accepted' => 'Поле :attribute обязательно, если :other принято.',
    'required_unless' => 'Поле :attribute обязательно, если :other не входит в :values.',
    'required_with' => 'Поле :attribute обязательно для заполнения, когда :values указано.',
    'required_with_all' => 'Поле :attribute обязательно для заполнения, когда указаны :values.',
    'required_without' => 'Поле :attribute обязательно для заполнения, когда :values не указано.',
    'required_without_all' => 'Поле :attribute обязательно для заполнения, когда ни одно из :values не указано.',
    'same' => 'Значение поля :attribute должно совпадать со значением :other.',
    'size' => [
        'array' => 'Количество элементов в поле :attribute должно быть :size.',
        'file' => 'Размер файла в поле :attribute должен быть :size килобайт.',
        'numeric' => 'Значение поля :attribute должно быть :size.',
        'string' => 'Длина текста в поле :attribute должна быть :size символов.',
    ],
    'starts_with' => 'Поле :attribute должно начинаться с одного из следующих значений: :values.',
    'string' => 'Поле :attribute должно быть строкой.',
    'timezone' => 'Поле :attribute должно содержать корректный часовой пояс.',
    'ulid' => 'Поле :attribute должно содержать корректный ULID.',
    'unique' => 'Такое значение поля :attribute уже существует.',
    'uploaded' => 'Загрузка поля :attribute не удалась.',
    'uppercase' => 'Поле :attribute должно содержать заглавные буквы.',
    'url' => 'Поле :attribute имеет некорректный формат URL.',
    'uuid' => 'Поле :attribute должно содержать корректный UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Qui puoi specificare messaggi di validazione personalizzati per attributi
    | utilizzando la convenzione "attribute.rule" per nominare le righe. Questo
    | consente di specificare rapidamente una linea di messaggio personalizzata
    | per una determinata regola dell'attributo.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'пользовательское сообщение',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Le seguenti righe vengono utilizzate per scambiare il segnaposto degli
    | attributi con qualcosa di più leggibile, come "Indirizzo Email" invece di
    | "email". Questo aiuta a rendere il nostro messaggio più espressivo.
    |
    */

    'attributes' => [],
];
