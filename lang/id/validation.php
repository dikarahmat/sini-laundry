<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Bidang :attribute harus diterima.',
    'accepted_if' => 'Bidang :attribute harus diterima ketika :other adalah :value.',
    'active_url' => 'Bidang :attribute bukan URL yang valid.',
    'after' => 'Bidang :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Bidang :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Bidang :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Bidang :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Bidang :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Bidang :attribute harus berupa array.',
    'ascii' => 'Bidang :attribute hanya boleh berisi karakter dan simbol alfanumerik satu byte.',
    'before' => 'Bidang :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Bidang :attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Bidang :attribute harus memiliki antara :min dan :max item.',
        'file' => 'Bidang :attribute harus berukuran antara :min dan :max kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai antara :min dan :max.',
        'string' => 'Bidang :attribute harus memiliki panjang antara :min dan :max karakter.',
    ],
    'boolean' => 'Bidang :attribute harus bernilai true atau false.',
    'can' => 'Bidang :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Bidang :attribute bukan tanggal yang valid.',
    'date_equals' => 'Bidang :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Bidang :attribute tidak cocok dengan format :format.',
    'decimal' => 'Bidang :attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => 'Bidang :attribute harus ditolak.',
    'declined_if' => 'Bidang :attribute harus ditolak ketika :other adalah :value.',
    'different' => 'Bidang :attribute dan :other harus berbeda.',
    'digits' => 'Bidang :attribute harus terdiri dari :digits angka.',
    'digits_between' => 'Bidang :attribute harus terdiri dari antara :min dan :max angka.',
    'dimensions' => 'Bidang :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Bidang :attribute memiliki nilai yang duplikat.',
    'doesnt_end_with' => 'Bidang :attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => 'Bidang :attribute tidak boleh dimulai dengan salah satu dari: :values.',
    'email' => 'Bidang :attribute harus berupa alamat email yang valid.',
    'ends_with' => 'Bidang :attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => 'Pilihan :attribute tidak valid.',
    'exists' => 'Pilihan :attribute tidak valid.',
    'file' => 'Bidang :attribute harus berupa berkas.',
    'filled' => 'Bidang :attribute harus memiliki nilai.',
    'gt' => [
        'array' => 'Bidang :attribute harus memiliki lebih dari :max item.',
        'file' => 'Bidang :attribute harus berukuran lebih besar dari :max kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai lebih besar dari :max.',
        'string' => 'Bidang :attribute harus memiliki panjang lebih dari :max karakter.',
    ],
    'gte' => [
        'array' => 'Bidang :attribute harus memiliki :max item atau lebih.',
        'file' => 'Bidang :attribute harus berukuran lebih besar atau sama dengan :max kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai lebih besar atau sama dengan :max.',
        'string' => 'Bidang :attribute harus memiliki panjang :max karakter atau lebih.',
    ],
    'image' => 'Bidang :attribute harus berupa gambar.',
    'in' => 'Pilihan :attribute tidak valid.',
    'in_array' => 'Bidang :attribute harus ada di dalam :other.',
    'integer' => 'Bidang :attribute harus berupa bilangan bulat.',
    'ip' => 'Bidang :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Bidang :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Bidang :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Bidang :attribute harus berupa JSON string yang valid.',
    'lowercase' => 'Bidang :attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => 'Bidang :attribute harus memiliki kurang dari :min item.',
        'file' => 'Bidang :attribute harus berukuran kurang dari :min kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai kurang dari :min.',
        'string' => 'Bidang :attribute harus memiliki panjang kurang dari :min karakter.',
    ],
    'lte' => [
        'array' => 'Bidang :attribute tidak boleh memiliki lebih dari :min item.',
        'file' => 'Bidang :attribute harus berukuran kurang dari atau sama dengan :min kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai kurang dari atau sama dengan :min.',
        'string' => 'Bidang :attribute harus memiliki panjang kurang dari atau sama dengan :min karakter.',
    ],
    'mac_address' => 'Bidang :attribute harus berupa MAC address yang valid.',
    'max' => [
        'array' => 'Bidang :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Bidang :attribute harus berukuran kurang dari atau sama dengan :max kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai kurang dari atau sama dengan :max.',
        'string' => 'Bidang :attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => 'Bidang :attribute tidak boleh memiliki lebih dari :max angka.',
    'mimes' => 'Bidang :attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => 'Bidang :attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'array' => 'Bidang :attribute harus memiliki minimal :min item.',
        'file' => 'Bidang :attribute harus berukuran minimal :min kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai minimal :min.',
        'string' => 'Bidang :attribute harus memiliki panjang minimal :min karakter.',
    ],
    'min_digits' => 'Bidang :attribute harus memiliki minimal :min angka.',
    'missing' => 'Bidang :attribute harus kosong.',
    'missing_if' => 'Bidang :attribute harus kosong ketika :other adalah :value.',
    'missing_unless' => 'Bidang :attribute harus kosong kecuali :other adalah :value.',
    'missing_with' => 'Bidang :attribute harus kosong ketika :values ada.',
    'missing_with_all' => 'Bidang :attribute harus kosong ketika :values ada.',
    'multiple_of' => 'Bidang :attribute harus berupa kelipatan dari :value.',
    'not_in' => 'Pilihan :attribute tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => 'Bidang :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Bidang :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Bidang :attribute harus mengandung minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Bidang :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Bidang :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute yang dipilih sudah muncul dalam kebocoran data. Pilih :attribute yang berbeda.',
    ],
    'present' => 'Bidang :attribute harus ada.',
    'present_if' => 'Bidang :attribute harus ada ketika :other adalah :value.',
    'present_unless' => 'Bidang :attribute harus ada kecuali :other adalah :value.',
    'present_with' => 'Bidang :attribute harus ada ketika :values ada.',
    'present_with_all' => 'Bidang :attribute harus ada ketika :values ada.',
    'prohibited' => 'Bidang :attribute dilarang.',
    'prohibited_if' => 'Bidang :attribute dilarang ketika :other adalah :value.',
    'prohibited_unless' => 'Bidang :attribute dilarang kecuali :other ada di :values.',
    'prohibits' => 'Bidang :attribute melarang :other untuk ada.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Bidang :attribute harus berisi entri untuk: :values.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other adalah :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi ketika :other diterima.',
    'required_if_declined' => 'Kolom :attribute wajib diisi ketika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other ada di :values.',
    'required_with' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi ketika tidak ada :values yang ada.',
    'same' => 'Bidang :attribute dan :other harus sama.',
    'size' => [
        'array' => 'Bidang :attribute harus berisi :size item.',
        'file' => 'Bidang :attribute harus berukuran :size kilobyte.',
        'numeric' => 'Bidang :attribute harus bernilai :size.',
        'string' => 'Bidang :attribute harus memiliki panjang :size karakter.',
    ],
    'starts_with' => 'Bidang :attribute harus dimulai dengan salah satu dari: :values.',
    'string' => 'Bidang :attribute harus berupa teks.',
    'timezone' => 'Bidang :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan. Pilih :attribute yang lain.',
    'uploaded' => 'Bidang :attribute gagal diunggah.',
    'uppercase' => 'Bidang :attribute harus berupa huruf besar.',
    'url' => 'Bidang :attribute harus berupa URL yang valid.',
    'ulid' => 'Bidang :attribute harus berupa ULID yang valid.',
    'uuid' => 'Bidang :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'phone' => [
            'required' => 'Nomor HP wajib diisi.',
        ],
        'paymentAmount' => [
            'required' => 'Jumlah pembayaran wajib diisi.',
            'min' => 'Jumlah pembayaran harus lebih dari 0 rupiah.',
        ],
        'rows' => [
            'required' => 'Tambahkan minimal satu item pesanan.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'newPassword' => 'kata sandi baru',
        'current_password' => 'kata sandi saat ini',
        'password_confirmation' => 'konfirmasi kata sandi',
        'phone' => 'nomor HP',
        'address' => 'alamat',
        'code' => 'kode pesanan',
        'lastFour' => '4 digit terakhir nomor HP',
        'notes' => 'catatan',
        'total_price' => 'total harga',
        'price' => 'harga',
        'duration_hours' => 'estimasi durasi',
        'quantity' => 'berat',
        'amount' => 'jumlah',
        'pickupDate' => 'tanggal antar',
        'rejectReason' => 'alasan penolakan',
    ],

];
