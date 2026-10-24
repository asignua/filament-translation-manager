<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Support\Synchronizer;
use Illuminate\Database\Seeder;
use Workbench\App\Models\User;

/**
 * Screenshot data (`vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DemoSeeder'` after
 * `workbench:build`). Log in as emma@example.com / password.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'emma@example.com'], ['name' => 'Emma Carter', 'password' => 'password']);

        // What a scan finds in the workbench views and lang files...
        Synchronizer::make()->sync();

        // ...plus a few rows with deliberately missing locales.
        $rows = [
            ['*', 'Add to basket', ['en' => 'Add to basket', 'uk' => 'Додати в кошик']],
            ['*', 'Free delivery over :amount', ['en' => 'Free delivery over :amount']],
            ['*', 'Your order has shipped', ['en' => 'Your order has shipped', 'de' => 'Ihre Bestellung wurde versandt']],
            ['*', 'Це ключ українською', ['uk' => 'Це ключ українською', 'en' => 'This is a key in Ukrainian']],
            ['messages', 'checkout.title', ['en' => 'Checkout', 'uk' => 'Оформлення замовлення', 'de' => 'Kasse']],
            ['messages', 'checkout.pay', ['en' => 'Pay now']],
        ];

        foreach ($rows as [$group, $key, $text]) {
            $row = Translation::query()->firstOrNew(['group' => $group, 'key' => $key]);
            $row->text = array_merge($row->text ?? [], $text);
            $row->save();
        }
    }
}
