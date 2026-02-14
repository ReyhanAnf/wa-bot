<?php

namespace App\Commands\Fun;

use App\Contracts\CommandInterface;

class CekKhodamCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $name = implode(' ', $args);

        if (empty($name)) {
             return ['message' => "👻 Gunakan: `/cekkhodam [nama]`", 'source' => 'bot_rule'];
        }

        $khodams = [
            "Kulkas 2 Pintu", "Rice Cooker Rusak", "Tutup Botol", "Pecel Lele",
            "Mochicinno Cincau", "Sapu Lidi", "Kecoa Terbang", "Kucing Oren",
            "Naga Indosiar", "Kuntilanak Merah", "Tuyul Gondrong", "Genderuwo Glowing",
            "Martabak Manis", "Seblak Ceker", "Es Teh Manis", "Bakwan Jagung"
        ];

        // Consistent result based on name
        $index = abs(crc32($name)) % count($khodams);
        $khodam = $khodams[$index];

        return ['message' => "👻 *Cek Khodam*\n\n" .
               "👤 Nama: {$name}\n" .
               "👹 Khodam Kamu: *{$khodam}*", 'source' => 'bot_rule'];
    }
}
