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

    'accepted' => ':attribute muss akzeptiert werden.',
    'accepted_if' => ':attribute muss akzeptiert werden, wenn :other :value ist.',
    'active_url' => ':attribute ist keine gültige URL.',
    'after' => ':attribute muss ein Datum nach :date sein.',
    'after_or_equal' => ':attribute muss ein Datum nach oder gleich :date sein.',
    'alpha' => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => ':attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
    'alpha_num' => ':attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => ':attribute muss ein Array sein.',
    'ascii' => ':attribute darf nur alphanumerische Zeichen und Symbole mit einem Byte enthalten.',
    'before' => ':attribute muss ein Datum vor :date sein.',
    'before_or_equal' => ':attribute muss ein Datum vor oder gleich :date sein.',
    'between' => [
        'array' => ':attribute muss zwischen :min und :max Elemente haben.',
        'file' => ':attribute muss zwischen :min und :max Kilobytes groß sein.',
        'numeric' => ':attribute muss zwischen :min und :max liegen.',
        'string' => ':attribute muss zwischen :min und :max Zeichen lang sein.',
    ],
    'boolean' => ':attribute muss entweder "wahr" oder "falsch" sein.',
    'can' => ':attribute enthält einen nicht erlaubten Wert.',
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'contains' => ':attribute fehlt ein erforderlicher Wert.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => ':attribute ist kein gültiges Datum.',
    'date_equals' => ':attribute muss ein Datum gleich :date sein.',
    'date_format' => ':attribute entspricht nicht dem Format :format.',
    'decimal' => ':attribute muss :decimal Dezimalstellen haben.',
    'declined' => ':attribute muss abgelehnt werden.',
    'declined_if' => ':attribute muss abgelehnt werden, wenn :other :value ist.',
    'different' => ':attribute und :other müssen unterschiedlich sein.',
    'digits' => ':attribute muss :digits Stellen haben.',
    'digits_between' => ':attribute muss zwischen :min und :max Stellen haben.',
    'dimensions' => ':attribute hat ungültige Bildabmessungen.',
    'distinct' => ':attribute hat einen doppelten Wert.',
    'doesnt_end_with' => ':attribute darf nicht mit einem der folgenden Werte enden: :values.',
    'doesnt_start_with' => ':attribute darf nicht mit einem der folgenden Werte beginnen: :values.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => ':attribute muss mit einem der folgenden Werte enden: :values.',
    'enum' => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'exists' => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'extensions' => ':attribute muss eine Datei des Typs :values sein.',
    'file' => ':attribute muss eine Datei sein.',
    'filled' => ':attribute muss ausgefüllt sein.',
    'gt' => [
        'array' => ':attribute muss mehr als :value Elemente haben.',
        'file' => ':attribute muss größer als :value Kilobytes sein.',
        'numeric' => ':attribute muss größer als :value sein.',
        'string' => ':attribute muss länger als :value Zeichen sein.',
    ],
    'gte' => [
        'array' => ':attribute muss :value oder mehr Elemente haben.',
        'file' => ':attribute muss größer oder gleich :value Kilobytes sein.',
        'numeric' => ':attribute muss größer oder gleich :value sein.',
        'string' => ':attribute muss länger oder gleich :value Zeichen sein.',
    ],
    'hex_color' => ':attribute muss eine gültige hexadezimale Farbe sein.',
    'image' => ':attribute muss ein Bild sein.',
    'in' => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'in_array' => ':attribute muss in :other existieren.',
    'integer' => ':attribute muss eine Ganzzahl sein.',
    'ip' => ':attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => ':attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => ':attribute muss eine gültige IPv6-Adresse sein.',
    'json' => ':attribute muss ein gültiger JSON-String sein.',
    'list' => ':attribute muss eine Liste sein.',
    'lowercase' => ':attribute muss in Kleinbuchstaben sein.',
    'lt' => [
        'array' => ':attribute muss weniger als :value Elemente haben.',
        'file' => ':attribute muss kleiner als :value Kilobytes sein.',
        'numeric' => ':attribute muss kleiner als :value sein.',
        'string' => ':attribute muss kürzer als :value Zeichen sein.',
    ],
    'lte' => [
        'array' => ':attribute darf nicht mehr als :value Elemente haben.',
        'file' => ':attribute muss kleiner oder gleich :value Kilobytes sein.',
        'numeric' => ':attribute muss kleiner oder gleich :value sein.',
        'string' => ':attribute muss kürzer oder gleich :value Zeichen sein.',
    ],
    'mac_address' => ':attribute muss eine gültige MAC-Adresse sein.',
    'max' => [
        'array' => ':attribute darf nicht mehr als :max Elemente haben.',
        'file' => ':attribute darf nicht größer als :max Kilobytes sein.',
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'string' => ':attribute darf nicht länger als :max Zeichen sein.',
    ],
    'max_digits' => ':attribute darf nicht mehr als :max Stellen haben.',
    'mimes' => ':attribute muss eine Datei des Typs :values sein.',
    'mimetypes' => ':attribute muss eine Datei des Typs :values sein.',
    'min' => [
        'array' => ':attribute muss mindestens :min Elemente haben.',
        'file' => ':attribute muss mindestens :min Kilobytes groß sein.',
        'numeric' => ':attribute muss mindestens :min sein.',
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
    ],
    'min_digits' => ':attribute muss mindestens :min Stellen haben.',
    'missing' => ':attribute muss fehlen.',
    'missing_if' => ':attribute muss fehlen, wenn :other :value ist.',
    'missing_unless' => ':attribute muss fehlen, außer :other ist :value.',
    'missing_with' => ':attribute muss fehlen, wenn :values vorhanden ist.',
    'missing_with_all' => ':attribute muss fehlen, wenn :values vorhanden sind.',
    'multiple_of' => ':attribute muss ein Vielfaches von :value sein.',
    'not_in' => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'not_regex' => 'Das Format von :attribute ist ungültig.',
    'numeric' => ':attribute muss eine Zahl sein.',
    'password' => [
        'letters' => ':attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':attribute muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers' => ':attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':attribute muss mindestens ein Symbol enthalten.',
        'uncompromised' => ':attribute ist in einem Datenleck aufgetaucht. Bitte wählen Sie ein anderes :attribute.',
    ],
    'present' => ':attribute muss vorhanden sein.',
    'prohibited' => ':attribute ist verboten.',
    'prohibited_if' => ':attribute ist verboten, wenn :other :value ist.',
    'prohibited_unless' => ':attribute ist verboten, außer :other ist in :values.',
    'prohibits' => ':attribute verbietet das Vorhandensein von :other.',
    'regex' => 'Das Format von :attribute ist ungültig.',
    'required' => ':attribute ist erforderlich.',
    'required_array_keys' => ':attribute muss Einträge für :values enthalten.',
    'required_if' => ':attribute ist erforderlich, wenn :other :value ist.',
    'required_if_accepted' => ':attribute ist erforderlich, wenn :other akzeptiert wird.',
    'required_unless' => ':attribute ist erforderlich, außer :other ist in :values.',
    'required_with' => ':attribute ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all' => ':attribute ist erforderlich, wenn :values vorhanden sind.',
    'required_without' => ':attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all' => ':attribute ist erforderlich, wenn keiner von :values vorhanden ist.',
    'same' => ':attribute und :other müssen übereinstimmen.',
    'size' => [
        'array' => ':attribute muss :size Elemente enthalten.',
        'file' => ':attribute muss :size Kilobytes groß sein.',
        'numeric' => ':attribute muss :size sein.',
        'string' => ':attribute muss :size Zeichen lang sein.',
    ],
    'starts_with' => ':attribute muss mit einem der folgenden Werte beginnen: :values.',
    'string' => ':attribute muss eine Zeichenkette sein.',
    'timezone' => ':attribute muss eine gültige Zeitzone sein.',
    'ulid' => ':attribute muss eine gültige ULID sein.',
    'unique' => ':attribute wurde bereits verwendet.',
    'uploaded' => ':attribute konnte nicht hochgeladen werden.',
    'uppercase' => ':attribute muss in Großbuchstaben geschrieben werden.',
    'url' => ':attribute muss eine gültige URL sein.',
    'uuid' => ':attribute muss eine gültige UUID sein.',

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
            'rule-name' => 'benutzerdefinierte Nachricht',
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

